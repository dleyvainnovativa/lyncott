<!DOCTYPE html>
<html lang="es" data-theme="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Gastos de viaje · Lyncott</title>

    {{-- Fonts: Inter (UI) + JetBrains Mono (data). --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap">

    {{-- Linked CSS (no FOUC) + JS bundle. No wizard boots here (no #lxWizard). --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @php
    // Auto-detect the background image: supports public/img/background/<file>
        // or a single public/img/background.<ext>. Filename doesn't matter.
            $bg = null;
            $sets = [
            glob(public_path('img/background') . '/*.{jpg,jpeg,png,webp,avif,JPG,JPEG,PNG,WEBP}', GLOB_BRACE) ?: [],
            glob(public_path('img/background.{jpg,jpeg,png,webp,avif,JPG,JPEG,PNG,WEBP}'), GLOB_BRACE) ?: [],
            ];
            foreach ($sets as $set) {
            if ($set) {
            $rel = ltrim(str_replace(public_path(), '', $set[0]), '/\\');
            $bg = str_replace('\\', '/', $rel);
            break;
            }
            }
            $hasLogo = file_exists(public_path('img/logo.png'));
            @endphp

            <style>
                .lx-welcome-body {
                    margin: 0;
                    background: #f7f6f4;
                }

                .lx-welcome {
                    position: relative;
                    min-height: 100vh;
                    min-height: 100dvh;
                    display: flex;
                    flex-direction: column;
                    color: #1c1a19;
                    font-family: 'Inter', system-ui, -apple-system, sans-serif;
                    background: #f7f6f4;
                    @if ($bg) background-image: url('{{ asset($bg) }}');
                    @endif background-size: cover;
                    background-position: center;
                    background-repeat: no-repeat;
                    overflow: hidden;
                }

                /* Light wash over the photo: near-opaque on the left (for the text),
           fading right so the background image stays visible behind the card. */
                .lx-welcome__overlay {
                    position: absolute;
                    inset: 0;
                    z-index: 0;
                    pointer-events: none;
                    background:
                        radial-gradient(1050px 620px at 82% 22%, rgba(212, 37, 49, .10), transparent 62%),
                        linear-gradient(90deg, rgba(247, 246, 244, .97) 0%, rgba(247, 246, 244, .4) 46%, rgba(247, 246, 244, .58) 100%),
                        linear-gradient(0deg, rgba(247, 246, 244, .9), rgba(247, 246, 244, .25));
                }

                .lx-welcome__inner {
                    position: relative;
                    z-index: 1;
                    flex: 1;
                    width: 100%;
                    max-width: 1280px;
                    margin: 0 auto;
                    padding: 48px 40px;
                    display: grid;
                    grid-template-columns: 1.05fr .95fr;
                    gap: 40px;
                    align-items: center;
                }

                .lx-welcome__logo {
                    display: inline-flex;
                    align-items: center;
                    text-decoration: none;
                }

                /* Original (red) logo on the light background — no white invert. */
                .lx-welcome__logo img {
                    height: 52px;
                    width: auto;
                }

                .lx-welcome__logo span {
                    font-weight: 800;
                    font-size: 1.6rem;
                    color: #d42531;
                    letter-spacing: .01em;
                }

                .lx-welcome__eyebrow {
                    margin: 36px 0 16px;
                    letter-spacing: .32em;
                    text-transform: uppercase;
                    font-size: .74rem;
                    font-weight: 700;
                    color: #b41e29;
                }

                .lx-welcome__title {
                    margin: 0;
                    font-weight: 800;
                    font-size: clamp(2.6rem, 5vw, 3.7rem);
                    line-height: 1.04;
                    letter-spacing: -.01em;
                    color: #1c1a19;
                }

                .lx-welcome__title .lx-red {
                    color: #d42531;
                }

                .lx-welcome__lead {
                    margin: 22px 0 0;
                    max-width: 460px;
                    font-size: 1.07rem;
                    line-height: 1.6;
                    color: #6c6763;
                }

                .lx-welcome__actions {
                    display: flex;
                    gap: 14px;
                    margin: 32px 0 26px;
                    flex-wrap: wrap;
                }

                .lx-wbtn {
                    display: inline-flex;
                    align-items: center;
                    gap: 10px;
                    padding: 15px 26px;
                    border-radius: 12px;
                    font-weight: 700;
                    font-size: .98rem;
                    text-decoration: none;
                    border: 1px solid transparent;
                    transition: transform .15s ease, box-shadow .15s ease, background .15s ease, border-color .15s ease;
                }

                .lx-wbtn i {
                    font-size: 1rem;
                }

                .lx-wbtn--primary {
                    background: #d42531;
                    color: #fff;
                    box-shadow: 0 10px 28px -10px rgba(212, 37, 49, .5);
                }

                .lx-wbtn--primary:hover {
                    background: #b41e29;
                    color: #fff;
                    transform: translateY(-2px);
                    box-shadow: 0 16px 38px -12px rgba(212, 37, 49, .6);
                }

                .lx-wbtn--ghost {
                    background: #ffffff;
                    color: #1c1a19;
                    border-color: #d8d2cc;
                }

                .lx-wbtn--ghost:hover {
                    background: #faf9f7;
                    color: #1c1a19;
                    transform: translateY(-2px);
                    border-color: #9a938d;
                }

                .lx-welcome__trust {
                    list-style: none;
                    padding: 0;
                    margin: 8px 0 0;
                    display: flex;
                    flex-wrap: wrap;
                    align-items: center;
                    gap: 12px;
                    color: #6c6763;
                    font-size: .9rem;
                }

                .lx-welcome__trust li {
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                }

                .lx-welcome__trust i {
                    color: #d42531;
                }

                .lx-dot-sep {
                    color: #c9c2bb;
                }

                /* ---- Right-side CSS composition (decorative app card) ---- */
                .lx-welcome__visual {
                    display: flex;
                    justify-content: center;
                }

                .lx-mock {
                    width: 100%;
                    max-width: 420px;
                    background: #ffffff;
                    border: 1px solid #e7e3df;
                    border-radius: 18px;
                    box-shadow: 0 34px 80px -32px rgba(28, 26, 25, .3), 0 0 0 1px rgba(212, 37, 49, .04);
                    overflow: hidden;
                    transform: perspective(1500px) rotateY(-11deg) rotateX(3deg);
                }

                .lx-mock__head {
                    display: flex;
                    gap: 7px;
                    padding: 14px 16px;
                    border-bottom: 1px solid #efece8;
                }

                .lx-mock__dot {
                    width: 11px;
                    height: 11px;
                    border-radius: 50%;
                    background: #e2ddd7;
                }

                .lx-mock__dot:first-child {
                    background: #d42531;
                }

                .lx-mock__body {
                    padding: 30px 26px 32px;
                    text-align: center;
                }

                .lx-mock__check {
                    width: 74px;
                    height: 74px;
                    margin: 2px auto 14px;
                    border-radius: 50%;
                    display: grid;
                    place-items: center;
                    background: #e6f4ec;
                    color: #1f8a53;
                    font-size: 2rem;
                    border: 2px solid rgba(31, 138, 83, .4);
                }

                .lx-mock__t {
                    font-weight: 700;
                    font-size: 1.06rem;
                    color: #1c1a19;
                }

                .lx-mock__folio {
                    font-family: 'JetBrains Mono', monospace;
                    color: #9a938d;
                    font-size: .85rem;
                    margin-top: 4px;
                    letter-spacing: .05em;
                }

                .lx-mock__rows {
                    margin-top: 22px;
                    display: flex;
                    flex-direction: column;
                    gap: 10px;
                    text-align: left;
                }

                .lx-mock__row {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 11px 14px;
                    border-radius: 10px;
                    background: #faf9f7;
                    border: 1px solid #efece8;
                    font-size: .88rem;
                    color: #403b37;
                }

                .lx-mock__row i {
                    color: #1f8a53;
                }

                /* ---- Footer ---- */
                .lx-welcome__foot {
                    position: relative;
                    z-index: 1;
                    padding: 16px 40px;
                    text-align: right;
                    color: #9a938d;
                    font-size: .82rem;
                    font-style: italic;
                }

                .lx-welcome__foot strong {
                    color: #6c6763;
                    font-style: normal;
                }

                /* ---- Responsive ---- */
                @media (max-width: 991px) {
                    .lx-welcome__inner {
                        grid-template-columns: 1fr;
                        padding: 40px 24px;
                    }

                    .lx-welcome__visual {
                        display: none;
                    }

                    .lx-welcome__foot {
                        text-align: center;
                        padding: 16px 24px;
                    }
                }

                @media (max-width: 575px) {
                    .lx-wbtn {
                        width: 100%;
                        justify-content: center;
                    }

                    .lx-welcome__trust {
                        flex-direction: column;
                        align-items: flex-start;
                        gap: 10px;
                    }

                    .lx-dot-sep {
                        display: none;
                    }
                }

                /* ---- Auth ---- */
                .lx-welcome__hi { font-size: .95rem; color: #6c6763; margin: 10px 0 18px; }
                .lx-welcome__hi strong { color: #1c1a19; }
                .lx-logout { border: 0; background: none; color: #d42531; font-weight: 600; cursor: pointer; padding: 0; margin-left: 10px; text-decoration: underline; }
                .lx-welcome__note { font-size: .82rem; color: #9a938d; margin: 14px 0 0; }
                .lx-welcome__alert { background: rgba(212,37,49,.08); border: 1px solid rgba(212,37,49,.25); color: #b41e29; padding: 10px 14px; border-radius: 10px; font-size: .88rem; margin: 0 0 18px; max-width: 460px; }
                .lx-wbtn .fa-microsoft { color: inherit; }
            </style>
</head>

<body class="lx-welcome-body">
    <main class="lx-welcome">
        <div class="lx-welcome__overlay"></div>

        <div class="lx-welcome__inner">
            {{-- Left: brand + copy + actions --}}
            <div class="lx-welcome__content">
                <a class="lx-welcome__logo" href="{{ route('welcome') }}" aria-label="Lyncott">
                    @if ($hasLogo)
                    <img src="{{ asset('img/logo.png') }}" alt="Lyncott">
                    @else
                    <span>Lyncott</span>
                    @endif
                </a>

                <p class="lx-welcome__eyebrow">Portal en línea de</p>

                <h1 class="lx-welcome__title">Comprobación <br><span class="lx-red">de Gastos</span></h1>

                <p class="lx-welcome__lead">
                    Para solicitar y comprobar gastos operativos en general, podrás optar por algunas de las opciones siguientes:

                </p>

                @if (!empty($user))
                    <p class="lx-welcome__hi">Hola, <strong>{{ $user['name'] }}</strong>
                        <button type="button" id="btnLogout" class="lx-logout">Cerrar sesión</button></p>
                    <div class="lx-welcome__actions">
                        <a href="{{ route('flujo.anticipo') }}" class="lx-wbtn lx-wbtn--primary">
                            <i class="fa-solid fa-hand-holding-dollar"></i> Solicitud de gastos
                        </a>
                        <a href="{{ route('flujo.comprobacion') }}" class="lx-wbtn lx-wbtn--ghost">
                            <i class="fa-solid fa-file-invoice-dollar"></i> Comprobación de gastos
                        </a>
                    </div>
                @else
                    @if (!empty($authError))
                        <div class="lx-welcome__alert">{{ $authError }}</div>
                    @endif
                    <div id="authMsg" class="lx-welcome__alert" style="display:none; white-space:pre-wrap"></div>
                    <div class="lx-welcome__actions">
                        <button type="button" id="btnMsLogin" class="lx-wbtn lx-wbtn--primary">
                            <i class="fa-brands fa-microsoft"></i> Entrar con Microsoft
                        </button>
                    </div>
                    <p class="lx-welcome__note">Acceso solo para cuentas &#64;lyncott.com.mx / &#64;lyncott.mx</p>
                @endif

                <!-- <ul class="lx-welcome__trust">
                    <li><i class="fa-solid fa-pen-nib"></i> Firma electrónica ContiSign</li>
                    <li class="lx-dot-sep" aria-hidden="true">•</li>
                    <li><i class="fa-solid fa-shield-halved"></i> Datos protegidos</li>
                    <li class="lx-dot-sep" aria-hidden="true">•</li>
                    <li><i class="fa-solid fa-bolt"></i> Proceso 100&nbsp;% digital</li>
                </ul> -->
            </div>

        </div>

        <!-- <footer class="lx-welcome__foot">
            <span>Powered by</span> <strong>ContiSign®</strong>
        </footer> -->
    </main>

    <script>
        window.LX_FIREBASE = @json($firebase ?? []);
        window.LX_AUTH = {
            loginUrl:   @json(route('auth.login')),
            logoutUrl:  @json(route('auth.logout')),
            welcomeUrl: @json(route('welcome')),
            csrf:       @json(csrf_token()),
        };
        document.getElementById('btnLogout')?.addEventListener('click', async () => {
            try {
                const r = await fetch(window.LX_AUTH.logoutUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': window.LX_AUTH.csrf, 'Accept': 'application/json' },
                });
                const d = await r.json().catch(() => ({}));
                window.location.href = d.redirect || window.LX_AUTH.welcomeUrl;
            } catch (e) { window.location.href = window.LX_AUTH.welcomeUrl; }
        });
    </script>

    {{-- Firebase Microsoft sign-in (full-page redirect). Inert until LX_FIREBASE.apiKey is set. --}}
    <script type="module">
        import { initializeApp } from 'https://www.gstatic.com/firebasejs/10.12.2/firebase-app.js';
        import { getAuth, OAuthProvider, signInWithPopup }
            from 'https://www.gstatic.com/firebasejs/10.12.2/firebase-auth.js';

        const showAuthError = (msg) => {
            const box = document.getElementById('authMsg');
            if (box) { box.textContent = msg; box.style.display = 'block'; }
            else { alert(msg); }
        };

        const cfg = window.LX_FIREBASE || {};
        console.log('[auth] firebase config present:', !!cfg.apiKey, '| tenant:', cfg.tenant || '(none)');
        if (!cfg.apiKey) {
            console.warn('[auth] LX_FIREBASE.apiKey missing — sign-in is inert. Set FIREBASE_* in .env (and run php artisan config:clear).');
        } else {
            const auth = getAuth(initializeApp(cfg));

            // Popup sign-in (robust against third-party-storage partitioning that
            // breaks signInWithRedirect on modern browsers).
            document.getElementById('btnMsLogin')?.addEventListener('click', async () => {
                const provider = new OAuthProvider('microsoft.com');
                provider.addScope('openid');
                provider.addScope('email');
                provider.addScope('profile');
                // 'common' allows BOTH work/school and personal (outlook.com) accounts.
                provider.setCustomParameters({ tenant: cfg.tenant || 'common' });

                try {
                    console.log('[auth] opening Microsoft popup… (tenant:', cfg.tenant || 'common', ')');
                    const result = await signInWithPopup(auth, provider);
                    console.log('[auth] signed in as', result.user.email, '| emailVerified:', result.user.emailVerified);

                    const idToken = await result.user.getIdToken();
                    const resp = await fetch(window.LX_AUTH.loginUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': window.LX_AUTH.csrf,
                        },
                        body: JSON.stringify({ idToken }),
                    });
                    const data = await resp.json().catch(() => ({}));
                    console.log('[auth] POST /auth/firebase →', resp.status, data);

                    if (data.ok) {
                        window.location.href = data.redirect || window.LX_AUTH.welcomeUrl;
                    } else {
                        const extra = data.debug ? '\n\n' + JSON.stringify(data.debug, null, 2) : '';
                        showAuthError((data.message || 'No se pudo iniciar sesión.') + extra);
                    }
                } catch (e) {
                    console.error('[auth] signInWithPopup error:', e);
                    showAuthError('Error de autenticación: ' + (e.code ? e.code + ' — ' : '') + (e.message || e));
                }
            });
        }
    </script>
</body>

</html>