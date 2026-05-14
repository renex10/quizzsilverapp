<?php

use App\Http\Controllers\Student\DashboardController;
use App\Http\Controllers\Student\CatalogController;
use App\Http\Controllers\Student\AttemptController;
use App\Http\Controllers\Student\ResultController;
use App\Http\Controllers\Student\StatsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas del Panel de Estudiante (y admin con permiso)
|--------------------------------------------------------------------------
| Middlewares: 'auth' y 'student' (este permite a admin también).
| Prefijo '/student' y nombre 'student.' aplicados desde web.php.
*/

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog');

Route::get('/attempt/{examId}', [AttemptController::class, 'start'])->name('attempt.start');
Route::post('/attempt/{attemptId}/answer', [AttemptController::class, 'storeAnswer'])->name('attempt.store-answer');
Route::post('/attempt/{attemptId}/complete', [AttemptController::class, 'complete'])->name('attempt.complete');
Route::post('/attempt/{attemptId}/heartbeat', [AttemptController::class, 'heartbeat'])->name('attempt.heartbeat');

Route::get('/results/{attemptId}', [ResultController::class, 'show'])->name('results.show');

Route::get('/stats', [StatsController::class, 'index'])->name('stats');