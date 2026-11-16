{{-- Shared sidebar / offcanvas content. No ids (included twice per page). --}}
@php
    $current = $flujo ?? 'comprobacion';

    $flows = [
        'anticipo' => [
            'url'   => route('flujo.anticipo'),
            'title' => 'Solicitud de anticipo',
            'desc'  => 'Solicita un monto por adelantado.',
            'icon'  => 'fa-hand-holding-dollar',
        ],
        'comprobacion' => [
            'url'   => route('flujo.comprobacion'),
            'title' => 'Comprobación de gastos',
            'desc'  => 'Sube tus facturas (CFDI) y arma la relación de gastos.',
            'icon'  => 'fa-file-invoice-dollar',
        ],
    ];

    $desc = $current === 'anticipo'
        ? 'Captura los datos de tu anticipo de viaje y fírmalo electrónicamente. Proceso 100 % digital.'
        : 'Sube los CFDI de tus gastos de viaje; el sistema arma la relación y la envía con firma electrónica.';
@endphp

<div class="lx-flownav">
    <a class="lx-flownav__logo" href="{{ route('welcome') }}" aria-label="Lyncott">
        @if (file_exists(public_path('img/logo.png')))
            <img src="{{ asset('img/logo.png') }}" alt="Lyncott">
        @else
            <span>Lyncott</span>
        @endif
    </a>

    <h1 class="lx-flownav__title">Portal de gastos de viaje</h1>
    <p class="lx-flownav__desc">{{ $desc }}</p>

    <div class="lx-flownav__nav">
        @foreach ($flows as $name => $f)
            @if ($name === $current)
                <div class="lx-flownav__card is-active" aria-current="page">
                    <span class="lx-flownav__ico"><i class="fa-solid {{ $f['icon'] }}"></i></span>
                    <span class="lx-flownav__txt">
                        <span class="lx-flownav__t">{{ $f['title'] }}</span>
                        <span class="lx-flownav__d">{{ $f['desc'] }}</span>
                    </span>
                    <i class="fa-solid fa-circle-check lx-flownav__on" aria-hidden="true"></i>
                </div>
            @else
                <button type="button" class="lx-flownav__card"
                        data-switch-url="{{ $f['url'] }}" data-switch-flujo="{{ $name }}">
                    <span class="lx-flownav__ico"><i class="fa-solid {{ $f['icon'] }}"></i></span>
                    <span class="lx-flownav__txt">
                        <span class="lx-flownav__t">{{ $f['title'] }}</span>
                        <span class="lx-flownav__d">{{ $f['desc'] }}</span>
                    </span>
                    <i class="fa-solid fa-arrow-right lx-flownav__go" aria-hidden="true"></i>
                </button>
            @endif
        @endforeach
    </div>

    <a class="lx-flownav__home" href="{{ route('welcome') }}">
        <i class="fa-solid fa-arrow-left-long"></i> Volver al inicio
    </a>
</div>
