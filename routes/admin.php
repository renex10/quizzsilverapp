<?php
/* routes\admin.php */
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SeriesController;
use App\Http\Controllers\Admin\ExamController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\StatsController;
use App\Http\Controllers\Shared\AttemptController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas del Panel de Administrador
|--------------------------------------------------------------------------
| Protegidas por middlewares 'auth' y 'admin'.
| Prefijo '/admin' y nombre 'admin.' aplicados desde web.php.
|
| NOTA: las rutas compartidas de ejecución (answer, heartbeat, complete,
| result) están en web.php bajo /attempts — accesibles por ambos roles.
*/

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Series
Route::resource('series', SeriesController::class);

// Rutas específicas de exams ANTES del resource
Route::post('/exams/validate-json', [ExamController::class, 'validateJson'])
     ->name('exams.validate-json');
Route::get('/exams/json-guide/{type}', [ExamController::class, 'getJsonGuide'])
     ->name('exams.json-guide');

// Admin inicia un intento desde la vista de detalle del examen
// POST /admin/exams/{examId}/start → renderiza Admin/Exams/Active
Route::post('/exams/{examId}/start', [AttemptController::class, 'start'])
     ->name('exams.attempt.start');

// Resource de exams
Route::resource('exams', ExamController::class);

// Usuarios
Route::resource('users', UserController::class);
Route::patch('/users/{id}/toggle-active', [UserController::class, 'toggleActive'])
     ->name('users.toggle-active');

// Estadísticas
Route::get('/stats', [StatsController::class, 'index'])->name('stats');
Route::get('/stats/student/{userId}', [StatsController::class, 'studentDetail'])
     ->name('stats.student-detail');