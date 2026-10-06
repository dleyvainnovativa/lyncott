<?php

namespace App\Services;

use App\Models\Comprobacion;

/**
 * Builds the Smartker payload as a single JSON object { attributes, files }.
 *
 * Field ids come from config('smartker.fields.<flujo>'), one map per flow:
 *   - comprobación: numero/nombre/desde/hasta/folio/tipo/observaciones/depto/
 *     centro/clabe/sucursal/banco + the gastos table (fieldId 13) + monto (14).
 *   - anticipo: same identity fields (ids 18-29) + monto, NO table.
 *
 * The map's key order is the emission order. 'tabla' (comprobación only)
 * expands to the fieldId-13 attribute built from the gastos rows.
 */
class SmartkerPayloadBuilder
{
    /**
     * @param  iterable   $gastos          Gasto models (comprobación only).
     * @param  array      $filesByGastoId  [gastoId => ['xml'=>dataUri|null, 'pdf'=>dataUri|null]]
     * @param  array|null $principalFile   ['fileName'=>, 'extension'=>, 'base64'=>dataUri]
     * @return array{payload: array, file_count: int}
     */
    public static function build(Comprobacion $c, string $flujo, iterable $gastos = [], array $filesByGastoId = [], ?array $principalFile = null): array
    {
        $map    = config("smartker.fields.{$flujo}", []);
        $values = self::values($c, $flujo);

        $attributes = [];
        $fileCount  = 0;

        foreach ($map as $name => $fieldId) {
            if ($name === 'tabla') {
                [$tableAttr, $cnt] = self::tableAttribute($gastos, $filesByGastoId);
                $attributes[] = $tableAttr;
                $fileCount += $cnt;
                continue;
            }
            $attributes[] = self::attr($fieldId, $values[$name] ?? '');
        }

        $files = [];
        if ($principalFile) { $files[] = self::principal($principalFile); $fileCount++; }

        return [
            'payload'    => ['attributes' => $attributes, 'files' => $files],
            'file_count' => $fileCount,
        ];
    }

    /** Deep copy of the payload with every base64 data-URI replaced (storage + logs). */
    public static function sanitize(array $payload): array
    {
        array_walk_recursive($payload, function (&$v) {
            if (is_string($v) && str_starts_with($v, 'data:')) {
                $v = '[BASE64_FILE_OMITTED]';
            }
        });
        return $payload;
    }

    /* ----------------------------------------------------------------- helpers */

    /** Logical name => value, for both flows. */
    private static function values(Comprobacion $c, string $flujo): array
    {
        return [
            'numero_empleado' => $c->numero_empleado,
            'nombre_empleado' => $c->nombre_empleado,
            'desde'           => $c->fecha_salida?->toDateString() ?? '',
            'hasta'           => $c->fecha_regreso?->toDateString() ?? '',
            'folio_anticipo'  => $c->folio_anticipo,
            'tipo_gasto'      => $c->tipo_gasto,
            'observaciones'   => $c->observaciones,
            'departamento'    => $c->departamento,
            'centro_costos'   => $c->centro_costos,
            'clabe'           => $c->clabe,
            'sucursal'        => $c->sucursal_cedis,
            'banco'           => $c->banco,
            'monto'           => self::num($flujo === 'anticipo' ? $c->monto_anticipo : $c->monto_comprobacion),
        ];
    }

    /** Build the fieldId-13 table attribute from gastos. Returns [attr, fileCount]. */
    private static function tableAttribute(iterable $gastos, array $filesByGastoId): array
    {
        $tabla = config('smartker.tabla');
        $types = config('smartker.field_types');
        $cols  = $tabla['columns'];

        $cells = [];
        $fileCount = 0;
        $row = 0;

        foreach ($gastos as $g) {
            $row++;
            $files = $filesByGastoId[$g->id] ?? [];
            $pdf = $files['pdf'] ?? '';
            $xml = $files['xml'] ?? '';
            if ($pdf) $fileCount++;
            if ($xml) $fileCount++;

            $cells[] = self::cell($cols['categoria'], $row, $g->categoria ?? '', $types);
            $cells[] = self::cell($cols['pdf'], $row, $pdf, $types);
            $cells[] = self::cell($cols['xml'], $row, $xml, $types);
            $cells[] = self::cell($cols['justificacion'], $row, $g->justificacion ?? '', $types);
            $cells[] = self::cell($cols['cantidad'], $row, '1', $types);
            $cells[] = self::cell($cols['importe'], $row, self::num($g->total), $types);
        }

        return [[
            'fieldId'         => $tabla['fieldId'],
            'value'           => '',
            'tableAttributes' => $cells,
        ], $fileCount];
    }

    private static function attr(int $fieldId, mixed $value): array
    {
        return ['fieldId' => $fieldId, 'value' => (string) ($value ?? ''), 'tableAttributes' => []];
    }

    private static function cell(array $col, int $row, mixed $value, array $types): array
    {
        return [
            'fieldColumnId'   => $col['id'],
            'fieldId'         => $col['id'],
            'row'             => $row,
            'fieldTypeId'     => $types[$col['type']] ?? 1,
            'value'           => (string) ($value ?? ''),
            'tableAttributes' => null,
        ];
    }

    private static function principal(array $f): array
    {
        return [
            'fileName'    => $f['fileName'] ?? 'documento.pdf',
            'extension'   => $f['extension'] ?? '.pdf',
            'isPrincipal' => true,
            'base64'      => $f['base64'] ?? '',
        ];
    }

    private static function num(mixed $n): string
    {
        return number_format((float) $n, 2, '.', '');
    }
}
