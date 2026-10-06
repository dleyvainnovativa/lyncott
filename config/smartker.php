<?php

/*
|--------------------------------------------------------------------------
| Smartker integration configuration
|--------------------------------------------------------------------------
|
| Central map for the Smartker submission, matching the real payload shape:
|
|   [ { "attributes": [...], "files": [...] } ]
|
| - Shared attributes: fieldIds 1-8 + grand total 14.
| - The gastos table is fieldId 13, value "" and a `tableAttributes` array
|   of cells; each row (starting at 1) has columns 9,10,11,12,15,16.
| - Files (fieldTypeId 15) carry base64 data URIs; the principal document
|   goes in the object's `files` array.
| - The submit URL is /api/web-form/file/{workflowId}/{stationId}/submit.
|
| 'live' => false means build + log + persist the payload but DO NOT send.
|
*/

return [

    'live' => env('SMARTKER_LIVE', false),

    'public_key'  => env('SMARTKER_PUBLIC_KEY'),
    'private_key' => env('SMARTKER_PRIVATE_KEY'),
    'hostname'    => env('SMARTKER_HOSTNAME', 'intra'),

    'base_url' => env('SMARTKER_BASE_URL', 'https://v2-smartker-api.capturebi.com'),

    'endpoints' => [
        'auth' => env('SMARTKER_AUTH_URL', 'https://v2-smartker-api.capturebi.com/api/auth/authenticate-with-keys'),
    ],

    // workflowId / stationId are the two UUID segments of the submit URL.
    'workflow' => [
        'comprobacion' => [
            'id'      => env('SMARTKER_COMP_WORKFLOW_ID', '940ed339-ad2d-4ec5-9fa5-3ef88791ec72'),
            'station' => env('SMARTKER_COMP_STATION_ID', 'fc5dc943-8a6a-4e7a-9e7e-9b8dd20f4ff1'),
        ],
        // Anticipo form UUIDs not confirmed yet — placeholders from the upload form.
        'anticipo' => [
            'id'      => env('SMARTKER_ANT_WORKFLOW_ID', '3818edb4-1cd8-4df7-9375-9537d77492f2'),
            'station' => env('SMARTKER_ANT_STATION_ID', 'f55b1593-e791-4da0-aa21-95b4cd1f5d85'),
        ],
    ],

    // Smartker field type ids seen in the sample payload.
    'field_types' => [
        'text' => 1,
        'file' => 15,
    ],

    /*
    |----------------------------------------------------------------------
    | Field maps — one per flow. Keys are logical names; values are the
    | Smartker fieldIds. Order here is the order emitted in the payload.
    | The gastos table ('tabla') only exists on the comprobación flow.
    |----------------------------------------------------------------------
    */
    'fields' => [

        'comprobacion' => [
            'numero_empleado' => 1,
            'nombre_empleado' => 2,
            'desde'           => 3,
            'hasta'           => 30,
            'folio_anticipo'  => 31,
            'tipo_gasto'      => 32,
            'observaciones'   => 33,
            'departamento'    => 4,
            'centro_costos'   => 5,
            'clabe'           => 6,
            'sucursal'        => 7,
            'banco'           => 8,
            'tabla'           => true,   // fieldId 13, built from gastos (comprobación only)
            'monto'           => 14,
        ],

        'anticipo' => [
            'numero_empleado' => 18,
            'nombre_empleado' => 19,
            'desde'           => 20,
            'hasta'           => 21,
            'tipo_gasto'      => 22,
            'observaciones'   => 23,
            'departamento'    => 24,
            'centro_costos'   => 25,
            'clabe'           => 26,
            'sucursal'        => 27,
            'banco'           => 28,
            'monto'           => 29,
        ],
    ],

    // Gastos table (comprobación): fieldId 13 + its column fieldIds.
    'tabla' => [
        'fieldId' => 13,
        'columns' => [
            'categoria'     => ['id' => 9,  'type' => 'text'],
            'pdf'           => ['id' => 10, 'type' => 'file'],
            'xml'           => ['id' => 11, 'type' => 'file'],
            'justificacion' => ['id' => 12, 'type' => 'text'],
            'cantidad'      => ['id' => 15, 'type' => 'text'],  // "1" per line in the sample
            'importe'       => ['id' => 16, 'type' => 'text'],
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
