<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 32px 36px; }
    * { font-family: 'DejaVu Sans', sans-serif; }
    body { color: #1c1a19; font-size: 10.5px; }
    .bar { background: #d42531; color: #fff; padding: 11px 16px; border-radius: 6px; }
    .bar h1 { margin: 0; font-size: 15px; letter-spacing: .5px; }
    .bar .sub { font-size: 9px; opacity: .9; margin-top: 2px; }
    table.meta { width: 100%; border-collapse: collapse; margin: 16px 0 6px; }
    table.meta td { padding: 6px 8px; vertical-align: top; font-size: 10px; border-bottom: 1px solid #efece9; }
    table.meta .k { color: #6c6763; width: 30%; }
    table.meta .v { font-weight: bold; }
    .amount { margin-top: 20px; background: #1c1a19; color: #fff; border-radius: 8px; padding: 14px 18px; }
    .amount .lab { font-size: 9px; text-transform: uppercase; letter-spacing: .8px; opacity: .7; }
    .amount .val { font-size: 22px; font-weight: bold; margin-top: 3px; }
    .firmas { width: 100%; border-collapse: collapse; margin-top: 46px; }
    .firmas td { width: 50%; text-align: center; font-size: 9px; padding: 0 20px; }
    .firmas .line { border-top: 1px solid #1c1a19; margin: 0 8px 5px; padding-top: 4px; }
    .firmas .role { color: #6c6763; text-transform: uppercase; letter-spacing: .6px; font-size: 8px; }
    .foot { margin-top: 20px; font-size: 7.5px; color: #9a938d; text-align: center; }
</style>
</head>
<body>

@if (!empty($logo))
    <div style="margin-bottom:10px"><img src="{{ $logo }}" alt="Lyncott" style="height:40px;"></div>
@endif

<div class="bar">
    <h1>SOLICITUD DE ANTICIPO</h1>
    <div class="sub">Lyncott · Anticipo de gastos de viaje</div>
</div>

<table class="meta">
    <tr><td class="k">Empleado</td><td class="v">{{ $m['nombre'] }}</td></tr>
    <tr><td class="k">No. empleado</td><td class="v">{{ $m['numero'] }}</td></tr>
    <tr><td class="k">Departamento</td><td class="v">{{ $m['departamento'] ?: '—' }}</td></tr>
    <tr><td class="k">Periodo del gasto</td><td class="v">{{ $m['periodo'] }}</td></tr>
    <tr><td class="k">Tipo de gasto</td><td class="v">{{ $m['tipo_gasto'] ?: '—' }}</td></tr>
    <tr><td class="k">Centro de costos</td><td class="v">{{ $m['centro_costos'] ?: '—' }}</td></tr>
    <tr><td class="k">Banco</td><td class="v">{{ $m['banco'] ?: '—' }}</td></tr>
    <tr><td class="k">CLABE</td><td class="v">{{ $m['clabe'] ?: '—' }}</td></tr>
    <tr><td class="k">Sucursal</td><td class="v">{{ $m['sucursal'] ?: '—' }}</td></tr>
    @if (!empty($m['observaciones']))
        <tr><td class="k">Observaciones</td><td class="v" style="font-weight:normal">{{ $m['observaciones'] }}</td></tr>
    @endif
</table>

<div class="amount">
    <div class="lab">Monto del anticipo solicitado</div>
    <div class="val">{{ $m['monto'] }}</div>
</div>

<table class="firmas">
    <tr>
        <td><div class="line">{{ $m['nombre'] }}</div><div class="role">Solicita</div></td>
        <td><div class="line">&nbsp;</div><div class="role">Autoriza</div></td>
    </tr>
</table>

<div class="foot">Documento generado por la plataforma de comprobación de gastos · {{ $m['fecha'] }}</div>

</body>
</html>
