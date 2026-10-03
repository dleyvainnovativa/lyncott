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

    /**
     * POST the full payload for a flow ('comprobacion' | 'anticipo') to its
     * web-form submit URL: /api/web-form/file/{workflowId}/{stationId}/submit.
     * $payload is the complete array: [ { attributes, files } ].
     *
     * @return array{status: int, body: ?string, url: string}
     */
    public static function send(array $payload, string $flujo): array
    {
        $wf  = config("smartker.workflow.{$flujo}");
        $url = rtrim(config('smartker.base_url'), '/')
            . "/api/web-form/file/{$wf['id']}/{$wf['station']}/submit";

        $token = self::authenticate();
        $body  = json_encode($payload, true);
        Log::debug("Payload", [$payload]);


        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Content-Type: application/json',
                'hostname: ' . config('smartker.hostname'),
                'Authorization: Bearer ' . $token,
            ],
            CURLOPT_TIMEOUT => 120,   // base64 PDFs can be large
        ]);

        $response = curl_exec($ch);
        $status   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (curl_errno($ch)) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \Exception($err);
        }
        curl_close($ch);

        return ['status' => $status, 'body' => $response, 'url' => $url];
    }
}
