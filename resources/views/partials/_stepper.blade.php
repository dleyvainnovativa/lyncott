{{-- Responsive stepper. Steps are declared once here; JS drives their state. --}}
@php
    $steps = [
        ['lbl' => 'Datos del Solicitante', 'icon' => 'fa-user'],
        ['lbl' => 'Datos del Viaje',       'icon' => 'fa-calendar-days'],
        ['lbl' => 'Gestión de gastos',     'icon' => 'fa-file-invoice-dollar'],
        ['lbl' => 'Revisar y Enviar',      'icon' => 'fa-circle-check'],
    ];
@endphp
<ol class="lx-steps">
    @foreach ($steps as $i => $s)
        <li class="{{ $i === 0 ? 'is-active' : '' }}" data-step-index="{{ $i }}">
            <span class="lx-dot"><i class="fa-solid {{ $s['icon'] }}"></i></span>
            <span class="lx-lbl">{{ $s['lbl'] }}</span>
        </li>
    @endforeach
</ol>
