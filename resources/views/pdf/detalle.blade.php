<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 26px 30px; }

        * { font-family: 'DejaVu Sans', sans-serif; }

        body { color: #111; font-size: 9px; }

        .muted { color: #555; }
        .right { text-align: right; }
        .center { text-align: center; }
        .b { font-weight: bold; }

        /* ---- Header ---- */
        .hdr { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .hdr td { vertical-align: top; }
        .company { font-size: 8px; color: #222; }
        .logo { height: 40px; margin-top: 6px; }
        .brandword { font-size: 20px; font-weight: bold; color: #d42531; font-style: italic; }
        .title { font-size: 13px; font-weight: bold; letter-spacing: .5px; padding-top: 10px; }

        table.meta { border-collapse: collapse; float: right; }
        table.meta td { font-size: 8px; padding: 1px 2px; }
        table.meta td.k { color: #333; padding-right: 8px; }
        table.meta td.v { font-weight: bold; }

        .compby { font-size: 9px; margin: 8px 0 2px; }
        .compby .b { text-transform: uppercase; }

        /* ---- Section bar ---- */
        .secbar {
            background: #eee; border: 1px solid #ccc; font-weight: bold;
            font-size: 9px; padding: 3px 6px; margin-top: 6px;
        }

        /* ---- Tables ---- */
        table.data { width: 100%; border-collapse: collapse; margin-top: 2px; }
        table.data thead { display: table-header-group; }
        table.data th {
            font-size: 8px; text-align: left; border-bottom: 1px solid #333;
            padding: 3px 4px; background: #fafafa;
        }
        table.data td { font-size: 7.5px; padding: 2px 4px; border-bottom: 1px solid #eee; vertical-align: top; }
        table.data tr { page-break-inside: avoid; }
        td.uuid { font-family: 'DejaVu Sans Mono', monospace; word-break: break-all; }
        td.num { font-family: 'DejaVu Sans Mono', monospace; }

        .totrow td { border-top: 2px solid #333; border-bottom: none; font-weight: bold; font-size: 9px; padding-top: 4px; }

        /* ---- Signatures ---- */
        .firmas { width: 100%; border-collapse: collapse; margin-top: 28px; }
        .firmas td { width: 33.33%; text-align: center; font-size: 8px; padding-top: 2px; border-top: 1px solid #333; }
        .coment { margin-top: 14px; font-size: 8px; color: #333; }

        /* ---- Póliza ---- */
        .poliza-wrap { page-break-before: always; }
        .poliza-title { text-align: center; font-weight: bold; font-size: 11px; margin: 2px 0 8px; }
    </style>
</head>

<body>

    {{-- ======================= HEADER ======================= --}}
    <table class="hdr">
        <tr>
            <td style="width:40%">
                <div class="company">{{ $meta['empresa'] }}</div>
                @if ($logo)
                    <img class="logo" src="{{ $logo }}" alt="Lyncott">
                @else
                    <div class="brandword">Lyncott</div>
                @endif
            </td>
            <td style="width:24%" class="center">
                <div class="title">{{ $meta['titulo'] }}</div>
            </td>
            <td style="width:36%">
                <table class="meta">
                    <tr><td class="k">Sucursal</td><td class="v">{{ $meta['sucursal'] }}</td></tr>
                    <tr><td class="k">Documento</td><td class="v">{{ $meta['documento'] }}</td></tr>
                    <tr><td class="k">Fecha</td><td class="v">{{ $meta['fecha'] }}</td></tr>
                    <tr><td class="k">Moneda</td><td class="v">{{ $meta['moneda'] }}</td></tr>
                    <tr><td class="k">DoctoPrv</td><td class="v">{{ $meta['docto_prv'] }}</td></tr>
                    <tr><td class="k">Referencia</td><td class="v">{{ $meta['referencia'] }}</td></tr>
                    <tr><td class="k">Fecha de Pago</td><td class="v">{{ $meta['fecha_pago'] ?: '—' }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="compby">
        <span class="muted">Gastos comprobados por:</span>
        <span class="b">{{ $meta['comprobado_por'] }}</span>
    </div>

    {{-- ================== RELACIÓN DE FACTURAS ================== --}}
    <div class="secbar">RELACIÓN DE FACTURAS</div>
    <table class="data">
        <thead>
            <tr>
                <th style="width:3%">#</th>
                <th style="width:10%">Fecha</th>
                <th style="width:16%">RFC Emisor</th>
                <th style="width:38%">UUID</th>
                <th style="width:12%" class="right">Importe</th>
                <th style="width:21%">Razón Social</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($relacion as $r)
                <tr>
                    <td class="num">{{ $r['n'] }}</td>
                    <td class="num">{{ $r['fecha'] }}</td>
                    <td class="num">{{ $r['rfc'] }}</td>
                    <td class="uuid">{{ $r['uuid'] }}</td>
                    <td class="right num">{{ $money($r['importe']) }}</td>
                    <td>{{ $r['razon'] }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="center muted" style="padding:10px">Sin facturas capturadas.</td></tr>
            @endforelse
            <tr class="totrow">
                <td colspan="4" class="right">Importe Total</td>
                <td class="right num">{{ $money($importe_total) }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <div class="coment"><span class="muted">Comentarios:</span></div>

    <table class="firmas">
        <tr>
            <td>ELABORÓ</td>
            <td>REVISÓ</td>
            <td>AUTORIZÓ</td>
        </tr>
    </table>

    {{-- ===================== PÓLIZA CONTABLE ===================== --}}
    <div class="poliza-wrap">
        <div class="poliza-title">PÓLIZA CONTABLE</div>
        <div class="b" style="font-size:9px; margin-bottom:4px">Póliza {{ $meta['poliza_num'] }}</div>

        <table class="data">
            <thead>
                <tr>
                    <th style="width:18%">Número</th>
                    <th style="width:52%">Cuenta</th>
                    <th style="width:15%" class="right">Debe</th>
                    <th style="width:15%" class="right">Haber</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($poliza as $row)
                    <tr>
                        <td class="num">{{ $row['numero'] }}</td>
                        <td>{{ $row['cuenta'] }}</td>
                        <td class="right num">{{ (float) $row['debe'] != 0.0 ? $money($row['debe']) : '' }}</td>
                        <td class="right num">{{ (float) $row['haber'] != 0.0 ? $money($row['haber']) : '' }}</td>
                    </tr>
                @endforeach
                <tr class="totrow">
                    <td colspan="2" class="right">Sumas</td>
                    <td class="right num">{{ $money($sumas['debe']) }}</td>
                    <td class="right num">{{ $money($sumas['haber']) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

</body>

</html>
