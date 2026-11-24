<?php

namespace App\Http\Controllers;

use App\Services\FirebaseAuth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /** Verify the Firebase ID token, enforce the domain, and open a session. */
    public function login(Request $request): JsonResponse
    {
        Log::info('[auth] login attempt', [
            'idToken' => $request->input('idToken', null),
        ]);
        $token = (string) $request->input('idToken', '');
        if ($token === '') {
            return response()->json(['ok' => false, 'message' => 'Falta el token de sesión.'], 422);
        }

        try {
            $claims = FirebaseAuth::verify($token);
        } catch (\Throwable $e) {
            Log::warning('[auth] Firebase verify failed', ['error' => $e->getMessage()]);
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 401);
        }

        $email  = $claims['email'];
        $domain = ($email && str_contains($email, '@')) ? strtolower(substr(strrchr($email, '@'), 1)) : null;

        Log::info('[auth] login attempt', [
            'email'          => $email,
            'domain'         => $domain,
            'email_verified' => $claims['email_verified'],
            'uid'            => $claims['uid'],
            'allowed'        => config('firebase.allowed_domains'),
        ]);

        if ((bool) config('firebase.require_verified_email', true) && ! $claims['email_verified']) {
            return response()->json(
                $this->reject('La cuenta de correo no está verificada por el proveedor. (Puedes desactivar esta validación con FIREBASE_REQUIRE_VERIFIED_EMAIL=false para pruebas.)', $email, $domain, $claims),
                403,
            );
        }

        if (! FirebaseAuth::domainAllowed($email)) {
            return response()->json(
                $this->reject('El dominio del correo no está autorizado.', $email, $domain, $claims),
                403,
            );
        }

        $request->session()->regenerate();
        $request->session()->put('firebase_user', [
            'uid'   => $claims['uid'],
            'email' => $email,
            'name'  => $claims['name'],
        ]);

        Log::info('[auth] login ok', ['email' => $email]);

        return response()->json(['ok' => true, 'redirect' => route('welcome')]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->session()->forget('firebase_user');
        $request->session()->regenerate();

        return response()->json(['ok' => true, 'redirect' => route('welcome')]);
    }

    /** Build a rejection body, with claim details attached when APP_DEBUG is on. */
    private function reject(string $message, ?string $email, ?string $domain, array $claims): array
    {
        $out = ['ok' => false, 'message' => $message];

        if (config('app.debug')) {
            $out['debug'] = [
                'email'           => $email,
                'domain'          => $domain,
                'email_verified'  => $claims['email_verified'],
                'allowed_domains' => config('firebase.allowed_domains'),
            ];
        }

        return $out;
    }
}
