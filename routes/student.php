<?php

use App\Http\Controllers\Student\CatalogController;
use App\Http\Controllers\Student\DashboardController;
use App\Http\Controllers\Student\StatsController;
use App\Http\Controllers\Student\TopicController;     // ← NUEVO
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

// =============================================================
// Topics y contenido educativo (Banco de Conocimiento)
// =============================================================
// Vista pública de una serie (agrupador de temas)
Route::get('/series/{slug}', [TopicController::class, 'seriesIndex'])->name('series.show');

// Vista de un topic completo (con todas las lecciones)
Route::get('/topics/{topicSlug}', [TopicController::class, 'show'])->name('topic.show');

// Iniciar un mini quiz dentro de un topic (crea un intento de quiz)
Route::post('/topics/{topicSlug}/quiz', [TopicController::class, 'startQuiz'])->name('topic.quiz.start');

// Enviar las respuestas del mini quiz y guardar resultado
Route::post('/topics/{topicSlug}/quiz/submit', [TopicController::class, 'submitQuiz'])->name('topic.quiz.submit');

// =============================================================
// Exámenes (intentos de evaluación final)
// =============================================================
// El estudiante inicia un intento desde el catálogo
Route::post('/exams/{examId}/start', [AttemptController::class, 'start'])->name('attempt.start');