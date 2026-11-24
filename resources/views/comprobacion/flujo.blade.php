@extends('layouts.flujo')

@section('title', $flujo === 'anticipo' ? 'Solicitud de anticipo' : 'Comprobación de gastos')

@push('head')
    <script>
        // Flow fixed by the route — no in-wizard picker.
        window.LX_FLUJO = @json($flujo);

        // Named routes exposed to the front-end JS.
        window.LX_ROUTES = {
            empleadoBuscar:     @json(route('api.empleado.buscar')),
            cfdiParse:          @json(route('api.cfdi.parse')),
            cfdiValidar:        @json(route('api.cfdi.validar')),
            comprobacionPdf:    @json(route('api.comprobacion.pdf')),
            comprobacionEnviar: @json(route('api.comprobacion.enviar')),
        };

        // Catálogo de categorías (Anexo 2).
        window.LX_CATS = @json($categorias);
    </script>
@endpush

@section('content')
    @include('partials._stepper', ['flujo' => $flujo])

    <div id="lxWizard">
        {{-- First step is visible; the rest start hidden. The wizard paginates. --}}
        <section data-step="solicitante">
            @include('comprobacion.steps._solicitante')
        </section>

        <section data-step="viaje" hidden>
            @include('comprobacion.steps._viaje')
        </section>

        @if ($flujo === 'anticipo')
            <section data-step="desglose" hidden>
                @include('comprobacion.steps._desglose')
            </section>
        @endif

        @if ($flujo === 'comprobacion')
            <section data-step="gastos" hidden>
                @include('comprobacion.steps._gastos')
            </section>
        @endif

        <section data-step="revisar" hidden>
            @include('comprobacion.steps._revisar')
        </section>
    </div>
@endsection
