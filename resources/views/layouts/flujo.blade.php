<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Solicitud') · Lyncott</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap">

    <script>
        try {
            var t = localStorage.getItem('lx-theme');
            if (t) document.documentElement.setAttribute('data-theme', t);
        } catch (e) {}
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('head')
</head>
<body class="lx-shell-body">

    {{-- Mobile topbar (logo + hamburger) --}}
    <header class="lx-topbar">
        <a class="lx-topbar__logo" href="{{ route('welcome') }}" aria-label="Lyncott">
            @if (file_exists(public_path('img/logo.png')))
                <img src="{{ asset('img/logo.png') }}" alt="Lyncott">
            @else
                <span>Lyncott</span>
            @endif
        </a>
        <button class="lx-topbar__burger" type="button"
                data-bs-toggle="offcanvas" data-bs-target="#lxFlowOffcanvas"
                aria-controls="lxFlowOffcanvas" aria-label="Abrir menú">
            <i class="fa-solid fa-bars"></i>
        </button>
    </header>

    {{-- Mobile offcanvas: same content as the desktop sidebar --}}
    <div class="offcanvas offcanvas-start lx-offcanvas" tabindex="-1" id="lxFlowOffcanvas"
         aria-label="Menú de procesos">
        <div class="offcanvas-body">
            @include('partials._flow-nav', ['flujo' => $flujo, 'context' => 'offcanvas'])
        </div>
    </div>

    <div class="lx-shell">
        {{-- Desktop sidebar --}}
        <aside class="lx-sidebar">
            @include('partials._flow-nav', ['flujo' => $flujo, 'context' => 'sidebar'])
        </aside>

        {{-- Content --}}
        <main class="lx-shell__main">
            <div class="lx-shell__content">
                @yield('content')
            </div>
        </main>
    </div>

    {{-- Flow-switch warning --}}
    <div class="modal fade" id="modalSwitchFlujo" tabindex="-1" aria-labelledby="modalSwitchTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header lx-modal-head">
                    <h3 class="modal-title h5 mb-0" id="modalSwitchTitle">¿Cambiar de proceso?</h3>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Si cambias de proceso se perderá la información que hayas
                        capturado en este formulario. ¿Deseas continuar?</p>
                </div>
                <div class="modal-footer lx-modal-foot">
                    <button type="button" class="btn btn-lx-ghost" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-lx" id="btnSwitchConfirm">Sí, cambiar</button>
                </div>
            </div>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
