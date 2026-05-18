<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SeriesController;
use App\Http\Controllers\Admin\ExamController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\StatsController;
use App\Http\Controllers\Admin\TopicController;
use App\Http\Controllers\Admin\LessonController;
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

// =============================================================
// Series
// =============================================================
Route::resource('series', SeriesController::class);
// Imágenes de portada para series
Route::post('/series/{series}/cover', [SeriesController::class, 'uploadCover'])->name('series.cover.upload');
Route::delete('/series/{series}/cover', [SeriesController::class, 'deleteCover'])->name('series.cover.delete');

// =============================================================
// Topics (shallow resource: edit/update/destroy en /topics/{topic})
// =============================================================
Route::resource('series/{series}/topics', TopicController::class)->shallow();
// Reordenamiento drag & drop de topics dentro de una serie
Route::patch('/series/{series}/topics/reorder', [TopicController::class, 'reorder'])->name('topics.reorder');
// Imágenes de portada para topics
Route::post('/topics/{topic}/cover', [TopicController::class, 'uploadCover'])->name('topics.cover.upload');
Route::delete('/topics/{topic}/cover', [TopicController::class, 'deleteCover'])->name('topics.cover.delete');

// =============================================================
// Lessons (anidadas dentro de topics)
// =============================================================
Route::resource('topics/{topic}/lessons', LessonController::class)->shallow();
// Importación de lecciones desde archivo JSON (asociado a un topic existente)
Route::post('/topics/{topic}/lessons/import', [LessonController::class, 'import'])->name('lessons.import');

// =============================================================
// Importación masiva de series completas (topics + lessons)
// =============================================================
Route::post('/series/{series}/import', [LessonController::class, 'importSeries'])->name('series.import');
// Historial de importaciones de una serie
Route::get('/series/{series}/imports', [LessonController::class, 'importHistory'])->name('series.imports');

// =============================================================
// Exámenes
// =============================================================
// Rutas específicas ANTES del resource para evitar conflictos con parámetro {exam}
Route::post('/exams/validate-json', [ExamController::class, 'validateJson'])->name('exams.validate-json');
Route::get('/exams/json-guide/{type}', [ExamController::class, 'getJsonGuide'])->name('exams.json-guide');

// Admin inicia un intento desde la vista de detalle del examen
Route::post('/exams/{examId}/start', [AttemptController::class, 'start'])->name('exams.attempt.start');

// Resource de exams
Route::resource('exams', ExamController::class);

// =============================================================
// Usuarios
// =============================================================
Route::resource('users', UserController::class);
Route::patch('/users/{id}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');

// =============================================================
// Estadísticas
// =============================================================
Route::get('/stats', [StatsController::class, 'index'])->name('stats');
Route::get('/stats/student/{userId}', [StatsController::class, 'studentDetail'])->name('stats.student-detail');