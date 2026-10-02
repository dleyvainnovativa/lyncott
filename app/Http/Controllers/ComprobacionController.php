<?php

namespace App\Http\Controllers;

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
            'categorias' => config('smartker.categorias', []),
            'centros'    => $this->centrosCosto(),
            'medios'     => $this->mediosTransporte(),
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

    /** Phase 5 — render the Lyncott "DETALLE DE GASTOS" PDF for the review preview. */
    public function previewPdf(Request $request)
    {
        $payload = $request->validate([
            'solicitante'       => ['array'],
            'viaje'             => ['array'],
            'gastos'            => ['array'],
            'gastos.*.tipo'     => ['nullable', 'string'],
            'gastos.*.total'    => ['nullable', 'numeric'],
        ]);

        $data = $this->buildPdfData(
            $payload['solicitante'] ?? [],
            $payload['viaje'] ?? [],
            $payload['gastos'] ?? []
        );

        return Pdf::loadView('pdf.detalle', $data)
            ->setPaper('letter')
            ->stream('detalle-gastos.pdf');
    }

    /** Phase 5 — persist the comprobación, build the Smartker payload, stub the send. */
    public function enviar(Request $request): JsonResponse
    {
        $data = $request->validate([
            'terminos'          => ['accepted'],
            'solicitante'       => ['required', 'array'],
            'solicitante.numero_empleado' => ['required', 'string'],
            'viaje'             => ['required', 'array'],
            'gastos'            => ['required', 'array', 'min:1'],
        ], [], ['terminos' => 'aceptación de términos']);

        $sol    = $data['solicitante'];
        $viaje  = $data['viaje'];
        $gastos = $data['gastos'];

        $result = DB::transaction(function () use ($sol, $viaje, $gastos) {
            // Server-side totals (never trust the client).
            $totCf = $totSf = $iva = 0.0;
            foreach ($gastos as $g) {
                $total = (float) ($g['total'] ?? 0);
                if (($g['tipo'] ?? 'sf') === 'cf') {
                    $totCf += $total;
                    $iva   += round($total - $total / 1.16, 2);
                } else {
                    $totSf += $total;
                }
            }

            $comprobacion = Comprobacion::create([
                'numero_empleado'    => $sol['numero_empleado'] ?? '',
                'nombre_empleado'    => $sol['nombre_completo'] ?? '',
                'rfc'                => $sol['rfc'] ?? null,
                'departamento'       => $sol['departamento'] ?? null,
                'gerencia'           => $sol['gerencia'] ?? null,
                'direccion'          => $sol['direccion'] ?? null,
                'centro_costos'      => $viaje['centro_costos'] ?? ($sol['centro_costos'] ?? null),
                'clabe'              => $sol['clabe'] ?? null,
                'banco'              => $sol['banco'] ?? null,
                'sucursal_cedis'     => $sol['sucursal_cedis'] ?? null,
                'fecha_salida'       => $viaje['fecha_salida'] ?? null,
                'fecha_regreso'      => $viaje['fecha_regreso'] ?? null,
                'dias'               => (int) ($viaje['dias'] ?? 0),
                'noches'             => (int) ($viaje['noches'] ?? 0),
                'origen'             => $viaje['origen'] ?? null,
                'destino'            => $viaje['destino'] ?? null,
                'folio_anticipo'     => $viaje['folio_anticipo'] ?? null,
                'monto_anticipo'     => (float) ($viaje['monto_anticipo'] ?? 0),
                'medio_transporte'   => $viaje['medio_transporte'] ?? null,
                'observaciones'      => $viaje['observaciones'] ?? null,
                'tipo_gasto'         => 'Viaje',
                'justificacion'      => $viaje['observaciones'] ?? null,
                'total_con_factura'  => $totCf,
                'total_sin_factura'  => $totSf,
                'iva_total'          => $iva,
                'monto_comprobacion' => $totCf + $totSf,
                'estatus'            => 'capturada',
            ]);

            // Persist gastos + decode files to storage; keep base64 for payload.
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

            // Build the Smartker payload from the config field map.
            $built      = SmartkerPayloadBuilder::build($comprobacion, $comprobacion->gastos, $filesByGastoId);
            $sanitized  = SmartkerPayloadBuilder::sanitize($built['attributes']);
            $live       = (bool) config('smartker.live');
            $sent       = false;
            $response   = null;

            // Log mirrors the client's controller (base64 omitted).
            Log::debug('Smartker complemento (' . ($live ? 'LIVE' : 'STUB') . ')', $sanitized);

            if ($live) {
                try {
                    $response = SmartkerClient::complemento($built['attributes']);
                    $sent = true;
                } catch (\Throwable $e) {
                    $response = 'ERROR: ' . $e->getMessage();
                    Log::error('Smartker send failed', ['comprobacion' => $comprobacion->id, 'error' => $e->getMessage()]);
                }
            }

            $payloadRecord = $comprobacion->payloads()->create([
                'endpoint'   => 'complemento',
                'live'       => $live,
                'sent'       => $sent,
                'file_count' => $built['file_count'],
                'attributes' => $sanitized,
                'response'   => $response,
            ]);

            if ($sent) {
                $comprobacion->update(['estatus' => 'enviada']);
            }

            return compact('comprobacion', 'payloadRecord', 'live', 'sent');
        });

        /** @var Comprobacion $c */
        $c = $result['comprobacion'];

        return response()->json([
            'ok'            => true,
            'live'          => $result['live'],
            'sent'          => $result['sent'],
            'comprobacion_id' => $c->id,
            'folio'         => $c->folio(),
            'payload_id'    => $result['payloadRecord']->id,
            'file_count'    => $result['payloadRecord']->file_count,
            'resumen'       => [
                'con_factura' => (float) $c->total_con_factura,
                'sin_factura' => (float) $c->total_sin_factura,
                'iva'         => (float) $c->iva_total,
                'total'       => (float) $c->monto_comprobacion,
            ],
            'message'       => $result['live']
                ? ($result['sent'] ? 'Comprobación enviada a Smartker.' : 'Comprobación guardada; el envío a Smartker falló (ver logs).')
                : 'Comprobación guardada. Payload construido y almacenado (envío deshabilitado en modo demo).',
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

        $periodo = ($viaje['fecha_salida'] ?? '—') . ' → ' . ($viaje['fecha_regreso'] ?? '—');
        if (! empty($viaje['dias'])) {
            $periodo .= '  (' . $viaje['dias'] . ' días, ' . ($viaje['noches'] ?? 0) . ' noches)';
        }

        return [
            'logo' => $logo,
            'm' => [
                'nombre'        => $sol['nombre_completo'] ?? '—',
                'numero'        => $sol['numero_empleado'] ?? '—',
                'departamento'  => $sol['departamento'] ?? '',
                'centro_costos' => $viaje['centro_costos'] ?? ($sol['centro_costos'] ?? ''),
                'periodo'       => $periodo,
                'ruta'          => trim(($viaje['origen'] ?? '—') . ' → ' . ($viaje['destino'] ?? '—')),
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

    private function mediosTransporte(): array
    {
        return ['Avión', 'Autobús', 'Automóvil propio', 'Automóvil rentado', 'Tren', 'Otro'];
    }
}
