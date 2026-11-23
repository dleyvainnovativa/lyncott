<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ComprobacionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — Lyncott Comprobación de Gastos
|--------------------------------------------------------------------------
|
| Welcome landing on '/', then one page per flow with the sidebar shell.
| The flow is fixed by the route (no in-wizard picker). Switching flows is a
| full navigation, so the sidebar warns before leaving.
|
*/

// Welcome / landing page with the two flow options (and the Microsoft login).
Route::get('/', [ComprobacionController::class, 'welcome'])->name('welcome');

// Firebase session auth (Microsoft redirect flow).
Route::post('/auth/firebase', [AuthController::class, 'login'])->name('auth.login');
Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

// One page per flow (sidebar shell + paginated steps) — gated by the session.
Route::get('/anticipo', [ComprobacionController::class, 'flujo'])
    ->defaults('flujo', 'anticipo')->middleware('firebase')->name('flujo.anticipo');
Route::get('/comprobacion', [ComprobacionController::class, 'flujo'])
    ->defaults('flujo', 'comprobacion')->middleware('firebase')->name('flujo.comprobacion');

// Legacy entry point — redirect old links to the comprobación flow. The name
// is kept so any lingering route('comprobacion.index') still resolves.
Route::get('/solicitud', fn () => redirect()->route('flujo.comprobacion'))
    ->name('comprobacion.index');

/*
| JSON endpoints consumed by resources/js. Unchanged.
*/
Route::prefix('api')->name('api.')->group(function () {

    // Datos del Solicitante: match número/RFC -> employee data.
    Route::post('/empleado/buscar', [ComprobacionController::class, 'buscarEmpleado'])
        ->name('empleado.buscar');

    // Parse an uploaded CFDI 4.0 XML server-side (fallback/validation).
    Route::post('/cfdi/parse', [ComprobacionController::class, 'parseCfdi'])
        ->name('cfdi.parse');

    // Fiscal validation proxy (Venvers) — keeps the api-key server-side.
    Route::post('/cfdi/validar', [ComprobacionController::class, 'validarCfdi'])
        ->name('cfdi.validar');

    // Render the Lyncott PDF for the review preview.
    Route::post('/comprobacion/pdf', [ComprobacionController::class, 'previewPdf'])
        ->name('comprobacion.pdf');

    // Build + persist the Smartker payload (stubbed send).
    Route::post('/comprobacion/enviar', [ComprobacionController::class, 'enviar'])
        ->name('comprobacion.enviar');
});
