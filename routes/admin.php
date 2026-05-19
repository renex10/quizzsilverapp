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
| REGLA CRÍTICA — orden obligatorio:
| Las rutas con segmentos fijos (reorder, import, cover) deben declararse
| ANTES del Route::resource que usa parámetros dinámicos ({topic}, {lesson}).
|
| Si van después, el resource captura el segmento fijo como un ID:
|   PATCH /topics/{topic}/lessons/reorder
|   → {lesson} recibe "reorder" → llama a update() → 404 o error
|
| La regla aplica a todos los módulos: topics, lessons, exams.
*/

// ─── Dashboard ────────────────────────────────────────────────────────────────
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// ─── Series ───────────────────────────────────────────────────────────────────
Route::resource('series', SeriesController::class);
Route::post('/series/{series}/cover',   [SeriesController::class, 'uploadCover'])
     ->name('series.cover.upload');
Route::delete('/series/{series}/cover', [SeriesController::class, 'deleteCover'])
     ->name('series.cover.delete');

// Importación masiva de series — ANTES del resource de topics
Route::post('/series/{series}/import',  [LessonController::class, 'importSeries'])
     ->name('series.import');
Route::get('/series/{series}/imports',  [LessonController::class, 'importHistory'])
     ->name('series.imports');

// ─── Topics ───────────────────────────────────────────────────────────────────
// reorder PRIMERO — si va después del resource, {topic} captura "reorder"
Route::patch('/series/{series}/topics/reorder', [TopicController::class, 'reorder'])
     ->name('topics.reorder');

Route::resource('series/{series}/topics', TopicController::class)->shallow();

Route::post('/topics/{topic}/cover',   [TopicController::class, 'uploadCover'])
     ->name('topics.cover.upload');
Route::delete('/topics/{topic}/cover', [TopicController::class, 'deleteCover'])
     ->name('topics.cover.delete');

// ─── Lessons ──────────────────────────────────────────────────────────────────
// reorder e import PRIMERO — si van después del resource, {lesson} los captura
Route::patch('/topics/{topic}/lessons/reorder', [LessonController::class, 'reorder'])
     ->name('lessons.reorder');

Route::post('/topics/{topic}/lessons/import', [LessonController::class, 'import'])
     ->name('lessons.import');

Route::resource('topics/{topic}/lessons', LessonController::class)->shallow();

// ─── Exámenes ─────────────────────────────────────────────────────────────────
// validate-json y json-guide PRIMERO — si van después, {exam} los captura
Route::post('/exams/validate-json',    [ExamController::class, 'validateJson'])
     ->name('exams.validate-json');
Route::get('/exams/json-guide/{type}', [ExamController::class, 'getJsonGuide'])
     ->name('exams.json-guide');
Route::post('/exams/{examId}/start',   [AttemptController::class, 'start'])
     ->name('exams.attempt.start');

Route::resource('exams', ExamController::class);

// ─── Usuarios ─────────────────────────────────────────────────────────────────
Route::resource('users', UserController::class);
Route::patch('/users/{id}/toggle-active', [UserController::class, 'toggleActive'])
     ->name('users.toggle-active');

// ─── Estadísticas ─────────────────────────────────────────────────────────────
Route::get('/stats',                  [StatsController::class, 'index'])
     ->name('stats');
Route::get('/stats/student/{userId}', [StatsController::class, 'studentDetail'])
     ->name('stats.student-detail');