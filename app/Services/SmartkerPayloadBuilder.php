<?php

namespace App\Services;

use App\Models\Comprobacion;
use Illuminate\Support\Carbon;

/**
 * Builds the Smartker payload in its real shape:
 *
 *   [ { "attributes": [ ...shared..., {fieldId:13, tableAttributes:[...]}, {fieldId:14} ],
 *       "files": [ { fileName, extension, isPrincipal, base64 } ] } ]
 *
 * - Shared attributes come from config('smartker.fields.shared') (ids 1-8).
 * - The gastos table is config fieldId 13, with one set of cells per row
 *   (row starts at 1): categoría(9), PDF(10,file), XML(11,file),
 *   justificación(12), cantidad(15), importe(16).
 * - Grand total is config fieldId 14.
 * - The principal document goes in `files`.
 *
 * All ids live in config/smartker.php. Nothing here sends — see SmartkerClient.
 */
class SmartkerPayloadBuilder
{
    /**
     * @param  iterable   $gastos          Gasto models (ordered).
     * @param  array      $filesByGastoId  [gastoId => ['xml'=>dataUri|null, 'pdf'=>dataUri|null]]
     * @param  array|null $principalFile   ['fileName'=>, 'extension'=>, 'base64'=>dataUri]
     * @return array{payload: array, file_count: int}
     */
    public static function build(Comprobacion $c, iterable $gastos, array $filesByGastoId = [], ?array $principalFile = null): array
    {
        $fields = config('smartker.fields');
        $types  = config('smartker.field_types');

        $attributes = self::sharedAttributes($c, $fields);

        // Table (fieldId 13) — flatten every row's cells into one tableAttributes array.
        $cols = $fields['tabla']['columns'];
        $cells = [];
        $fileCount = 0;
        $totalImporte = 0.0;
        $row = 0;

        foreach ($gastos as $g) {
            $row++;
            $files = $filesByGastoId[$g->id] ?? [];
            $pdf = $files['pdf'] ?? '';
            $xml = $files['xml'] ?? '';
            if ($pdf) $fileCount++;
            if ($xml) $fileCount++;
            $totalImporte += (float) $g->total;

            $cells[] = self::cell($cols['categoria'], $row, $g->categoria ?? '', $types);
            $cells[] = self::cell($cols['pdf'], $row, $pdf, $types);
            $cells[] = self::cell($cols['xml'], $row, $xml, $types);
            $cells[] = self::cell($cols['justificacion'], $row, $g->justificacion ?? '', $types);
            $cells[] = self::cell($cols['cantidad'], $row, '1', $types);
            $cells[] = self::cell($cols['importe'], $row, self::num($g->total), $types);
        }

        $attributes[] = [
            'fieldId'         => $fields['tabla']['fieldId'],
            'value'           => '',
            'tableAttributes' => $cells,
        ];

        // Grand total (fieldId 14).
        $attributes[] = self::attr($fields['monto_total'], self::num($totalImporte ?: $c->monto_comprobacion));

        $files = [];
        if ($principalFile) { $files[] = self::principal($principalFile); $fileCount++; }

        // The /submit endpoint expects a single JSON OBJECT (SubmitWebformInputDto),
        // NOT an array wrapper — sending [ {...} ] returns HTTP 400 "Cannot
        // deserialize the current JSON array ... requires a JSON object".
        return [
            'payload'    => ['attributes' => $attributes, 'files' => $files],
            'file_count' => $fileCount,
        ];
    }

    /**
     * Anticipo payload: shared attributes + grand total (monto del anticipo).
     * No gastos table.
     *
     * @return array{payload: array, file_count: int}
     */
    public static function buildAnticipo(Comprobacion $c, ?array $principalFile = null): array
    {
        $fields = config('smartker.fields');
        $attributes = self::sharedAttributes($c, $fields);
        $attributes[] = self::attr($fields['monto_total'], self::num($c->monto_anticipo));

        $files = [];
        $fileCount = 0;
        if ($principalFile) { $files[] = self::principal($principalFile); $fileCount++; }

        return [
            'payload'    => ['attributes' => $attributes, 'files' => $files],
            'file_count' => $fileCount,
        ];
    }

    /** Deep copy of the payload with every base64 data-URI replaced (for storage + logs). */
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

    private static function sharedAttributes(Comprobacion $c, array $fields): array
    {
        $shared = [
            'nombre_empleado' => $c->nombre_empleado,
            'numero_empleado' => $c->numero_empleado,
            // fieldId 3 = fecha del gasto 1 (start of the range), fallback to today.
            'fecha_solicitud' => $c->fecha_salida?->toDateString() ?: Carbon::now('America/Mexico_City')->toDateString(),
            'departamento'    => $c->departamento,
            'centro_costos'   => $c->centro_costos,
            'clabe'           => $c->clabe,
            'sucursal_cedis'  => $c->sucursal_cedis,
            'banco'           => $c->banco,
        ];
        $out = [];
        foreach ($fields['shared'] as $name => $fieldId) {
            $out[] = self::attr($fieldId, $shared[$name] ?? '');
        }
        return $out;
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
