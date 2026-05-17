<?php

/* routes\web.php */

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Shared\AttemptController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

// ─────────────────────────────────────────────────────────────────────────────
// Rutas públicas
// ─────────────────────────────────────────────────────────────────────────────
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin'       => Route::has('login'),
        'canRegister'    => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion'     => PHP_VERSION,
    ]);
})->name('home');

// Redirección post-login según rol
Route::middleware('auth')->get('/dashboard', function () {
    /** @var \App\Models\User $user */
    $user = Auth::user();
    $user->load('role');

    if ($user->role && $user->role->name === 'admin') {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('student.dashboard');
})->name('dashboard');

// ─────────────────────────────────────────────────────────────────────────────
// Perfil
// ─────────────────────────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ─────────────────────────────────────────────────────────────────────────────
// Autenticación (Breeze)
// ─────────────────────────────────────────────────────────────────────────────
require __DIR__.'/auth.php';

// ─────────────────────────────────────────────────────────────────────────────
// Rutas compartidas de ejecución de examen
//
// Accesibles por admin y student sin distinción de rol.
// El controlador detecta internamente el rol para renderizar la vista correcta.
// Se registran aquí — fuera de los grupos de rol — para evitar duplicar rutas
// en admin.php y student.php y para que los prefijos no interfieran con el
// flujo de respuestas (ej. un admin inicia en /admin/exams/{id}/start pero
// sus respuestas van a /attempts/{id}/answer sin prefijo de rol).
// ─────────────────────────────────────────────────────────────────────────────
Route::middleware('auth')->prefix('attempts')->name('attempts.')->group(function () {
    // Retomar un intento por su ID (dashboard, URL directa)
    Route::get('/{attemptId}/resume',    [AttemptController::class, 'resume'])
         ->name('resume');

    // Guardar respuesta en tiempo real
    Route::post('/{attemptId}/answer',   [AttemptController::class, 'storeAnswer'])
         ->name('answer');

    // Heartbeat — mantiene la sesión y sincroniza el temporizador
    Route::post('/{attemptId}/heartbeat',[AttemptController::class, 'heartbeat'])
         ->name('heartbeat');

    // Finalizar el intento y calcular resultado
    Route::post('/{attemptId}/complete', [AttemptController::class, 'complete'])
         ->name('complete');

    // Ver resultado de un intento completado
    Route::get('/{attemptId}/result',    [AttemptController::class, 'result'])
         ->name('result');
});

// ─────────────────────────────────────────────────────────────────────────────
// Rutas de administrador
// ─────────────────────────────────────────────────────────────────────────────
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    require __DIR__.'/admin.php';
});

// ─────────────────────────────────────────────────────────────────────────────
// Rutas de estudiante
// ─────────────────────────────────────────────────────────────────────────────
Route::middleware(['auth', 'student'])->prefix('student')->name('student.')->group(function () {
    require __DIR__.'/student.php';
});

// ─────────────────────────────────────────────────────────────────────────────
// API del formulario multipaso (admin)
// ─────────────────────────────────────────────────────────────────────────────
require __DIR__.'/formApi.php';