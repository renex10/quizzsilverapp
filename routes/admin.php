<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SeriesController;
use App\Http\Controllers\Admin\ExamController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\StatsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas del Panel de Administrador
|--------------------------------------------------------------------------
| Todas las rutas aquí están protegidas por los middlewares 'auth' y 'admin'.
| El prefijo '/admin' y el nombre 'admin.' se aplican desde web.php.
*/

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::resource('series', SeriesController::class);

Route::resource('exams', ExamController::class);
Route::post('/exams/validate-json', [ExamController::class, 'validateJson'])->name('exams.validate-json');
Route::get('/exams/json-guide/{type}', [ExamController::class, 'getJsonGuide'])->name('exams.json-guide');

Route::resource('users', UserController::class);

Route::get('/stats', [StatsController::class, 'index'])->name('stats');