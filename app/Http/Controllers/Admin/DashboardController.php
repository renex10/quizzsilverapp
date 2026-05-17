<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\Exam;
use App\Models\Series;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Controlador del panel de administración.
 * 
 * Muestra métricas globales, actividad reciente y accesos rápidos.
 * Solo accesible para usuarios con rol 'admin'.
 */
class DashboardController extends Controller
{
    /**
     * Muestra el dashboard del administrador con estadísticas clave.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        // =============================================================
        // 1. Métricas globales (totales)
        // =============================================================
        $totalExams = Exam::count();
        $totalSeries = Series::count();
        $totalStudents = User::whereHas('role', function ($query) {
            $query->where('name', 'student');
        })->count();
        
        // Intentos completados en los últimos 30 días
        $attemptsLast30Days = Attempt::where('status', 'completed')
            ->where('completed_at', '>=', now()->subDays(30))
            ->count();
        
        // Tasa de aprobación global (sobre intentos completados con resultado)
        $totalCompletedAttempts = Attempt::where('status', 'completed')->count();
        $approvedAttempts = Attempt::where('status', 'completed')
            ->whereHas('result', function ($q) {
                $q->where('passed', true);
            })->count();
        $globalApprovalRate = $totalCompletedAttempts > 0 
            ? round(($approvedAttempts / $totalCompletedAttempts) * 100, 2) 
            : 0;

        // =============================================================
        // 2. Actividad reciente (últimos 10 intentos completados)
        // =============================================================
        $recentAttempts = Attempt::with(['user', 'exam.series', 'result'])
            ->where('status', 'completed')
            ->orderBy('completed_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($attempt) {
                return [
                    'id' => $attempt->id,
                    'user_name' => $attempt->user->name,
                    'exam_title' => $attempt->exam->title,
                    'series_title' => $attempt->exam->series->title,
                    'percentage' => $attempt->result?->percentage,
                    'passed' => $attempt->result?->passed,
                    'completed_at' => $attempt->completed_at->format('Y-m-d H:i'),
                ];
            });

        // =============================================================
        // 3. Evaluaciones con mayor tasa de reprobación (top 5)
        // =============================================================
        $failedExams = Exam::with('series')
            ->whereHas('attempts', function ($q) {
                $q->where('status', 'completed');
            })
            ->withCount(['attempts as total_attempts' => function ($q) {
                $q->where('status', 'completed');
            }])
            ->withCount(['attempts as failed_attempts' => function ($q) {
                $q->where('status', 'completed')
                  ->whereHas('result', function ($r) {
                      $r->where('passed', false);
                  });
            }])
            ->get()
            ->filter(function ($exam) {
                return $exam->total_attempts > 0;
            })
            ->map(function ($exam) {
                $failRate = ($exam->failed_attempts / $exam->total_attempts) * 100;
                return [
                    'id' => $exam->id,
                    'title' => $exam->title,
                    'series_title' => $exam->series->title,
                    'fail_rate' => round($failRate, 2),
                    'total_attempts' => $exam->total_attempts,
                    'failed_attempts' => $exam->failed_attempts,
                ];
            })
            ->sortByDesc('fail_rate')
            ->take(5)
            ->values();

        // =============================================================
        // 4. Distribución de intentos por día (últimos 7 días)
        // =============================================================
        $last7Days = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $count = Attempt::where('status', 'completed')
                ->whereDate('completed_at', $date)
                ->count();
            $last7Days->push(['date' => $date, 'attempts' => $count]);
        }

        // =============================================================
        // 5. Estadísticas rápidas para tarjetas adicionales
        // =============================================================
        $todayAttempts = Attempt::whereDate('created_at', today())->count();
        $pendingExams = Exam::where('status', 'draft')->count();

        // =============================================================
        // 6. Retornar vista Inertia con todos los datos
        // =============================================================
        return inertia('Admin/Dashboard/Index', [
            'metrics' => [
                'total_exams' => $totalExams,
                'total_series' => $totalSeries,
                'total_students' => $totalStudents,
                'attempts_last_30_days' => $attemptsLast30Days,
                'global_approval_rate' => $globalApprovalRate,
                'today_attempts' => $todayAttempts,
                'pending_exams' => $pendingExams,
            ],
            'recent_attempts' => $recentAttempts,
            'failed_exams' => $failedExams,
            'daily_attempts' => $last7Days,
        ]);
    }
}