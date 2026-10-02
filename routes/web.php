<?php

use App\Http\Controllers\ComprobacionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — Lyncott Comprobación de Gastos
|--------------------------------------------------------------------------
|
| Phase 1 wires the wizard shell only. The endpoints the later phases need
| are declared here as a group so the front end has stable URLs to call;
| their controller methods are stubbed until their phase lands.
|
*/

// The wizard (single-page, 4-step).
Route::get('/', [ComprobacionController::class, 'index'])->name('comprobacion.index');

/*
| JSON endpoints consumed by resources/js. Kept on the web middleware group
| so they share the session + CSRF token with the Blade views (no api:install
| needed for the demo). Uncomment / implement per phase.
*/
Route::prefix('api')->name('api.')->group(function () {

    // Phase 2 — Datos del Solicitante: match número/RFC -> employee data.
    Route::post('/empleado/buscar', [ComprobacionController::class, 'buscarEmpleado'])
        ->name('empleado.buscar');

    // Phase 4 — parse an uploaded CFDI 4.0 XML server-side (fallback/validation).
    Route::post('/cfdi/parse', [ComprobacionController::class, 'parseCfdi'])
        ->name('cfdi.parse');

    // Phase 5 — render the Lyncott PDF for the review preview.
    Route::post('/comprobacion/pdf', [ComprobacionController::class, 'previewPdf'])
        ->name('comprobacion.pdf');

    // Phase 5 — build + persist the Smartker payload (stubbed send).
    Route::post('/comprobacion/enviar', [ComprobacionController::class, 'enviar'])
        ->name('comprobacion.enviar');
});
