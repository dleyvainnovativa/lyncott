<?php

/*
|--------------------------------------------------------------------------
| Venvers — Validación Fiscal de Comprobantes (CFDI)
|--------------------------------------------------------------------------
| Set VENVERS_API_KEY in .env. The key must never be committed or exposed to
| the front end — validation always goes through the server-side proxy.
*/

return [
    'url'     => env('VENVERS_URL', 'https://app.venvers.com/services/api/Validacion/ValidacionFiscal'),
    'key'     => env('VENVERS_API_KEY'),
    'timeout' => (int) env('VENVERS_TIMEOUT', 40),
];
