<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Series;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Controlador de estadísticas para el administrador.
 * 
 * Muestra:
 * - Métricas globales (totales, aprobación, evolución temporal)
 * - Rendimiento por serie y comparativa entre versiones de una misma serie
 * - Actividad de cada estudiante (ranking, progreso)
 * - Exámenes con mayor tasa de reprobación
 */
class StatsController extends Controller
{
    /**
     * Muestra el panel de estadísticas globales.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        // =============================================================
        // 1. Métricas globales básicas
        // =============================================================
        $totalStudents = User::whereHas('role', fn($q) => $q->where('name', 'student'))->count();
        $totalExams = Exam::count();
        $totalSeries = Series::count();
        $totalAttempts = Attempt::where('status', 'completed')->count();
        
        // Tasa de aprobación global
        $approvedAttempts = Attempt::where('status', 'completed')
            ->whereHas('result', fn($q) => $q->where('passed', true))
            ->count();
        $globalApprovalRate = $totalAttempts > 0 ? round(($approvedAttempts / $totalAttempts) * 100, 2) : 0;

        // =============================================================
        // 2. Evolución temporal (intentos por mes, últimos 12 meses)
        // =============================================================
        $monthlyAttempts = Attempt::where('status', 'completed')
            ->where('completed_at', '>=', now()->subMonths(12))
            ->selectRaw('DATE_FORMAT(completed_at, "%Y-%m") as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn($item) => ['month' => $item->month, 'attempts' => $item->count]);

        // =============================================================
        // 3. Rendimiento por serie (puntaje promedio y aprobación)
        // =============================================================
        $performanceBySeries = Series::with('exams')
            ->get()
            ->map(function ($series) {
                $examIds = $series->exams->pluck('id');
                $results = ExamResult::whereIn('exam_id', $examIds)->get();
                $avgPercentage = $results->avg('percentage');
                $passedCount = $results->where('passed', true)->count();
                $totalResults = $results->count();
                $approvalRate = $totalResults > 0 ? round(($passedCount / $totalResults) * 100, 2) : 0;
                return [
                    'series_id' => $series->id,
                    'series_title' => $series->title,
                    'exams_count' => $series->exams->count(),
                    'total_attempts' => $totalResults,
                    'avg_percentage' => round($avgPercentage, 2),
                    'approval_rate' => $approvalRate,
                ];
            })
            ->sortByDesc('avg_percentage')
            ->values();

        // =============================================================
        // 4. Comparativa entre versiones de la misma serie
        // (para cada serie con más de un examen, comparar resultados)
        // =============================================================
        $seriesWithMultipleVersions = Series::with('exams')
            ->has('exams', '>', 1)
            ->get()
            ->map(function ($series) {
                $versionsData = $series->exams->map(function ($exam) {
                    $results = $exam->examResults;
                    $avgPercentage = $results->avg('percentage');
                    $attemptsCount = $results->count();
                    $passedCount = $results->where('passed', true)->count();
                    $approvalRate = $attemptsCount > 0 ? round(($passedCount / $attemptsCount) * 100, 2) : 0;
                    return [
                        'exam_id' => $exam->id,
                        'title' => $exam->title,
                        'version' => $exam->version,
                        'avg_percentage' => round($avgPercentage, 2),
                        'attempts_count' => $attemptsCount,
                        'approval_rate' => $approvalRate,
                    ];
                });
                return [
                    'series_title' => $series->title,
                    'versions' => $versionsData,
                ];
            });

        // =============================================================
        // 5. Exámenes con mayor tasa de reprobación (top 5)
        // =============================================================
        $mostFailedExams = Exam::with('series')
            ->whereHas('attempts', fn($q) => $q->where('status', 'completed'))
            ->withCount(['attempts as total_attempts' => fn($q) => $q->where('status', 'completed')])
            ->withCount(['attempts as failed_attempts' => fn($q) => $q->where('status', 'completed')
                ->whereHas('result', fn($r) => $r->where('passed', false))])
            ->get()
            ->filter(fn($exam) => $exam->total_attempts > 0)
            ->map(fn($exam) => [
                'id' => $exam->id,
                'title' => $exam->title,
                'series' => $exam->series->title,
                'fail_rate' => round(($exam->failed_attempts / $exam->total_attempts) * 100, 2),
                'total_attempts' => $exam->total_attempts,
                'failed_attempts' => $exam->failed_attempts,
            ])
            ->sortByDesc('fail_rate')
            ->take(5)
            ->values();

        // =============================================================
        // 6. Actividad de estudiantes (top 10 por número de intentos)
        // =============================================================
        $topStudents = User::whereHas('role', fn($q) => $q->where('name', 'student'))
            ->withCount(['attempts as total_attempts' => fn($q) => $q->where('status', 'completed')])
            ->withCount(['attempts as approved_attempts' => fn($q) => $q->where('status', 'completed')
                ->whereHas('result', fn($r) => $r->where('passed', true))])
            ->having('total_attempts', '>', 0)
            ->orderByDesc('total_attempts')
            ->limit(10)
            ->get()
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'total_attempts' => $user->total_attempts,
                'approved_attempts' => $user->approved_attempts,
                'approval_rate' => $user->total_attempts > 0 
                    ? round(($user->approved_attempts / $user->total_attempts) * 100, 2) 
                    : 0,
            ]);

        // =============================================================
        // 7. Distribución de puntajes global (histograma simple)
        // =============================================================
        $scoreDistribution = [
            '0-20' => ExamResult::whereBetween('percentage', [0, 20])->count(),
            '21-40' => ExamResult::whereBetween('percentage', [21, 40])->count(),
            '41-60' => ExamResult::whereBetween('percentage', [41, 60])->count(),
            '61-80' => ExamResult::whereBetween('percentage', [61, 80])->count(),
            '81-100' => ExamResult::whereBetween('percentage', [81, 100])->count(),
        ];

        // =============================================================
        // 8. Datos para la vista
        // =============================================================
        return inertia('Admin/Stats/Index', [
            'global' => [
                'total_students' => $totalStudents,
                'total_exams' => $totalExams,
                'total_series' => $totalSeries,
                'total_attempts' => $totalAttempts,
                'global_approval_rate' => $globalApprovalRate,
            ],
            'monthly_attempts' => $monthlyAttempts,
            'performance_by_series' => $performanceBySeries,
            'series_comparison' => $seriesWithMultipleVersions,
            'most_failed_exams' => $mostFailedExams,
            'top_students' => $topStudents,
            'score_distribution' => $scoreDistribution,
        ]);
    }

    /**
     * Endpoint para obtener estadísticas de un estudiante específico (detalle).
     * Útil para que el admin explore el progreso individual.
     *
     * @param  int  $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function studentDetail($userId)
    {
        $user = User::with('role')->findOrFail($userId);
        
        if ($user->role->name !== 'student') {
            return response()->json(['error' => 'El usuario no es un estudiante'], 403);
        }

        $attempts = $user->attempts()->with(['exam.series', 'result'])
            ->where('status', 'completed')
            ->orderBy('completed_at', 'desc')
            ->get()
            ->map(fn($a) => [
                'exam' => $a->exam->title,
                'series' => $a->exam->series->title,
                'percentage' => $a->result->percentage,
                'passed' => $a->result->passed,
                'completed_at' => $a->completed_at->format('Y-m-d H:i'),
            ]);

        $failedQuestions = collect();
        foreach ($user->examResults as $result) {
            $detail = is_array($result->detail) ? $result->detail : json_decode($result->detail, true);
            foreach ($detail['incorrectQuestions'] ?? [] as $q) {
                $failedQuestions->push($q['questionText']);
            }
        }
        $mostFailed = $failedQuestions->countBy()->sortDesc()->take(5);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'attempts' => $attempts,
            'failed_questions_top' => $mostFailed,
        ]);
    }
}