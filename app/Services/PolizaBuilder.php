<?php

namespace App\Services;

/**
 * Builds the two sections of the comprobación document from the captured gastos:
 *   1) Relación de facturas — one row per invoice.
 *   2) Póliza contable — balanced double-entry, modeled on the ERP output:
 *        · accrual    : Dr expense account + Dr IVA acreditable ; Cr acreedor
 *        · settlement : Dr acreedor ; Cr card-liability (or bank / fondo fijo)
 *      so Sumas(Debe) = Sumas(Haber) = 2 × importe total.
 *
 * Account numbers come from config/poliza.php (placeholder values for the demo).
 */
class PolizaBuilder
{
    public static function build(array $gastos): array
    {
        $cfg  = config('poliza');
        $rate = (float) ($cfg['iva_rate'] ?? 0.16);

        $relacion      = [];
        $accrual       = [];   // stage 1
        $settleDebits  = [];   // stage 2 (clear each acreedor)
        $settleCredits = [];   // stage 2 (grouped credit to card / bank)
        $importeTotal  = 0.0;
        $seq           = 0;

        foreach ($gastos as $g) {
            $total = (float) ($g['total'] ?? 0);
            if ($total <= 0) {
                continue;
            }

            $tipo = (($g['tipo'] ?? 'sf') === 'cf') ? 'cf' : 'sf';
            $base = $tipo === 'cf' ? round($total / (1 + $rate), 2) : $total;
            $iva  = round($total - $base, 2);

            $razon = trim((string) ($g['co'] ?? '')) ?: 'GASTO SIN FACTURA';
            $rfc   = trim((string) ($g['rfc'] ?? ''));
            $uuid  = trim((string) ($g['uuid'] ?? ''));

            $seq++;
            $relacion[] = [
                'n'       => $seq,
                'fecha'   => $g['fecha'] ?? '—',
                'rfc'     => $rfc ?: '—',
                'uuid'    => $uuid ?: '—',
                'importe' => $total,
                'razon'   => $razon,
            ];
            $importeTotal += $total;

            // Accounts.
            $exp = self::expenseAccount($cfg, (string) ($g['cat'] ?? ''));
            $acr = $rfc !== ''
                ? ['numero' => $cfg['acreedor_base'] . self::code4($rfc), 'cuenta' => $razon]
                : $cfg['cuentas']['acreedor_reembolsos'];

            $cardNum  = trim((string) ($g['tarjeta_numero'] ?? ''));
            $cardName = trim((string) ($g['tarjeta_nombre'] ?? ''));
            $settle = $cardNum !== ''
                ? ['numero' => $cfg['tarjeta_base'] . self::last3($cardNum), 'cuenta' => $cardName ?: 'TARJETA']
                : $cfg['cuentas']['bancos'];

            // Stage 1 — accrual.
            $accrual[] = ['numero' => $exp['numero'], 'cuenta' => $exp['cuenta'], 'debe' => $base, 'haber' => 0.0];
            if ($iva > 0) {
                $accrual[] = ['numero' => $cfg['cuentas']['iva']['numero'], 'cuenta' => $cfg['cuentas']['iva']['cuenta'], 'debe' => $iva, 'haber' => 0.0];
            }
            $accrual[] = ['numero' => $acr['numero'], 'cuenta' => $acr['cuenta'], 'debe' => 0.0, 'haber' => $total];

            // Stage 2 — settlement.
            $settleDebits[] = ['numero' => $acr['numero'], 'cuenta' => $acr['cuenta'], 'debe' => $total, 'haber' => 0.0];
            $key = $settle['numero'];
            $settleCredits[$key] ??= ['numero' => $settle['numero'], 'cuenta' => $settle['cuenta'], 'debe' => 0.0, 'haber' => 0.0];
            $settleCredits[$key]['haber'] += $total;
        }

        $poliza = array_merge($accrual, $settleDebits, array_values($settleCredits));
        $debe   = round(array_sum(array_column($poliza, 'debe')), 2);
        $haber  = round(array_sum(array_column($poliza, 'haber')), 2);

        return [
            'relacion'      => $relacion,
            'importe_total' => round($importeTotal, 2),
            'poliza'        => $poliza,
            'sumas'         => ['debe' => $debe, 'haber' => $haber],
        ];
    }

    private static function expenseAccount(array $cfg, string $cat): array
    {
        $up = mb_strtoupper($cat);
        foreach ($cfg['gastos'] as $rule) {
            foreach ($rule['match'] as $kw) {
                if ($up !== '' && str_contains($up, $kw)) {
                    return ['numero' => $rule['numero'], 'cuenta' => $rule['cuenta']];
                }
            }
        }
        return $cfg['gasto_default'];
    }

    /** Deterministic 4-digit provider code (2100–4899), avoiding the 5xxx range. */
    private static function code4(string $s): string
    {
        return str_pad((string) (2100 + (crc32($s) % 2800)), 4, '0', STR_PAD_LEFT);
    }

    private static function last3(string $num): string
    {
        $d = preg_replace('/\D/', '', $num) ?? '';
        return str_pad(substr($d, -3) ?: '000', 3, '0', STR_PAD_LEFT);
    }
}
