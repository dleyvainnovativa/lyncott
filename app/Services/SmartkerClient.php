<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Live Smartker client (config-driven). Only used when SMARTKER_LIVE=true.
 * Mirrors the client's SmartkerController: key auth -> bearer -> submit.
 *
 * With SMARTKER_LIVE=false this class is never called; the controller builds,
 * logs and persists the payload instead.
 */
class SmartkerClient
{
    public static function authenticate(): string
    {
        $url = config('smartker.endpoints.auth');
        $payload = json_encode([
            'publicKey'  => config('smartker.public_key'),
            'privateKey' => config('smartker.private_key'),
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Content-Type: application/json',
                'hostname: ' . config('smartker.hostname'),
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (curl_errno($ch)) {
            throw new \Exception('Smartker auth error: ' . curl_error($ch));
        }
        curl_close($ch);

        if ($httpCode !== 200) {
            Log::error('Smartker auth failed', ['status' => $httpCode, 'response' => $response]);
            throw new \Exception('Smartker authentication failed');
        }

        $data = json_decode($response, true);
        if (! isset($data['token'])) {
            throw new \Exception('Smartker token not found in response');
        }

        return $data['token'];
    }

    /** POST the comprobación (complemento) attributes. */
    public static function complemento(array $attributes): ?string
    {
        return self::submit($attributes, config('smartker.endpoints.complemento'));
    }

    /** POST the anticipo attributes. */
    public static function anticipo(array $attributes): ?string
    {
        return self::submit($attributes, config('smartker.endpoints.anticipo'));
    }

    /** POST attributes to a web-form submit URL. Returns the raw response body. */
    private static function submit(array $attributes, string $url): ?string
    {
        $token = self::authenticate();

        $payload = json_encode(['attributes' => $attributes, 'files' => []]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Content-Type: application/json',
                'hostname: ' . config('smartker.hostname'),
                'Authorization: Bearer ' . $token,
            ],
        ]);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new \Exception(curl_error($ch));
        }
        curl_close($ch);

        return $response;
    }
}
