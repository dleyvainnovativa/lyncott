@extends('layouts.app')

@section('title', 'Comprobación de gastos')

@push('head')
    <script>
        // Named routes exposed to the front-end JS.
        window.LX_ROUTES = {
            empleadoBuscar: @json(route('api.empleado.buscar')),
            cfdiParse:      @json(route('api.cfdi.parse')),
            comprobacionPdf: @json(route('api.comprobacion.pdf')),
            comprobacionEnviar: @json(route('api.comprobacion.enviar')),
        };
        // Catálogo de categorías (Anexo 2) para el front-end.
        window.LX_CATS = @json($categorias);
    </script>
@endpush

@section('content')
    @include('partials._stepper')

    <div id="lxWizard">
        {{-- Each panel is a [data-step]; the wizard shows one at a time. --}}
        <section data-step="0">
            @include('comprobacion.steps._solicitante')
        </section>

        <section data-step="1" hidden>
            @include('comprobacion.steps._viaje')
        </section>

        <section data-step="2" hidden>
            @include('comprobacion.steps._gastos')
        </section>

        <section data-step="3" hidden>
            @include('comprobacion.steps._revisar')
        </section>
    </div>
@endsection
