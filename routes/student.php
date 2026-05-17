<?php
/* routes\student.php */

use App\Http\Controllers\Student\CatalogController;
use App\Http\Controllers\Student\DashboardController;
use App\Http\Controllers\Student\StatsController;
use App\Http\Controllers\Shared\AttemptController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas del Panel de Estudiante
|--------------------------------------------------------------------------
| Protegidas por middlewares 'auth' y 'student'.
| Prefijo '/student' y nombre 'student.' aplicados desde web.php.
|
| NOTA: las rutas compartidas de ejecución (answer, heartbeat, complete,
| result) están en web.php bajo /attempts — accesibles por ambos roles.
*/

// Dashboard del estudiante
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Catálogo de evaluaciones disponibles
Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');

// Estadísticas personales del estudiante
Route::get('/stats', [StatsController::class, 'index'])->name('stats.index');

// El estudiante inicia un intento desde el catálogo
// POST /student/exams/{examId}/start → renderiza Student/Attempt/Active
Route::post('/exams/{examId}/start', [AttemptController::class, 'start'])
     ->name('attempt.start');