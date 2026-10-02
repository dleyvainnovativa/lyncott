<?php

namespace App\Services;

use App\Models\Comprobacion;
use Illuminate\Support\Carbon;

/**
 * Builds the Smartker "complemento" attributes array for a comprobación:
 *
 *   - shared attributes (one per field, fieldIds from config)
 *   - the whole gastos table as ONE JSON attribute (metadata only)
 *   - per con-factura gasto: an XML + optional PDF file attribute
 *
 * All fieldIds come from config/smartker.php (currently DUMMY numbers).
 * Nothing here sends anything — see SmartkerClient for the live call.
 */
class SmartkerPayloadBuilder
{
    /**
     * @param  iterable  $gastos          Gasto models (ordered).
     * @param  array     $filesByGastoId  [gastoId => ['xml' => dataUri|null, 'pdf' => dataUri|null]]
     * @return array{attributes: array, file_count: int}
     */
    public static function build(Comprobacion $c, iterable $gastos, array $filesByGastoId = []): array
    {
        $fields = config('smartker.fields');
        $attributes = [];

        // 1) Shared attributes.
        $shared = [
            'numero_empleado'    => $c->numero_empleado,
            'nombre_empleado'    => $c->nombre_empleado,
            'fecha_solicitud'    => Carbon::now('America/Mexico_City')->toDateString(),
            'departamento'       => $c->departamento,
            'centro_costos'      => $c->centro_costos,
            'clabe'              => $c->clabe,
            'sucursal_cedis'     => $c->sucursal_cedis,
            'banco'              => $c->banco,
            'tipo_gasto'         => $c->tipo_gasto,
            'justificacion'      => $c->justificacion,
            'monto_comprobacion' => number_format((float) $c->monto_comprobacion, 2, '.', ''),
            'folio_anticipo'     => $c->folio_anticipo,
        ];
        foreach ($fields['shared'] as $name => $fieldId) {
            $attributes[] = self::attr($fieldId, $shared[$name] ?? '');
        }

        // 2) Gastos table as one JSON attribute + build file attributes in the same pass.
        $rows = [];
        $fileAttrs = [];
        $base = $fields['files'];
        $cfIndex = 0;

        foreach ($gastos as $g) {
            $ref = null;
            if ($g->tipo === 'cf') {
                $ref = $cfIndex;
                $files = $filesByGastoId[$g->id] ?? [];
                if (! empty($files['xml'])) {
                    $fileAttrs[] = self::attr($base['base_xml'] + $cfIndex * $base['stride'], $files['xml']);
                }
                if (! empty($files['pdf'])) {
                    $fileAttrs[] = self::attr($base['base_pdf'] + $cfIndex * $base['stride'], $files['pdf']);
                }
                $cfIndex++;
            }

            $rows[] = [
                'ref'           => $ref,                       // links to its file pair (con-factura only)
                'tipo'          => $g->tipo,
                'categoria'     => $g->categoria,
                'fecha'         => optional($g->fecha)->toDateString(),
                'compania'      => $g->compania,
                'rfc'           => $g->rfc,
                'uuid'          => $g->uuid,
                'importe'       => (float) $g->importe,
                'iva'           => (float) $g->iva,
                'total'         => (float) $g->total,
                'justificacion' => $g->justificacion,
            ];
        }

        $attributes[] = self::attr($fields['tabla_gastos'], json_encode($rows, JSON_UNESCAPED_UNICODE));

        // 3) Append the file attributes after the table.
        foreach ($fileAttrs as $fa) {
            $attributes[] = $fa;
        }

        return [
            'attributes' => $attributes,
            'file_count' => count($fileAttrs),
        ];
    }

    /**
     * Anticipo payload: shared attributes + "Monto de la solicitud".
     * No gastos table, no files.
     *
     * @return array{attributes: array, file_count: int}
     */
    public static function buildAnticipo(Comprobacion $c): array
    {
        $fields = config('smartker.fields');
        $attributes = [];

        $shared = [
            'numero_empleado'    => $c->numero_empleado,
            'nombre_empleado'    => $c->nombre_empleado,
            'fecha_solicitud'    => Carbon::now('America/Mexico_City')->toDateString(),
            'departamento'       => $c->departamento,
            'centro_costos'      => $c->centro_costos,
            'clabe'              => $c->clabe,
            'sucursal_cedis'     => $c->sucursal_cedis,
            'banco'              => $c->banco,
            'tipo_gasto'         => $c->tipo_gasto,
            'justificacion'      => $c->justificacion,
            'monto_comprobacion' => '',               // n/a for anticipo
            'folio_anticipo'     => '',
        ];
        foreach ($fields['shared'] as $name => $fieldId) {
            $attributes[] = self::attr($fieldId, $shared[$name] ?? '');
        }

        // Monto de la solicitud.
        $attributes[] = self::attr(
            $fields['anticipo']['monto_solicitud'],
            number_format((float) $c->monto_anticipo, 2, '.', '')
        );

        return ['attributes' => $attributes, 'file_count' => 0];
    }

    /** Replace base64 data-URI values with a marker (for storage + logs). */
    public static function sanitize(array $attributes): array
    {
        return array_map(function ($attr) {
            if (isset($attr['value']) && is_string($attr['value']) && str_starts_with($attr['value'], 'data:')) {
                $attr['value'] = '[BASE64_FILE_OMITTED]';
            }
            return $attr;
        }, $attributes);
    }

    private static function attr(int $fieldId, mixed $value): array
    {
        return [
            'fieldId'         => $fieldId,
            'value'           => (string) ($value ?? ''),
            'tableAttributes' => [],
        ];
    }
}
