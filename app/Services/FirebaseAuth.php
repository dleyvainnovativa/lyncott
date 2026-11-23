<?php

namespace App\Services;

/**
 * Server-side verification of the Firebase ID token issued after the Microsoft
 * sign-in. Uses kreait/firebase-php; guarded with class_exists so the app keeps
 * working before the package is installed (composer require kreait/firebase-php).
 */
class FirebaseAuth
{
    /** @return array{uid:?string,email:?string,name:string,email_verified:bool} */
    public static function verify(string $idToken): array
    {
        if (! class_exists(\Kreait\Firebase\Factory::class)) {
            throw new \RuntimeException('Falta el paquete kreait/firebase-php (ejecuta: composer require kreait/firebase-php).');
        }

        $creds = (string) config('firebase.credentials');
        if ($creds === '') {
            throw new \RuntimeException('Falta FIREBASE_CREDENTIALS (ruta al service-account JSON).');
        }
        $path = is_file($creds) ? $creds : base_path($creds);
        if (! is_file($path)) {
            throw new \RuntimeException('No se encontró el service-account JSON en: ' . $path);
        }

        $auth     = (new \Kreait\Firebase\Factory)->withServiceAccount($path)->createAuth();
        $verified = $auth->verifyIdToken($idToken);
        $claims   = $verified->claims();

        return [
            'uid'            => $claims->get('sub'),
            'email'          => $claims->get('email'),
            'name'           => $claims->get('name') ?: ($claims->get('email') ?: 'Usuario'),
            'email_verified' => (bool) $claims->get('email_verified'),
        ];
    }

    public static function domainAllowed(?string $email): bool
    {
        if (! $email || ! str_contains($email, '@')) {
            return false;
        }
        $domain  = strtolower(substr(strrchr($email, '@'), 1));
        $allowed = array_map('strtolower', (array) config('firebase.allowed_domains', []));

        return in_array($domain, $allowed, true);
    }
}
