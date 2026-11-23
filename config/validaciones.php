<?php

/*
|--------------------------------------------------------------------------
| Glosario de validaciones fiscales (Venvers)
|--------------------------------------------------------------------------
| Explains each check returned in data.matrizValidaciones and the result
| codes. Refine the wording as needed — this is the user-facing glossary.
*/

return [

    // matrizValidaciones[].resultado
    'codigos' => [
        1  => ['label' => 'Aprobada',    'desc' => 'El comprobante cumple con este criterio de validación.'],
        0  => ['label' => 'No aprobada', 'desc' => 'El comprobante no cumple con el criterio.'],
        -1 => ['label' => 'No aplica',   'desc' => 'La validación no se ejecutó o no aplica para este comprobante.'],
    ],

    // validacion (nombre) => explicación
    'checks' => [
        'Valida licencia'         => 'La licencia del servicio de validación está vigente y activa.',
        'XML válido'              => 'El archivo es un CFDI con estructura XML bien formada (versión 3.3 o 4.0).',
        'Comprobante nuevo'       => 'El UUID no se había procesado antes. Un 0 indica que el comprobante ya existe (duplicado); no necesariamente es un error.',
        'Fecha válida'            => 'La fecha de emisión del comprobante es válida y está dentro del rango permitido.',
        'Receptor válido'         => 'El RFC receptor corresponde a la empresa y está correcto.',
        'Emisor válido'           => 'El RFC emisor existe y está correctamente registrado ante el SAT.',
        'Certificado válido'      => 'El certificado de sello digital (CSD) del emisor es válido y vigente.',
        'Cadena original válida'  => 'La cadena original se generó correctamente a partir del comprobante.',
        'Sello válido'            => 'El sello digital del CFDI es auténtico y coincide con la cadena original.',
        'Web service SAT'         => 'El SAT confirma que el comprobante está vigente (no cancelado).',
        'Listas negras SAT'       => 'El emisor no aparece en las listas 69 / 69-B del SAT (EFOS / EDOS).',
        'Validación comercial'    => 'El comprobante cumple con las reglas comerciales configuradas.',
        'Conciliación'            => 'Conciliación contra el registro esperado. Un -1 indica que no aplica para este comprobante.',
        'Addenda'                 => 'Revisión de addenda. Un -1 indica que el comprobante no contiene addenda.',
    ],
];
