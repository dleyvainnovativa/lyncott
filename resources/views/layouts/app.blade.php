<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Comprobación de gastos') · Lyncott</title>

    {{-- Fonts: Inter (UI) + JetBrains Mono (data). Google Fonts only. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap">

    {{-- Prevent dark-mode flash: apply saved theme before first paint. --}}
    <script>
        try {
            var t = localStorage.getItem('lx-theme');
            if (t) document.documentElement.setAttribute('data-theme', t);
        } catch (e) {}
    </script>

    {{-- Bootstrap 5 + Font Awesome + theme.css are bundled through app.js --}}
    @vite(['resources/js/app.js'])

    @stack('head')
</head>
<body>
    @include('partials._brand')

    <main class="lx-wrap">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
