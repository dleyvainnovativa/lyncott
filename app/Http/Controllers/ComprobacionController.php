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
use Illuminate\View\View;

class ComprobacionController extends Controller
{
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
            'rfc'             => ['required', 'string', 'max:13'],
        ], [], [
            'numero_empleado' => 'número de empleado',
            'rfc'             => 'RFC',
        ]);

        $empleado = Empleado::query()
            ->where('numero_empleado', $data['numero_empleado'])
            ->whereRaw('UPPER(rfc) = ?', [mb_strtoupper($data['rfc'])])
            ->first();

        if (! $empleado) {
            return response()->json([
                'message' => 'No encontramos un empleado con ese número y RFC. Verifica los datos.',
            ], 404);
        }

        return response()->json(['empleado' => $empleado->only(Empleado::PUBLIC_FIELDS)]);
    }

    /** Phase 4 — (optional) parse a CFDI 4.0 XML server-side. Front parses client-side. */
    public function parseCfdi(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'El parseo de CFDI se realiza en el navegador en esta versión.',
        ], 501);
    }

    /** Phase 5 — render the review-preview PDF (detalle for comprobación, summary for anticipo). */
    public function previewPdf(Request $request)
    {
        // Read full input (NOT validate(), which returns only ruled keys).
        $sol   = (array) $request->input('solicitante', []);
        $viaje = (array) $request->input('viaje', []);
        $flujo = $request->input('flujo', 'comprobacion');

        if ($flujo === 'anticipo') {
            return Pdf::loadView('pdf.anticipo', $this->buildAnticipoPdfData($sol, $viaje))
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

        $flujo  = $request->input('flujo');
        $sol    = (array) $request->input('solicitante', []);
        $viaje  = (array) $request->input('viaje', []);
        $gastos = (array) $request->input('gastos', []);

        if ($flujo === 'comprobacion' && count($gastos) < 1) {
            return response()->json(['message' => 'Agrega al menos un gasto para comprobar.'], 422);
        }
        if ($flujo === 'anticipo' && ! ((float) ($viaje['monto_anticipo'] ?? 0) > 0)) {
            return response()->json(['message' => 'Captura el monto del anticipo.'], 422);
        }

        $result = DB::transaction(function () use ($flujo, $sol, $viaje, $gastos) {
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

            $comprobacion = Comprobacion::create([
                'tipo_solicitud'     => $flujo,
                'numero_empleado'    => $sol['numero_empleado'] ?? '',
                'nombre_empleado'    => $sol['nombre_completo'] ?? '',
                'rfc'                => $sol['rfc'] ?? null,
                'departamento'       => $sol['departamento'] ?? null,
                'gerencia'           => $sol['gerencia'] ?? null,
                'direccion'          => $sol['direccion'] ?? null,
                // Pago/clasificación: from the viaje form, fallback to the employee record.
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
                'total_con_factura'  => $totCf,
                'total_sin_factura'  => $totSf,
                'iva_total'          => $iva,
                'monto_comprobacion' => $totCf + $totSf,
                'estatus'            => 'capturada',
            ]);

            // Comprobación: persist gastos + decode files; Anticipo: no gastos.
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

                // Principal file = the generated Lyncott "DETALLE DE GASTOS" PDF.
                $pdfData  = $this->buildPdfData($sol, $viaje, $gastos);
                $pdfBytes = Pdf::loadView('pdf.detalle', $pdfData)->setPaper('letter')->output();
                $principal = [
                    'fileName'  => 'comprobacion-' . $comprobacion->folio() . '.pdf',
                    'extension' => '.pdf',
                    'base64'    => 'data:application/pdf;base64,' . base64_encode($pdfBytes),
                ];

                $built = SmartkerPayloadBuilder::build($comprobacion, 'comprobacion', $comprobacion->gastos, $filesByGastoId, $principal);
            } else {
                // Principal file = the generated "SOLICITUD DE ANTICIPO" summary PDF.
                $pdfData  = $this->buildAnticipoPdfData($sol, $viaje);
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

            // Log the sanitized payload (base64 omitted).
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
        });

        /** @var Comprobacion $c */
        $c = $result['comprobacion'];

        return response()->json([
            'ok'            => true,
            'live'          => $result['live'],
            'sent'          => $result['sent'],
            'http_status'   => $result['httpStatus'],
            'comprobacion_id' => $c->id,
            'folio'         => $c->folio(),
            'payload_id'    => $result['payloadRecord']->id,
            'file_count'    => $result['payloadRecord']->file_count,
            'tipo_solicitud' => $c->tipo_solicitud,
            'resumen'       => [
                'con_factura' => (float) $c->total_con_factura,
                'sin_factura' => (float) $c->total_sin_factura,
                'iva'         => (float) $c->iva_total,
                'total'       => (float) $c->monto_comprobacion,
                'anticipo'    => (float) $c->monto_anticipo,
            ],
            'message'       => $result['live']
                ? ($result['sent']
                    ? 'Enviada a Smartker (HTTP ' . $result['httpStatus'] . ').'
                    : 'Guardada; el envío a Smartker no fue exitoso (HTTP ' . ($result['httpStatus'] ?? 'sin respuesta') . '). Revisa el log del payload.')
                : 'Guardada. Payload construido y almacenado (envío deshabilitado: SMARTKER_LIVE=false).',
        ]);
    }

    /* ---------------------------------------------------------------- helpers */

    /** Decode a base64 data URI to the local storage disk. Returns the path or null. */
    private function saveDataUri(?string $dataUri, string $dir, string $baseName): ?string
    {
        if (! $dataUri || ! str_contains($dataUri, ',')) {
            return null;
        }
        [$meta, $b64] = explode(',', $dataUri, 2);
        $bytes = base64_decode($b64, true);
        if ($bytes === false) {
            return null;
        }
        $ext  = str_contains($meta, 'pdf') ? 'pdf' : (str_contains($meta, 'xml') ? 'xml' : 'bin');
        $name = pathinfo($baseName, PATHINFO_FILENAME) . '-' . uniqid() . '.' . $ext;
        $path = $dir . '/' . $name;
        Storage::disk('local')->put($path, $bytes);

        return $path;
    }

    /** Group gastos by categoría and format for the PDF view. */
    private function buildPdfData(array $sol, array $viaje, array $gastos): array
    {
        $fmt = fn ($n) => '$' . number_format((float) $n, 2);

        // Embed the Lyncott logo (red, original) as base64 so DomPDF can render it.
        $logoPath = public_path('img/logo.png');
        $logo = file_exists($logoPath)
            ? 'data:image/png;base64,' . base64_encode((string) file_get_contents($logoPath))
            : null;

        $grupos = [];
        $totImporte = $totIva = $totTotal = 0.0;

        foreach ($gastos as $g) {
            $cat   = $g['cat'] ?? 'SIN CATEGORÍA';
            $tipo  = ($g['tipo'] ?? 'sf') === 'cf' ? 'cf' : 'sf';
            $total = (float) ($g['total'] ?? 0);
            $importe = $tipo === 'cf' ? round($total / 1.16, 2) : $total;
            $iva     = $tipo === 'cf' ? round($total - $importe, 2) : 0.0;

            $grupos[$cat] ??= ['categoria' => $cat, 'rows' => [], 'subtotal_n' => 0.0];
            $grupos[$cat]['rows'][] = [
                'fecha'    => $g['fecha'] ?? '—',
                'nodoc'    => $tipo === 'cf' ? (substr((string) ($g['uuid'] ?? ''), 0, 8) ?: '—') : 'S/F',
                'concepto' => $g['co'] ?? '',
                'rfc'      => $g['rfc'] ?? '—',
                'importe'  => $fmt($importe),
                'iva'      => $fmt($iva),
                'total'    => $fmt($total),
            ];
            $grupos[$cat]['subtotal_n'] += $total;
            $totImporte += $importe;
            $totIva     += $iva;
            $totTotal   += $total;
        }

        foreach ($grupos as &$grupo) {
            $grupo['subtotal'] = $fmt($grupo['subtotal_n']);
        }
        unset($grupo);

        return [
            'logo' => $logo,
            'm' => [
                'nombre'        => $sol['nombre_completo'] ?? '—',
                'numero'        => $sol['numero_empleado'] ?? '—',
                'departamento'  => $sol['departamento'] ?? '',
                'centro_costos' => $viaje['centro_costos'] ?? ($sol['centro_costos'] ?? ''),
                'periodo'       => $this->periodo($viaje),
                'folio_anticipo'=> $viaje['folio_anticipo'] ?? '',
                'fecha'         => Carbon::now('America/Mexico_City')->translatedFormat('d/M/Y'),
            ],
            'grupos' => array_values($grupos),
            'tot' => [
                'importe' => $fmt($totImporte),
                'iva'     => $fmt($totIva),
                'total'   => $fmt($totTotal),
            ],
        ];
    }

    /** Data for the anticipo summary PDF. */
    private function buildAnticipoPdfData(array $sol, array $viaje): array
    {
        $logoPath = public_path('img/logo.png');
        $logo = file_exists($logoPath)
            ? 'data:image/png;base64,' . base64_encode((string) file_get_contents($logoPath))
            : null;

        return [
            'logo' => $logo,
            'm' => [
                'nombre'        => $sol['nombre_completo'] ?? '—',
                'numero'        => $sol['numero_empleado'] ?? '—',
                'departamento'  => $sol['departamento'] ?? '',
                'periodo'       => $this->periodo($viaje),
                'tipo_gasto'    => $viaje['tipo_gasto'] ?? '',
                'centro_costos' => $viaje['centro_costos'] ?? '',
                'banco'         => $viaje['banco'] ?? '',
                'clabe'         => $viaje['clabe'] ?? '',
                'sucursal'      => $viaje['sucursal'] ?? '',
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
    private function tiposGasto(): array
    {
        return ['Viaje', 'Compras', 'Representación', 'Transporte', 'Hospedaje', 'Alimentos', 'Otros'];
    }
}
