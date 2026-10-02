<?php

/*
|--------------------------------------------------------------------------
| Smartker integration configuration
|--------------------------------------------------------------------------
|
| Central map for the Smartker "complemento" submission. The real Smartker
| fieldIds for the comprobación web-form have NOT been assigned yet, so every
| id below is a DUMMY placeholder. When Smartker provides the real field map,
| change the numbers HERE and nowhere else — the controller and services read
| exclusively from this file.
|
| 'live' => false means the app builds and persists the full payload but does
| NOT call the Smartker endpoint. Flip SMARTKER_LIVE=true in .env to send.
|
*/

return [

    // Master switch. false = build + log + persist payload, do NOT send.
    'live' => env('SMARTKER_LIVE', false),

    // Credentials (only used when 'live' is true).
    'public_key'  => env('SMARTKER_PUBLIC_KEY'),
    'private_key' => env('SMARTKER_PRIVATE_KEY'),
    'hostname'    => env('SMARTKER_HOSTNAME', 'intra'),

    // Endpoints (v2). Kept here so QA/prod swaps are a config change.
    'endpoints' => [
        'auth'        => env('SMARTKER_AUTH_URL', 'https://v2-smartker-api.capturebi.com/api/auth/authenticate-with-keys'),
        'complemento' => env('SMARTKER_COMPLEMENTO_URL', 'https://v2-smartker-api.capturebi.com/api/web-form/file/9c8fde04-159c-4e58-b6b6-c14bf443c15d/b9abee88-1b21-4450-9695-97bf090bdf0a/submit'),
    ],

    /*
    |----------------------------------------------------------------------
    | Field map — ALL DUMMY NUMBERS, swap for real Smartker fieldIds later.
    |----------------------------------------------------------------------
    |
    | 'shared'  : one attribute per comprobación (flat values).
    | 'tabla'   : single attribute whose value is a JSON string of all rows.
    | 'files'   : per con-factura gasto, one XML + one PDF attribute. The
    |             numbering is a base pair + a stride, so gasto N gets
    |             (base_xml + N*stride) / (base_pdf + N*stride). Each JSON row
    |             carries a "ref" that matches its file pair.
    |
    */
    'fields' => [

        'shared' => [
            'numero_empleado'      => 1,
            'nombre_empleado'      => 2,
            'fecha_solicitud'      => 3,
            'departamento'         => 4,
            'centro_costos'        => 5,
            'clabe'                => 6,
            'sucursal_cedis'       => 7,
            'banco'                => 8,
            'tipo_gasto'           => 9,   // Lista: Viaje, Compras, Representación, Transporte…
            'justificacion'        => 10,
            'monto_comprobacion'   => 11,
            'folio_anticipo'       => 12,  // Opcional (comprobación)
        ],

        // Whole gastos table as one JSON attribute (metadata only).
        'tabla_gastos' => 99,

        // File attributes, one pair per con-factura gasto.
        'files' => [
            'base_xml' => 201,
            'base_pdf' => 202,
            'stride'   => 10,  // gasto 0 -> 201/202, gasto 1 -> 211/212, …
        ],
    ],

    // Catálogo de categorías (Anexo 2). Used for validation + the auto map.
    'categorias' => [
        'HOTEL',
        'TRANSPORTACION AEREA',
        'TRANSPORTACION TERRESTRE (Taxi)',
        'TRANSPORTACION TERRESTRE (AUTOBUS, TAXI, ESTACIONAMIENTO)',
        'TRANSPORTACION TERRESTRE (CASETAS)',
        'ALIMENTOS',
        'VARIOS (Herramienta o material)',
    ],
];
