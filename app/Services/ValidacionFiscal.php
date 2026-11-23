<?php

namespace App\Services;

/**
 * Normalizes the Venvers ValidacionFiscal response into a compact shape the
 * front end can render: an overall status, a duplicate flag, the human
 * message, and the per-check matrix joined with the glossary.
 */
class ValidacionFiscal
{
    public static function normalize(array $json): array
    {
        $data  = $json['data'] ?? [];
        $glos  = (array) config('validaciones.checks', []);
        $codes = (array) config('validaciones.codigos', []);

        $matriz    = $data['matrizValidaciones'] ?? [];
        $checks    = [];
        $fallidas  = [];
        $duplicado = false;

        foreach ($matriz as $m) {
            $name = (string) ($m['validacion'] ?? '');
            $res  = (int) ($m['resultado'] ?? -1);

            if ($name === 'Comprobante nuevo' && $res === 0) {
                $duplicado = true;
            }
            if ($res === 0 && $name !== 'Comprobante nuevo') {
                $fallidas[] = $name;
            }

            $checks[] = [
                'validacion'  => $name,
                'resultado'   => $res,
                'estado'      => $res === 1 ? 'ok' : ($res === 0 ? 'fail' : 'na'),
                'codigo'      => $codes[$res]['label'] ?? '',
                'explicacion' => $glos[$name] ?? '',
            ];
        }

        $esValido = (bool) ($data['esValido'] ?? false);
        $mensaje  = (string) ($data['mensaje'] ?? '');
        $mensaje  = trim(strip_tags(str_ireplace(['<br>', '<br/>', '<br />'], ' · ', $mensaje)));

        return [
            'ok'        => true,
            'esValido'  => $esValido,
            'status'    => $esValido ? 'valido' : 'invalido',
            'duplicado' => $duplicado,
            'mensaje'   => $mensaje,
            'receptor'  => $data['receptor'] ?? '',
            'version'   => $data['version'] ?? '',
            'fallidas'  => $fallidas,
            'checks'    => $checks,
        ];
    }
}
