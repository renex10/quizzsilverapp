<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\ExamResult;
use App\Models\Series;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Controlador de estadísticas personales para el estudiante.
 * 
 * Muestra:
 * - Resumen global (total exámenes, tasa de aprobación, racha actual)
 * - Evolución de puntajes a lo largo del tiempo (gráfico)
 * - Rendimiento por serie (puntaje promedio por serie)
 * - Preguntas más falladas históricamente (top 5)
 * - Intentos abandonados recuperables
 */
class StatsController extends Controller
{
    /**
     * Muestra el panel de estadísticas del estudiante.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // =============================================================
        // 1. Resumen global del estudiante
        // =============================================================
        $totalAttempts = Attempt::where('user_id', $user->id)
            ->where('status', 'completed')
            ->count();
        
        $totalResults = ExamResult::where('user_id', $user->id)->count();
        $approvedCount = ExamResult::where('user_id', $user->id)
            ->where('passed', true)
            ->count();
        $approvalRate = $totalResults > 0 ? round(($approvedCount / $totalResults) * 100, 2) : 0;
        
        // Puntaje promedio general
        $avgPercentage = ExamResult::where('user_id', $user->id)
            ->avg('percentage');
        $avgPercentage = $avgPercentage ? round($avgPercentage, 2) : 0;
        
        // Racha actual de aprobados consecutivos (últimos intentos ordenados por fecha)
        $recentResults = ExamResult::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get(['passed', 'created_at']);
        $currentStreak = 0;
        foreach ($recentResults as $result) {
            if ($result->passed) {
                $currentStreak++;
            } else {
                break;
            }
        }
        
        // =============================================================
        // 2. Evolución de puntajes en el tiempo (para gráfico de líneas)
        // =============================================================
        $scoreEvolution = ExamResult::where('user_id', $user->id)
            ->with('exam')
            ->orderBy('created_at', 'asc')
            ->get(['id', 'percentage', 'created_at', 'exam_id'])
            ->map(function ($result) {
                return [
                    'date' => $result->created_at->format('Y-m-d'),
                    'percentage' => $result->percentage,
                    'exam_title' => $result->exam->title,
                    'passed' => $result->passed,
                ];
            });
        
        // =============================================================
        // 3. Rendimiento por serie (puntaje promedio agrupado por serie)
        // =============================================================
        $performanceBySeries = ExamResult::where('exam_results.user_id', $user->id)
            ->join('exams', 'exam_results.exam_id', '=', 'exams.id')
            ->join('series', 'exams.series_id', '=', 'series.id')
            ->select(
                'series.id as series_id',
                'series.title as series_title',
                DB::raw('AVG(exam_results.percentage) as avg_percentage'),
                DB::raw('COUNT(exam_results.id) as attempts_count'),
                DB::raw('SUM(CASE WHEN exam_results.passed = 1 THEN 1 ELSE 0 END) as passed_count')
            )
            ->groupBy('series.id', 'series.title')
            ->get()
            ->map(function ($item) {
                return [
                    'series_id' => $item->series_id,
                    'series_title' => $item->series_title,
                    'avg_percentage' => round($item->avg_percentage, 2),
                    'attempts_count' => $item->attempts_count,
                    'passed_count' => $item->passed_count,
                    'approval_rate' => $item->attempts_count > 0 
                        ? round(($item->passed_count / $item->attempts_count) * 100, 2)
                        : 0,
                ];
            });
        
        // =============================================================
        // 4. Preguntas más falladas (extraer de exam_results.detail)
        // =============================================================
        // Obtenemos todos los resultados y extraemos las preguntas incorrectas
        $allResults = ExamResult::where('user_id', $user->id)
            ->whereNotNull('detail')
            ->get(['detail']);
        
        $failedQuestionsCount = [];
        foreach ($allResults as $result) {
            $detail = is_array($result->detail) ? $result->detail : json_decode($result->detail, true);
            if (isset($detail['incorrectQuestions']) && is_array($detail['incorrectQuestions'])) {
                foreach ($detail['incorrectQuestions'] as $incorrect) {
                    $key = $incorrect['questionId'] . '|' . ($incorrect['questionText'] ?? '');
                    if (!isset($failedQuestionsCount[$key])) {
                        $failedQuestionsCount[$key] = [
                            'question_id' => $incorrect['questionId'],
                            'question_text' => $incorrect['questionText'] ?? 'Sin texto',
                            'category' => $incorrect['category'] ?? 'General',
                            'count' => 0,
                        ];
                    }
                    $failedQuestionsCount[$key]['count']++;
                }
            }
        }
        
        // Ordenar por frecuencia descendente y tomar top 5
        usort($failedQuestionsCount, function ($a, $b) {
            return $b['count'] - $a['count'];
        });
        $topFailedQuestions = array_slice($failedQuestionsCount, 0, 5);
        
        // =============================================================
        // 5. Intentos abandonados recuperables
        // =============================================================
        $abandonedAttempts = Attempt::with('exam')
            ->where('user_id', $user->id)
            ->where('status', 'abandoned')
            ->orderBy('last_seen_at', 'desc')
            ->get()
            ->map(function ($attempt) {
                return [
                    'id' => $attempt->id,
                    'exam_id' => $attempt->exam_id,
                    'exam_title' => $attempt->exam->title,
                    'started_at' => $attempt->started_at,
                    'last_seen_at' => $attempt->last_seen_at,
                ];
            });
        
        // =============================================================
        // 6. Datos para gráficos adicionales (opcional)
        // =============================================================
        // Distribución de aprobados/reprobados
        $passedDistribution = [
            'approved' => $approvedCount,
            'failed' => $totalResults - $approvedCount,
        ];
        
        // Últimos 5 resultados
        $latestResults = ExamResult::where('user_id', $user->id)
            ->with('exam')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($result) {
                return [
                    'date' => $result->created_at->format('d/m/Y'),
                    'exam_title' => $result->exam->title,
                    'percentage' => $result->percentage,
                    'passed' => $result->passed,
                ];
            });
        
        // =============================================================
        // 7. Retornar vista Inertia con todos los datos
        // =============================================================
        return inertia('Student/Stats/Index', [
            'summary' => [
                'total_attempts' => $totalAttempts,
                'total_exams_taken' => $totalResults,
                'approval_rate' => $approvalRate,
                'avg_percentage' => $avgPercentage,
                'current_streak' => $currentStreak,
            ],
            'score_evolution' => $scoreEvolution,
            'performance_by_series' => $performanceBySeries,
            'top_failed_questions' => $topFailedQuestions,
            'abandoned_attempts' => $abandonedAttempts,
            'passed_distribution' => $passedDistribution,
            'latest_results' => $latestResults,
        ]);
    }
}