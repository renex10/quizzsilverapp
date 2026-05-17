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

        // Decodificar el detalle almacenado en JSON (ya debería venir como array por el cast del modelo)
        $detail = $result->detail ?? [];

        // Estadísticas adicionales para mejorar la vista
        $totalQuestions = $detail['summary']['totalQuestions'] ?? 0;
        $answered = $detail['summary']['answered'] ?? 0;
        $correct = $detail['summary']['correct'] ?? 0;
        $incorrect = $detail['summary']['incorrect'] ?? 0;
        $criticalFailed = $detail['summary']['criticalFailed'] ?? false;
        $timeUsed = $detail['summary']['timeUsedSeconds'] ?? $result->time_used_seconds;

        // Desglose por categoría y dificultad
        $byCategory = $detail['byCategory'] ?? [];
        $byDifficulty = $detail['byDifficulty'] ?? [];

        // Lista de preguntas incorrectas (para mostrar retroalimentación)
        $incorrectQuestions = $detail['incorrectQuestions'] ?? [];

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

        // Preparar datos para la vista Inertia
        return inertia('Student/Results/Show', [
            'attempt' => [
                'id' => $attempt->id,
                'started_at' => $attempt->started_at,
                'completed_at' => $attempt->completed_at,
                'time_used_seconds' => $timeUsed,
            ],
            'exam' => [
                'id' => $attempt->exam->id,
                'title' => $attempt->exam->title,
                'version' => $attempt->exam->version,
                'type' => $attempt->exam->type,
                'series' => $attempt->exam->series->title,
            ],
            'result' => [
                'score' => $result->score,
                'percentage' => $result->percentage,
                'passed' => $result->passed,
                'total_correct' => $result->total_correct,
                'total_wrong' => $result->total_wrong,
                'total_questions' => $totalQuestions,
                'answered' => $answered,
                'unanswered' => $totalQuestions - $answered,
                'critical_failed' => $criticalFailed,
            ],
            'detail' => [
                'by_category' => $byCategory,
                'by_difficulty' => $byDifficulty,
                'incorrect_questions' => $incorrectQuestions,
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