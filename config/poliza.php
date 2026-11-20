<?php

/*
|--------------------------------------------------------------------------
| Póliza contable — chart of accounts (DEMO / PLACEHOLDER VALUES)
|--------------------------------------------------------------------------
| These account numbers are INVENTED for the demo. Replace the numbers and
| names with the client's real ERP catalog when available — the structure
| (expense accounts, IVA, acreedores, card liability) stays the same.
*/

return [

    'iva_rate' => 0.16,

    // Fixed accounts.
    'cuentas' => [
        'iva'                 => ['numero' => '1116-0002',      'cuenta' => 'IVA ACREDITABLE'],
        'acreedor_reembolsos' => ['numero' => '2000-0002-5000', 'cuenta' => 'ACREEDORES REEMBOLSOS'],
        'bancos'              => ['numero' => '1102-0001',      'cuenta' => 'BANCOS / FONDO FIJO'],
    ],

    // Expense account by categoría (first rule whose keyword is contained wins).
    'gastos' => [
        ['match' => ['HOTEL', 'HOSPED'],                         'numero' => '6002-0025-0003', 'cuenta' => 'HOSPEDAJE'],
        ['match' => ['AEREA', 'VUELO'],                          'numero' => '6002-0025-0001', 'cuenta' => 'TRANSPORTE AEREO'],
        ['match' => ['CASETA'],                                  'numero' => '6002-0025-0004', 'cuenta' => 'CASETAS Y PEAJES'],
        ['match' => ['TERRESTRE', 'TAXI', 'AUTOBUS', 'ESTACIONAMIENTO'], 'numero' => '6002-0025-0005', 'cuenta' => 'TRANSPORTE TERRESTRE'],
        ['match' => ['ALIMENT', 'COMIDA', 'RESTAUR'],            'numero' => '6002-0025-0002', 'cuenta' => 'COMIDAS'],
        ['match' => ['VARIO', 'HERRAMIENTA', 'MATERIAL'],        'numero' => '6002-0025-0009', 'cuenta' => 'GASTOS VARIOS'],
    ],
    'gasto_default' => ['numero' => '6002-0025-0009', 'cuenta' => 'GASTOS VARIOS'],

    // Prefixes for the generated (made-up) provider acreedor + card-liability accounts.
    'acreedor_base' => '2000-0002-',   // + a 4-digit code derived from the emisor RFC
    'tarjeta_base'  => '2000-0002-5',  // + last 3 digits of the card number
];
