<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controlador para mostrar los resultados de un intento de examen.
 * 
 * Solo permite acceso si el intento pertenece al usuario autenticado
 * y el estado es 'completed'.
 * 
 * Modificado en Sesión 4 para alinear payload con AttemptController@result:
 * - Estructura unificada: camelCase, detail.summary, recommendation.
 */
class ResultController extends Controller
{
    /**
     * Muestra la página de resultados de un intento completado.
     *
     * @param  int  $attemptId
     * @return \Inertia\Response|\Illuminate\Http\RedirectResponse
     */
    public function show($attemptId)
    {
        $user = Auth::user();
        
        // Cargar intento con relaciones necesarias
        $attempt = Attempt::with(['exam.series', 'result'])
            ->where('id', $attemptId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Verificar que el intento esté completado
        if ($attempt->status !== 'completed') {
            // Si no está completado, redirigir según corresponda
            if ($attempt->status === 'active' || $attempt->status === 'pending') {
                return redirect()->route('student.attempt.active', $attempt->id);
            }
            return redirect()->route('student.catalog')->with('error', 'Este intento no está finalizado.');
        }

        // Obtener el resultado (debería existir)
        $result = $attempt->result;
        if (!$result) {
            abort(404, 'No se encontraron resultados para este intento.');
        }

        // Decodificar el detalle almacenado en JSON
        $detail = $result->detail ?? [];

        // Extraer datos usando la nueva estructura (camelCase, con summary)
        $summary = $detail['summary'] ?? [];
        $totalQuestions = $summary['totalQuestions'] ?? 0;
        $answered = $summary['answered'] ?? 0;
        $unanswered = $summary['unanswered'] ?? 0;
        $criticalFailed = $summary['criticalFailed'] ?? false;

        // Desglose por categoría, dificultad e incorrectas (convertir a camelCase si vienen en snake)
        $byCategory = $detail['byCategory'] ?? $detail['by_category'] ?? [];
        $byDifficulty = $detail['byDifficulty'] ?? $detail['by_difficulty'] ?? [];
        $incorrectQuestions = $detail['incorrectQuestions'] ?? $detail['incorrect_questions'] ?? [];

        // Tiempo usado
        $timeUsed = $summary['timeUsedSeconds'] ?? $result->time_used_seconds;

        // Determinar recomendación (reintentar, ver estadísticas, etc.)
        $recommendation = '';
        if ($result->passed) {
            $recommendation = '¡Felicidades! Has aprobado. Puedes revisar tus respuestas incorrectas para seguir aprendiendo.';
        } else {
            if ($criticalFailed) {
                $recommendation = 'Reprobaste porque fallaste una o más preguntas críticas. Es obligatorio responder correctamente esas preguntas.';
            } else {
                $recommendation = 'No alcanzaste el puntaje mínimo. Te recomendamos estudiar los temas de las categorías con más errores y volver a intentarlo.';
            }
        }

        // Preparar datos para la vista Inertia siguiendo el payload unificado
        return inertia('Student/Results/Show', [
            'attempt' => [
                'id' => $attempt->id,
                'exam_id' => $attempt->exam_id,          // ← consistente con AttemptController
                'completed_at' => $attempt->completed_at,
            ],
            'exam' => [
                'id' => $attempt->exam->id,
                'title' => $attempt->exam->title,
                'series' => $attempt->exam->series->title,
                'version' => $attempt->exam->version,
                'type' => $attempt->exam->type,
            ],
            'result' => [
                'score' => $result->score,
                'percentage' => $result->percentage,
                'passed' => $result->passed,
                'time_used_seconds' => $timeUsed,
                'total_correct' => $result->total_correct,
                'total_wrong' => $result->total_wrong,
                'detail' => [
                    'summary' => [
                        'totalQuestions' => $totalQuestions,
                        'answered' => $answered,
                        'unanswered' => $unanswered,
                        'criticalFailed' => $criticalFailed,
                    ],
                    'byCategory' => $byCategory,
                    'byDifficulty' => $byDifficulty,
                    'incorrectQuestions' => $incorrectQuestions,
                ],
            ],
            'recommendation' => $recommendation,
        ]);
    }

    /**
     * Opcional: Permite al estudiante reintentar el mismo examen.
     * 
     * @param  int  $examId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function retry($examId)
    {
        $user = Auth::user();
        
        // Verificar que el examen exista y esté publicado
        $exam = \App\Models\Exam::where('id', $examId)
            ->where('status', 'published')
            ->firstOrFail();
        
        // Redirigir al inicio de intento (creará uno nuevo o retomará abandonado)
        return redirect()->route('student.attempt.start', $examId);
    }
}