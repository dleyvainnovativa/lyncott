{{-- Brand bar: Lyncott logo (left) + Instrucciones & theme toggle (right). --}}
<header class="lx-brand">
    <div class="lx-brand__inner">
        <a class="lx-brand__logo" href="{{ route('comprobacion.index') }}" aria-label="Lyncott">
            @php $logo = public_path('img/logo.png'); @endphp
            @if (file_exists($logo))
                {{-- PNG logo turned white via CSS filter (brightness(0) invert(1)). --}}
                <img src="{{ asset('img/logo.png') }}" alt="Lyncott">
            @else
                <span class="lx-brand__fallback">L</span>
                <span class="text-white">Lyncott<small>Comprobación de gastos</small></span>
            @endif
        </a>

        <div class="lx-brand__right">
            <a class="lx-brand__instr" href="#" aria-label="Instrucciones">
                <i class="fa-solid fa-book-open"></i>
                <span class="d-none d-sm-inline">Instrucciones</span>
            </a>
            <button type="button" id="lxThemeBtn" class="lx-theme-btn" aria-label="Cambiar tema">
                <i class="fa-solid fa-moon"></i>
            </button>
        </div>
    </div>
</header>
