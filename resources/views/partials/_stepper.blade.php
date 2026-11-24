{{-- Responsive stepper, driven by JS. Steps are name-keyed and per-flow;
     the anticipo flow has no "gastos" step. --}}
@php
    $allSteps = [
        'solicitante' => ['lbl' => 'Datos del Solicitante', 'icon' => 'fa-user'],
        'viaje'       => ['lbl' => 'Datos del Viaje',       'icon' => 'fa-calendar-days'],
        'desglose'    => ['lbl' => 'Desglose del anticipo', 'icon' => 'fa-coins'],
        'gastos'      => ['lbl' => 'Gestión de gastos',     'icon' => 'fa-file-invoice-dollar'],
        'revisar'     => ['lbl' => 'Revisar y Enviar',      'icon' => 'fa-circle-check'],
    ];

    $seq = ($flujo ?? 'comprobacion') === 'anticipo'
        ? ['solicitante', 'viaje', 'desglose', 'revisar']
        : ['solicitante', 'viaje', 'gastos', 'revisar'];
@endphp
<ol class="lx-steps">
    @foreach ($seq as $i => $name)
        <li data-stepper="{{ $name }}" class="{{ $i === 0 ? 'is-active' : '' }}">
            <span class="lx-dot"><i class="fa-solid {{ $allSteps[$name]['icon'] }}"></i></span>
            <span class="lx-lbl">{{ $allSteps[$name]['lbl'] }}</span>
        </li>
    @endforeach
</ol>
