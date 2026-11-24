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
        // Comprobación: field IDs updated to the new form (17-33), but Smartker
        // has NOT sent the new workflow/station UUIDs yet — these are placeholders.
        'comprobacion' => [
            'id'      => env('SMARTKER_COMP_WORKFLOW_ID', '940ed339-ad2d-4ec5-9fa5-3ef88791ec72'),
            'station' => env('SMARTKER_COMP_STATION_ID', 'fc5dc943-8a6a-4e7a-9e7e-9b8dd20f4ff1'),
        ],
        // Anticipo form UUIDs confirmed by Smartker (2026-11-24).
        'anticipo' => [
            'id'      => env('SMARTKER_ANT_WORKFLOW_ID', '51444c1c-0542-4138-8204-8dab370d2ed6'),
            'station' => env('SMARTKER_ANT_STATION_ID', '6cf7d488-6ac2-40d3-970e-c543005f9733'),
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

        // Comprobación form — new Smartker IDs 17-33 (2026-11-24).
        'comprobacion' => [
            'numero_empleado' => 17,
            'nombre_empleado' => 18,
            'desde'           => 19,
            'hasta'           => 20,
            'folio_anticipo'  => 21,
            'tipo_gasto'      => 22,
            'observaciones'   => 23,
            'departamento'    => 24,
            'centro_costos'   => 25,
            'clabe'           => 26,
            'sucursal'        => 27,
            'tabla'           => true,   // fieldId 28, built from gastos (comprobación only)
            'monto'           => 29,
            'moneda'          => 30,
            'referencia'      => 31,
            'tarjeta'         => 32,
            'banco'           => 33,
        ],

        // Anticipo form — new Smartker IDs 1-16 (2026-11-24).
        'anticipo' => [
            'numero_empleado' => 1,
            'nombre_empleado' => 2,
            'desde'           => 3,
            'hasta'           => 4,
            'tipo_gasto'      => 5,
            'observaciones'   => 6,
            'departamento'    => 7,
            'centro_costos'   => 8,
            'clabe'           => 9,
            'sucursal'        => 10,
            'banco'           => 11,
            'monto'           => 12,
            'moneda'          => 13,
            'referencia'      => 14,
            'tarjeta'         => 15,
            'folio_anticipo'  => 16,
        ],
    ],

    // Gastos table (comprobación): fieldId 28 + its column fieldIds (incl. per-row Tarjeta 40).
    'tabla' => [
        'fieldId' => 28,
        'columns' => [
            'categoria'     => ['id' => 34, 'type' => 'text'],
            'pdf'           => ['id' => 35, 'type' => 'file'],
            'xml'           => ['id' => 36, 'type' => 'file'],
            'justificacion' => ['id' => 37, 'type' => 'text'],
            'cantidad'      => ['id' => 38, 'type' => 'text'],  // "1" per line in the sample
            'importe'       => ['id' => 39, 'type' => 'text'],
            'tarjeta'       => ['id' => 40, 'type' => 'text'],
        ],
    ],

    /*
    |----------------------------------------------------------------------
    | Category catalogs (client-defined, 2026-11-24).
    | - con_factura: keyed by Tipo de Gasto (Anexo 1 image).
    | - sin_factura: one independent flat list (no tipo dependency).
    |----------------------------------------------------------------------
    */
    'categorias' => [
        'con_factura' => [
            'Gastos de viaje (Comprobación)' => [
                'CONSUMO DE ALIMENTOS', 'CASETAS', 'HOSPEDAJE', 'AUTOBUS', 'TAXI/UBER',
                'INSUMOS EXPOS', 'ESTACIONAMIENTOS', 'LAVANDERIA', 'PRUEBAS LABORATORIO',
                'FERRIE', 'MANTENIMIENTO AUTO UTILITARIO', 'GASOLINA',
            ],
            'AMEX (Comprobación)' => [
                'VUELOS', 'HOSPEDAJE', 'ALIMENTOS', 'CUOTAS', 'INTERNET', 'RENTA AUTO',
                'TAXI/UBER', 'CASETAS', 'ESTACIONAMIENTOS', 'PREMIOS (CONCURSOS VENTAS)',
                'UNIFORMES (CHOFER)',
            ],
            'Fondo Fijo (Reembolso)' => [
                'CONSUMO DE ALIMENTOS', 'CASETAS', 'HOSPEDAJE', 'TAXI/UBER', 'AUTOBUS',
                'DESCARGAS', 'ESTACIONAMIENTOS', 'ARTICULOS DE LIMPIEZA', 'PASTEL DE CUMPLEAÑOS',
                'AGUA GARRAFON', 'AGUA POTABLE', 'AGUA (PIPA)', 'INSUMOS CAFETERIA', 'PERMISOS',
                'VERIFICACIONES', 'LICENCIAS', 'PAQUETERIA', 'PAPELERIA', 'FOCOS/LAMPARAS/FERRETERIA',
                'TALACHAS', 'COMISIONES', 'INSUMOS DEGUSTACION/MUESTRAS', 'LIMPIEZA',
                'CALIBRACION DE BASCULA', 'PRUEBAS LABORATORIO', 'BOTIQUIN', 'GAS LP',
                'MANIOBRAS CARGA/DESCARGA', 'GASOLINA (PRESIDENCIA)', 'ACEITES',
                'DEDUCIBLES PRIMA DE SEGUROS AUTO',
            ],
        ],
        'sin_factura' => [
            'Pasajes', 'Comisiones bancarias', 'Cuotas/Permisos',
            'Gastos de viaje', 'Otros gastos', 'Factura extranjera',
        ],
    ],
];
