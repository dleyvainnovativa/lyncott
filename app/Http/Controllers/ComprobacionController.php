<?php

namespace App\Http\Controllers;

use App\Models\Banco;
use App\Models\Comprobacion;
use App\Models\Empleado;
use App\Models\Gasto;
use App\Models\SmartkerPayload;
use App\Services\SmartkerClient;
use App\Services\SmartkerPayloadBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ComprobacionController extends Controller
{
    /** Landing page with the two flow options (anticipo / comprobación) + Microsoft login. */
    public function welcome(Request $request): View
    {
        return view('bienvenida', [
            'user'      => $request->session()->get('firebase_user'),
            'firebase'  => config('firebase.web'),
            'authError' => $request->session()->get('auth_error'),
        ]);
    }

    /** Flow shell (sidebar + paginated steps). $flujo comes from the route. */
    public function flujo(string $flujo): View
    {
        abort_unless(in_array($flujo, ['anticipo', 'comprobacion'], true), 404);

        return view('comprobacion.flujo', [
            'flujo'       => $flujo,
            'categorias'  => config('smartker.categorias', []),
            'centros'     => $this->centrosCosto(),
            'tiposGasto'  => $this->tiposGasto($flujo),
            'bancos'      => Banco::where('activo', true)->orderBy('nombre')->get(['clave', 'nombre']),
        ]);
    }

    public function index(): View
    {
        return view('comprobacion.index', [
            'categorias'  => config('smartker.categorias', []),
            'centros'     => $this->centrosCosto(),
            'tiposGasto'  => $this->tiposGasto(),
            'bancos'      => Banco::where('activo', true)->orderBy('nombre')->get(['clave', 'nombre']),
        ]);
    }

    /** Phase 2 — lookup employee by número de empleado + RFC. */
    public function buscarEmpleado(Request $request): JsonResponse
    {
        $data = $request->validate([
            'numero_empleado' => ['required', 'string', 'max:30'],
        ], [], [
            'numero_empleado' => 'número de empleado',
        ]);

        $empleado = Empleado::query()
            ->where('numero_empleado', $data['numero_empleado'])
            ->first();

        if (! $empleado) {
            return response()->json([
                'message' => 'No encontramos un empleado con ese número. Verifica el dato.',
            ], 404);
        }

        $tarjetas = $empleado->tarjetasActivas()
            ->orderBy('nombre')
            ->get(\App\Models\Tarjeta::PUBLIC_FIELDS)
            ->toArray();

        return response()->json([
            'empleado' => $empleado->only(Empleado::PUBLIC_FIELDS),
            'tarjetas' => $tarjetas,
        ]);
    }

    /** Phase 4 — (optional) parse a CFDI 4.0 XML server-side. Front parses client-side. */
    public function parseCfdi(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'El parseo de CFDI se realiza en el navegador en esta versión.',
        ], 501);
    }

    /** Server-side proxy to the Venvers fiscal-validation service (keeps the api-key secret). */
    public function validarCfdi(Request $request): JsonResponse
    {
        $xml = $this->decodeXml((string) $request->input('xmlB64', ''));
        if ($xml === '') {
            return response()->json(['ok' => false, 'message' => 'No se recibió el XML del comprobante.'], 422);
        }

        $key = (string) config('venvers.key');
        if ($key === '') {
            return response()->json([
                'ok'      => false,
                'message' => 'La validación fiscal no está configurada (falta VENVERS_API_KEY).',
            ], 503);
        }

        try {
            $resp = \Illuminate\Support\Facades\Http::withHeaders(['x-api-key' => $key])
                ->timeout((int) config('venvers.timeout', 40))
                ->attach('Archivo', $xml, 'comprobante.xml')
                ->post((string) config('venvers.url'));
        } catch (\Throwable $e) {
            Log::warning('Venvers request failed', ['error' => $e->getMessage()]);
            return response()->json(['ok' => false, 'message' => 'No se pudo contactar el servicio de validación.'], 502);
        }

        if (! $resp->ok()) {
            return response()->json([
                'ok'      => false,
                'message' => 'El servicio de validación respondió con un error (' . $resp->status() . ').',
            ], 502);
        }

        return response()->json(\App\Services\ValidacionFiscal::normalize((array) $resp->json()));
    }

    /** Decode a (possibly data-URI) base64 string to raw XML text. */
    private function decodeXml(string $b64): string
    {
        if ($b64 === '') {
            return '';
        }
        if (str_contains($b64, ',')) {
            $b64 = substr($b64, strpos($b64, ',') + 1);
        }
        return (string) base64_decode($b64, true);
    }

    /** Phase 5 — render the review-preview PDF (detalle for comprobación, summary for anticipo). */
    public function previewPdf(Request $request)
    {
        // Read full input (NOT validate(), which returns only ruled keys).
        $sol   = (array) $request->input('solicitante', []);
        $viaje = (array) $request->input('viaje', []);
        $flujo = $request->input('flujo', 'comprobacion');

        if ($flujo === 'anticipo') {
            $desglose = (array) $request->input('desglose', []);
            // Preview reflects what will be sent: compute the monto from the desglose.
            $viaje['monto_anticipo'] = self::sumaDesglose($desglose) ?: (float) ($viaje['monto_anticipo'] ?? 0);
            return Pdf::loadView('pdf.anticipo', $this->buildAnticipoPdfData($sol, $viaje, $desglose))
                ->setPaper('letter')->stream('anticipo.pdf');
        }

        $data = $this->buildPdfData($sol, $viaje, (array) $request->input('gastos', []));
        return Pdf::loadView('pdf.detalle', $data)->setPaper('letter')->stream('detalle-gastos.pdf');
    }

    /** Phase 5 — persist the comprobación/anticipo, build the Smartker payload, stub the send. */
    public function enviar(Request $request): JsonResponse
    {
        $request->validate([
            'terminos' => ['accepted'],
            'flujo'    => ['required', 'in:comprobacion,anticipo'],
            'solicitante.numero_empleado' => ['required', 'string'],
        ], [], ['terminos' => 'aceptación de términos']);

        $flujo    = $request->input('flujo');
        $sol      = (array) $request->input('solicitante', []);
        $viaje    = (array) $request->input('viaje', []);
        $gastos   = (array) $request->input('gastos', []);
        $desglose = (array) $request->input('desglose', []);

        if ($flujo === 'comprobacion' && count($gastos) < 1) {
            return response()->json(['message' => 'Agrega al menos un gasto para comprobar.'], 422);
        }

        // Anticipo: the amount is the sum of the desglose (alimentos + pasajes +
        // peajes + each "otros" line). Computed server-side; never trust the client total.
        if ($flujo === 'anticipo') {
            $montoAnticipo = self::sumaDesglose($desglose);
            if (! ($montoAnticipo > 0)) {
                return response()->json(['message' => 'Captura al menos un monto en el desglose del anticipo.'], 422);
            }
            $viaje['monto_anticipo'] = $montoAnticipo;
            // Folio generated when the anticipo is registered; the comprobación later references it.
            $viaje['folio_anticipo'] = 'ANT-' . strtoupper(substr(str_replace('-', '', (string) Str::uuid()), 0, 10));
        }

        $resultados = DB::transaction(function () use ($flujo, $sol, $viaje, $gastos, $desglose) {
            if ($flujo === 'anticipo') {
                return [$this->procesarComprobacion('anticipo', $sol, $viaje, [], $desglose)];
            }

            // Comprobación: one document (PDF + póliza + submission) per selected card.
            $grupos = [];
            foreach ($gastos as $g) {
                $key = trim((string) ($g['tarjeta_numero'] ?? '')) ?: 'sin-tarjeta';
                $grupos[$key][] = $g;
            }

            $out = [];
            foreach ($grupos as $grupo) {
                $out[] = $this->procesarComprobacion('comprobacion', $sol, $viaje, $grupo);
            }

            return $out;
        });

        // Aggregate across the one-or-more comprobaciones created.
        $comps     = array_map(fn ($r) => $r['comprobacion'], $resultados);
        $first     = $resultados[0];
        $allSent   = count(array_filter($resultados, fn ($r) => $r['sent'])) === count($resultados);
        $anyLive   = (bool) array_filter($resultados, fn ($r) => $r['live']);
        $fileCount = array_sum(array_map(fn ($r) => (int) $r['payloadRecord']->file_count, $resultados));

        $lista = array_map(fn ($r) => [
            'folio'      => $r['comprobacion']->folio(),
            'tarjeta'    => trim((string) ($r['comprobacion']->tarjeta_nombre ?? '')
                . (($r['comprobacion']->tarjeta_numero ?? '') ? ' · ' . $r['comprobacion']->tarjeta_numero : '')) ?: 'Sin tarjeta',
            'total'      => (float) $r['comprobacion']->monto_comprobacion,
            'sent'       => $r['sent'],
            'file_count' => (int) $r['payloadRecord']->file_count,
            'payload_id' => $r['payloadRecord']->id,
        ], $resultados);

        return response()->json([
            'ok'              => true,
            'multiple'        => count($resultados) > 1,
            'count'           => count($resultados),
            'live'            => $anyLive,
            'sent'            => $allSent,
            'http_status'     => $first['httpStatus'],
            'comprobacion_id' => $first['comprobacion']->id,
            'folio'           => count($resultados) > 1
                ? count($resultados) . ' comprobaciones'
                : $first['comprobacion']->folio(),
            'payload_id'      => $first['payloadRecord']->id,
            'file_count'      => $fileCount,
            'tipo_solicitud'  => $flujo,
            'folio_anticipo'  => $flujo === 'anticipo' ? $first['comprobacion']->folio_anticipo : null,
            'resumen'         => [
                'con_factura' => array_sum(array_map(fn ($c) => (float) $c->total_con_factura, $comps)),
                'sin_factura' => array_sum(array_map(fn ($c) => (float) $c->total_sin_factura, $comps)),
                'iva'         => array_sum(array_map(fn ($c) => (float) $c->iva_total, $comps)),
                'total'       => array_sum(array_map(fn ($c) => (float) $c->monto_comprobacion, $comps)),
                'anticipo'    => (float) $first['comprobacion']->monto_anticipo,
            ],
            'comprobaciones'  => $lista,
            'message'         => (count($resultados) > 1 ? count($resultados) . ' comprobaciones generadas (una por tarjeta). ' : '')
                . ($anyLive
                    ? ($allSent ? 'Enviadas a Smartker.' : 'Guardadas; algún envío a Smartker no fue exitoso. Revisa el log del payload.')
                    : 'Guardadas. Payload construido (envío deshabilitado: SMARTKER_LIVE=false).'),
        ]);
    }

    /** Sum of the anticipo desglose: alimentos + pasajes + peajes + each "otros" line. */
    private static function sumaDesglose(array $d): float
    {
        $total = (float) ($d['alimentos'] ?? 0) + (float) ($d['pasajes'] ?? 0) + (float) ($d['peajes'] ?? 0);
        foreach ((array) ($d['otros'] ?? []) as $o) {
            $total += (float) ($o['monto'] ?? 0);
        }
        return round($total, 2);
    }

    /** Creates one Comprobación from a set of gastos (one card): persists, builds its PDF + Smartker submission. */
    private function procesarComprobacion(string $flujo, array $sol, array $viaje, array $gastos, array $desglose = []): array
    {
        // Server-side totals (comprobación only; never trust the client).
        $totCf = $totSf = $iva = 0.0;
        if ($flujo === 'comprobacion') {
            foreach ($gastos as $g) {
                $total = (float) ($g['total'] ?? 0);
                if (($g['tipo'] ?? 'sf') === 'cf') {
                    $totCf += $total;
                    $iva   += round($total - $total / 1.16, 2);
                } else {
                    $totSf += $total;
                }
            }
        }

        // Card for this document: the group's card (comprobación) or the viaje form (anticipo).
        $cardNombre = $flujo === 'comprobacion' ? ($gastos[0]['tarjeta_nombre'] ?? null) : ($viaje['tarjeta_nombre'] ?? null);
        $cardNumero = $flujo === 'comprobacion' ? ($gastos[0]['tarjeta_numero'] ?? null) : ($viaje['tarjeta_numero'] ?? null);

        $comprobacion = Comprobacion::create([
            'tipo_solicitud'     => $flujo,
            'numero_empleado'    => $sol['numero_empleado'] ?? '',
            'nombre_empleado'    => $sol['nombre_completo'] ?? '',
            'rfc'                => $sol['rfc'] ?? null,
            'departamento'       => $sol['departamento'] ?? null,
            'gerencia'           => $sol['gerencia'] ?? null,
            'direccion'          => $sol['direccion'] ?? null,
            'centro_costos'      => ($viaje['centro_costos'] ?? '') ?: ($sol['centro_costos'] ?? null),
            'clabe'              => ($viaje['clabe'] ?? '') ?: ($sol['clabe'] ?? null),
            'banco'              => ($viaje['banco'] ?? '') ?: ($sol['banco'] ?? null),
            'sucursal_cedis'     => ($viaje['sucursal'] ?? '') ?: ($sol['sucursal_cedis'] ?? null),
            'fecha_salida'       => $viaje['fecha_gasto_1'] ?? null,
            'fecha_regreso'      => $viaje['fecha_gasto_2'] ?? null,
            'dias'               => (int) ($viaje['dias'] ?? 0),
            'noches'             => (int) ($viaje['noches'] ?? 0),
            'folio_anticipo'     => $viaje['folio_anticipo'] ?? null,
            'monto_anticipo'     => (float) ($viaje['monto_anticipo'] ?? 0),
            'observaciones'      => $viaje['observaciones'] ?? null,
            'tipo_gasto'         => ($viaje['tipo_gasto'] ?? '') ?: 'Viaje',
            'justificacion'      => $viaje['observaciones'] ?? null,
            'tarjeta_nombre'     => $cardNombre,
            'tarjeta_numero'     => $cardNumero,
            // Anticipo hides moneda/referencia (defaults); comprobación captures them.
            'moneda'             => $flujo === 'anticipo' ? 'MXN' : (($viaje['moneda'] ?? '') ?: 'MXN'),
            'referencia'         => $flujo === 'anticipo' ? null : ($viaje['referencia'] ?? null),
            'fecha_dispersacion' => $viaje['fecha_dispersacion'] ?? null,
            'total_con_factura'  => $totCf,
            'total_sin_factura'  => $totSf,
            'iva_total'          => $iva,
            'monto_comprobacion' => $totCf + $totSf,
            'estatus'            => 'capturada',
        ]);

        $endpointName = $flujo === 'comprobacion' ? 'complemento' : 'anticipo';

        if ($flujo === 'comprobacion') {
            $filesByGastoId = [];
            $dir = 'comprobaciones/' . $comprobacion->id;

            foreach ($gastos as $g) {
                $tipo  = ($g['tipo'] ?? 'sf') === 'cf' ? 'cf' : 'sf';
                $total = (float) ($g['total'] ?? 0);
                $gImporte = $tipo === 'cf' ? round($total / 1.16, 2) : $total;
                $gIva     = $tipo === 'cf' ? round($total - $gImporte, 2) : 0.0;

                $xmlPath = $this->saveDataUri($g['xmlB64'] ?? null, $dir, ($g['xmlName'] ?? 'factura') . '');
                $pdfPath = $this->saveDataUri($g['pdfB64'] ?? null, $dir, ($g['pdfName'] ?? 'factura') . '');

                $gasto = $comprobacion->gastos()->create([
                    'tipo'          => $tipo,
                    'fecha'         => $g['fecha'] ?? null,
                    'categoria'     => $g['cat'] ?? null,
                    'compania'      => $g['co'] ?? null,
                    'rfc'           => $g['rfc'] ?? null,
                    'uuid'          => $g['uuid'] ?? null,
                    'importe'       => $gImporte,
                    'iva'           => $gIva,
                    'total'         => $total,
                    'justificacion' => $g['justificacion'] ?? null,
                    'xml_filename'  => $g['xmlName'] ?? null,
                    'xml_path'      => $xmlPath,
                    'pdf_filename'  => $g['pdfName'] ?? null,
                    'pdf_path'      => $pdfPath,
                ]);

                $filesByGastoId[$gasto->id] = [
                    'xml' => $g['xmlB64'] ?? null,
                    'pdf' => $g['pdfB64'] ?? null,
                ];
            }

            // Principal file = the generated Lyncott "COMPROBACION GASTOS" PDF (this card only).
            $pdfData  = $this->buildPdfData($sol, $viaje, $gastos, [
                'folio'      => $comprobacion->folio(),
                'docto_prv'  => 'XA8001-' . str_pad((string) $comprobacion->id, 7, '0', STR_PAD_LEFT),
                'poliza_num' => 'D ' . str_pad((string) $comprobacion->id, 4, '0', STR_PAD_LEFT),
            ]);
            $pdfBytes = Pdf::loadView('pdf.detalle', $pdfData)->setPaper('letter')->output();
            $principal = [
                'fileName'  => 'comprobacion-' . $comprobacion->folio() . '.pdf',
                'extension' => '.pdf',
                'base64'    => 'data:application/pdf;base64,' . base64_encode($pdfBytes),
            ];

            $built = SmartkerPayloadBuilder::build($comprobacion, 'comprobacion', $comprobacion->gastos, $filesByGastoId, $principal);
        } else {
            $pdfData  = $this->buildAnticipoPdfData($sol, $viaje, $desglose);
            $pdfBytes = Pdf::loadView('pdf.anticipo', $pdfData)->setPaper('letter')->output();
            $principal = [
                'fileName'  => 'anticipo-' . $comprobacion->folio() . '.pdf',
                'extension' => '.pdf',
                'base64'    => 'data:application/pdf;base64,' . base64_encode($pdfBytes),
            ];

            $built = SmartkerPayloadBuilder::build($comprobacion, 'anticipo', [], [], $principal);
        }

        $sanitized  = SmartkerPayloadBuilder::sanitize($built['payload']);
        $live       = (bool) config('smartker.live');
        $sent       = false;
        $response   = null;
        $httpStatus = null;

        Log::debug("Smartker {$endpointName} (" . ($live ? 'LIVE' : 'STUB') . ')', ['payload' => $sanitized]);

        if ($live) {
            try {
                $res        = SmartkerClient::send($built['payload'], $flujo);
                $httpStatus = $res['status'];
                $response   = $res['body'];
                $sent       = $httpStatus >= 200 && $httpStatus < 300;
                Log::info('Smartker response', [
                    'comprobacion' => $comprobacion->id,
                    'url'          => $res['url'],
                    'status'       => $httpStatus,
                    'body'         => $response,
                ]);
            } catch (\Throwable $e) {
                $response = 'ERROR: ' . $e->getMessage();
                Log::error('Smartker send failed', ['comprobacion' => $comprobacion->id, 'error' => $e->getMessage()]);
            }
        }

        $payloadRecord = $comprobacion->payloads()->create([
            'endpoint'    => $endpointName,
            'live'        => $live,
            'sent'        => $sent,
            'http_status' => $httpStatus,
            'file_count'  => $built['file_count'],
            'attributes'  => $sanitized,
            'response'    => $response,
        ]);

        $comprobacion->update(['estatus' => $sent ? 'enviada' : ($live ? 'error_envio' : 'capturada')]);

        return compact('comprobacion', 'payloadRecord', 'live', 'sent', 'httpStatus');
    }

    /**
     * Build the ERP-style comprobación document: Relación de Facturas + Póliza Contable.
     * $opts: ['folio' => ..., 'docto_prv' => ..., 'poliza_num' => ...] (optional).
     */
    private function buildPdfData(array $sol, array $viaje, array $gastos, array $opts = []): array
    {
        // Embed the Lyncott logo (red, original) as base64 so DomPDF can render it.
        $logoPath = public_path('img/logo.png');
        $logo = file_exists($logoPath)
            ? 'data:image/png;base64,' . base64_encode((string) file_get_contents($logoPath))
            : null;

        $p = \App\Services\PolizaBuilder::build($gastos);

        // "Comprobado por" / Referencia: the card when there's a single one, else the employee.
        $cards = array_values(array_unique(array_filter(array_map(
            fn ($g) => trim((string) ($g['tarjeta_nombre'] ?? '')),
            $gastos,
        ))));
        $comprobadoPor = count($cards) === 1 ? $cards[0] : ($sol['nombre_completo'] ?? '—');

        $moneda = match (strtoupper((string) ($viaje['moneda'] ?? 'MXN'))) {
            'USD' => 'DOLARES',
            'EUR' => 'EUROS',
            default => 'PESOS',
        };

        return [
            'logo'  => $logo,
            'money' => fn ($n) => number_format((float) $n, 2),
            'meta'  => [
                'empresa'        => 'LYNCOTT ALIMENTARIA S.A.P.I. DE C.V.',
                'titulo'         => 'COMPROBACION GASTOS',
                'sucursal'       => ($viaje['sucursal'] ?? '') ?: 'Planta - Corporativo',
                'documento'      => $opts['folio'] ?? 'BORRADOR',
                'fecha'          => Carbon::now('America/Mexico_City')->format('d-m-Y'),
                'moneda'         => $moneda,
                'docto_prv'      => $opts['docto_prv'] ?? '—',
                'referencia'     => $comprobadoPor,
                'fecha_pago'     => '',
                'comprobado_por' => $comprobadoPor,
                'poliza_num'     => $opts['poliza_num'] ?? '—',
                'empleado'       => $sol['nombre_completo'] ?? '—',
                'numero'         => $sol['numero_empleado'] ?? '—',
                'tipo_gasto'     => $viaje['tipo_gasto'] ?? '',
            ],
            'relacion'      => $p['relacion'],
            'importe_total' => $p['importe_total'],
            'poliza'        => $p['poliza'],
            'sumas'         => $p['sumas'],
        ];
    }

    /** Data for the anticipo summary PDF (incl. the desglose breakdown). */
    private function buildAnticipoPdfData(array $sol, array $viaje, array $desglose = []): array
    {
        $logoPath = public_path('img/logo.png');
        $logo = file_exists($logoPath)
            ? 'data:image/png;base64,' . base64_encode((string) file_get_contents($logoPath))
            : null;

        // Desglose lines for the PDF table: the 3 fixed buckets + each "otros" line.
        $lineas = [];
        foreach (['alimentos' => 'Alimentos', 'pasajes' => 'Pasajes', 'peajes' => 'Peajes'] as $k => $lbl) {
            $monto = (float) ($desglose[$k] ?? 0);
            if ($monto > 0) $lineas[] = ['concepto' => $lbl, 'monto' => $monto];
        }
        foreach ((array) ($desglose['otros'] ?? []) as $o) {
            $monto = (float) ($o['monto'] ?? 0);
            if ($monto > 0) {
                $desc = trim((string) ($o['descripcion'] ?? ''));
                $lineas[] = ['concepto' => 'Otros' . ($desc !== '' ? ' — ' . $desc : ''), 'monto' => $monto];
            }
        }

        return [
            'logo' => $logo,
            'money' => fn ($n) => number_format((float) $n, 2),
            'lineas' => $lineas,
            'm' => [
                'nombre'        => $sol['nombre_completo'] ?? '—',
                'numero'        => $sol['numero_empleado'] ?? '—',
                'departamento'  => $sol['departamento'] ?? '',
                'periodo'       => $this->periodo($viaje),
                'tipo_gasto'    => $viaje['tipo_gasto'] ?? '',
                'centro_costos' => $viaje['centro_costos'] ?? '',
                'clabe'         => $viaje['clabe'] ?? '',
                'destino'       => $viaje['sucursal'] ?? '',
                'fecha_disp'    => $viaje['fecha_dispersacion'] ?? '',
                'tarjeta'       => trim((string) ($viaje['tarjeta_nombre'] ?? '') . (($viaje['tarjeta_numero'] ?? '') ? ' · ' . $viaje['tarjeta_numero'] : '')),
                'observaciones' => $viaje['observaciones'] ?? '',
                'monto'         => '$' . number_format((float) ($viaje['monto_anticipo'] ?? 0), 2),
                'fecha'         => Carbon::now('America/Mexico_City')->translatedFormat('d/M/Y'),
            ],
        ];
    }

    /** "fecha1 → fecha2 (N días, M noches)" from the viaje range. */
    private function periodo(array $viaje): string
    {
        $p = ($viaje['fecha_gasto_1'] ?? '—') . ' → ' . ($viaje['fecha_gasto_2'] ?? '—');
        if (! empty($viaje['dias'])) {
            $p .= '  (' . $viaje['dias'] . ' días, ' . ($viaje['noches'] ?? 0) . ' noches)';
        }
        return $p;
    }

    private function centrosCosto(): array
    {
        return [
            'TI-001'  => 'TI-001 — Tecnologías de la Información',
            'COM-014' => 'COM-014 — Comercial',
            'OPS-007' => 'OPS-007 — Operaciones',
            'ABA-022' => 'ABA-022 — Abastecimiento',
            'LOG-003' => 'LOG-003 — Logística',
            'GEN-000' => 'GEN-000 — General',
        ];
    }

    /** Catálogo "Tipo de Gasto" (lista). */
    private function tiposGasto(string $flujo = 'comprobacion'): array
    {
        // Anticipo (solicitud) only offers Gastos de viaje; comprobación adds AMEX + Fondo Fijo.
        if ($flujo === 'anticipo') {
            return ['Gastos de viaje'];
        }

        return ['Gastos de viaje (Comprobación)', 'AMEX (Comprobación)', 'Fondo Fijo (Reembolso)'];
    }
}
