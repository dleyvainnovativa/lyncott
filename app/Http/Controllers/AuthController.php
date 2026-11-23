<?php

namespace App\Http\Controllers;

use App\Services\FirebaseAuth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /** Verify the Firebase ID token, enforce the domain, and open a session. */
    public function login(Request $request): JsonResponse
    {
        $token = (string) $request->input('idToken', '');
        if ($token === '') {
            return response()->json(['ok' => false, 'message' => 'Falta el token de sesión.'], 422);
        }

        try {
            $claims = FirebaseAuth::verify($token);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 401);
        }

        if (! $claims['email_verified']) {
            return response()->json(['ok' => false, 'message' => 'La cuenta de correo no está verificada.'], 403);
        }
        if (! FirebaseAuth::domainAllowed($claims['email'])) {
            return response()->json([
                'ok'      => false,
                'message' => 'Solo cuentas @lyncott.com.mx o @lyncott.mx pueden ingresar.',
            ], 403);
        }

        $request->session()->regenerate();
        $request->session()->put('firebase_user', [
            'uid'   => $claims['uid'],
            'email' => $claims['email'],
            'name'  => $claims['name'],
        ]);

        return response()->json(['ok' => true, 'redirect' => route('welcome')]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->session()->forget('firebase_user');
        $request->session()->regenerate();

        return response()->json(['ok' => true, 'redirect' => route('welcome')]);
    }
}
