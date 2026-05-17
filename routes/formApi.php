<?php

use App\Http\Controllers\Admin\FormController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes para el formulario de creación de exámenes
|--------------------------------------------------------------------------
|
| Estas rutas son consumidas por el frontend (axios) para obtener datos
| y realizar validaciones asíncronas durante el proceso multipaso.
| Todas requieren autenticación y rol de administrador.
|
*/

Route::middleware(['auth', 'admin'])->prefix('admin/form')->name('admin.form.')->group(function () {
    // Obtener lista de series (para el paso 2)
    Route::get('/series', [FormController::class, 'getSeriesList'])->name('series');

    // Validar el JSON del examen (paso 5)
    Route::post('/validate-json', [FormController::class, 'validateJson'])->name('validate-json');
});