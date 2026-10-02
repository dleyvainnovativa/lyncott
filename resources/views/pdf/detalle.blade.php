<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 28px 32px; }
    * { font-family: 'DejaVu Sans', sans-serif; }
    body { color: #1c1a19; font-size: 10px; }
    .red { color: #d42531; }
    .bar { background: #d42531; color: #fff; padding: 10px 14px; border-radius: 6px; }
    .bar h1 { margin: 0; font-size: 15px; letter-spacing: .5px; }
    .bar .sub { font-size: 9px; opacity: .9; margin-top: 2px; }
    .meta { width: 100%; border-collapse: collapse; margin: 14px 0 6px; }
    .meta td { padding: 3px 6px; vertical-align: top; font-size: 9.5px; }
    .meta .k { color: #6c6763; width: 16%; }
    .meta .v { font-weight: bold; width: 34%; }
    h2.cat { background: #faf2f3; color: #b41e29; border-left: 3px solid #d42531;
             padding: 5px 8px; font-size: 10.5px; margin: 14px 0 0; }
    table.g { width: 100%; border-collapse: collapse; margin-top: 2px; }
    table.g th { background: #f0ece8; color: #6c6763; font-size: 8px; text-transform: uppercase;
                 letter-spacing: .4px; text-align: left; padding: 5px 7px; border-bottom: 1px solid #ddd; }
    table.g td { padding: 5px 7px; border-bottom: 1px solid #efece9; font-size: 9px; }
    table.g td.n, table.g th.n { text-align: right; }
    .concept { color: #9a938d; font-size: 7.5px; }
    tr.sub td { font-weight: bold; background: #fbfafa; border-top: 1px solid #ddd; }
    .grand { width: 100%; border-collapse: collapse; margin-top: 16px; }
    .grand td { padding: 7px 10px; font-size: 11px; }
    .grand .tot { background: #1c1a19; color: #fff; font-weight: bold; text-align: right; }
    .grand .lab { text-align: right; color: #6c6763; }
    .firmas { width: 100%; border-collapse: collapse; margin-top: 40px; }
    .firmas td { width: 33.33%; text-align: center; font-size: 9px; padding: 0 14px; }
    .firmas .line { border-top: 1px solid #1c1a19; margin: 0 6px 5px; padding-top: 4px; }
    .firmas .role { color: #6c6763; text-transform: uppercase; letter-spacing: .6px; font-size: 8px; }
    .foot { margin-top: 18px; font-size: 7.5px; color: #9a938d; text-align: center; }
    .empty { padding: 10px; color: #9a938d; font-style: italic; font-size: 9px; }
</style>
</head>
<body>

@if (!empty($logo))
    <div style="margin-bottom:10px">
        <img src="{{ $logo }}" alt="Lyncott" style="height:40px;">
    </div>
@endif

<div class="bar">
    <h1>DETALLE DE GASTOS — VIAJE NACIONAL</h1>
    <div class="sub">Lyncott · Comprobación de gastos de viaje</div>
</div>

<table class="meta">
    <tr>
        <td class="k">Empleado</td><td class="v">{{ $m['nombre'] }}</td>
        <td class="k">No. empleado</td><td class="v">{{ $m['numero'] }}</td>
    </tr>
    <tr>
        <td class="k">Departamento</td><td class="v">{{ $m['departamento'] ?: '—' }}</td>
        <td class="k">Centro de costos</td><td class="v">{{ $m['centro_costos'] ?: '—' }}</td>
    </tr>
    <tr>
        <td class="k">Periodo</td><td class="v">{{ $m['periodo'] }}</td>
        <td class="k">Ruta</td><td class="v">{{ $m['ruta'] }}</td>
    </tr>
    <tr>
        <td class="k">Folio anticipo</td><td class="v">{{ $m['folio_anticipo'] ?: '—' }}</td>
        <td class="k">Fecha elaboración</td><td class="v">{{ $m['fecha'] }} · MXN</td>
    </tr>
</table>

@forelse ($grupos as $grupo)
    <h2 class="cat">{{ $grupo['categoria'] ?: 'SIN CATEGORÍA' }}</h2>
    <table class="g">
        <thead>
            <tr>
                <th style="width:12%">Fecha</th>
                <th style="width:30%">No. DOC / Concepto</th>
                <th style="width:18%">RFC</th>
                <th class="n" style="width:13%">Importe</th>
                <th class="n" style="width:13%">IVA</th>
                <th class="n" style="width:14%">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($grupo['rows'] as $r)
                <tr>
                    <td>{{ $r['fecha'] }}</td>
                    <td>{{ $r['nodoc'] }}@if($r['concepto'])<div class="concept">{{ $r['concepto'] }}</div>@endif</td>
                    <td>{{ $r['rfc'] }}</td>
                    <td class="n">{{ $r['importe'] }}</td>
                    <td class="n">{{ $r['iva'] }}</td>
                    <td class="n">{{ $r['total'] }}</td>
                </tr>
            @endforeach
            <tr class="sub">
                <td colspan="5" class="n">Subtotal {{ $grupo['categoria'] }}</td>
                <td class="n">{{ $grupo['subtotal'] }}</td>
            </tr>
        </tbody>
    </table>
@empty
    <div class="empty">No hay gastos capturados.</div>
@endforelse

<table class="grand">
    <tr>
        <td class="lab" style="width:70%">Importe</td>
        <td class="tot" style="width:30%">{{ $tot['importe'] }}</td>
    </tr>
    <tr>
        <td class="lab">IVA</td>
        <td class="tot">{{ $tot['iva'] }}</td>
    </tr>
    <tr>
        <td class="lab" style="font-size:12px;color:#1c1a19;font-weight:bold">TOTAL COMPROBACIÓN</td>
        <td class="tot" style="font-size:12px">{{ $tot['total'] }}</td>
    </tr>
</table>

<table class="firmas">
    <tr>
        <td><div class="line">{{ $m['nombre'] }}</div><div class="role">Elaboró</div></td>
        <td><div class="line">&nbsp;</div><div class="role">Revisó</div></td>
        <td><div class="line">&nbsp;</div><div class="role">Autorizó</div></td>
    </tr>
</table>

<div class="foot">Documento generado por la plataforma de comprobación de gastos · {{ $m['fecha'] }}</div>

</body>
</html>
