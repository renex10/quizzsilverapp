<?php

namespace App\Services;

use App\Models\Attempt;
use Illuminate\Support\Facades\Log;

/**
 * Motor de evaluación de exámenes.
 *
 * CORRECCIONES APLICADAS:
 * 1. json_schema ya viene como array PHP (cast del modelo Exam), NO usar json_decode()
 * 2. isCorrect con puntaje parcial ahora es consistente con el puntaje acumulado
 * 3. byDifficulty es dinámico — acepta cualquier valor de difficulty del JSON
 * 4. Orden correcto: $totalPoints se suma DESPUÉS de la corrección de isAnswered
 */
class EvaluationEngineService
{
    /**
     * Calcula el resultado completo de un intento.
     *
     * @param  Attempt  $attempt
     * @return array
     */
    public function calculate(Attempt $attempt): array
    {
        $exam    = $attempt->exam;
        $answers = $attempt->answers()->get()->keyBy('question_id');

        // CORRECCIÓN 1: json_schema ya es array por el cast del modelo Exam.
        // NO llamar json_decode() — devolvería null sobre un array.
        $jsonSchema = $exam->json_schema;

        // Seguridad: si por algún motivo llegara como string (legacy), decodificar
        if (is_string($jsonSchema)) {
            $jsonSchema = json_decode($jsonSchema, true);
        }

        if (empty($jsonSchema) || !isset($jsonSchema['questions'])) {
            Log::error("EvaluationEngineService: json_schema inválido para exam_id {$exam->id}");
            return $this->emptyResult();
        }

        $questions     = $jsonSchema['questions'];
        $passingScore  = $jsonSchema['exam']['passingScore']      ?? 70;
        $allowPartial  = $jsonSchema['exam']['allowPartialScore'] ?? false;

        // Acumuladores
        $totalPoints  = 0;
        $maxPoints    = 0;
        $totalCorrect = 0;
        $totalWrong   = 0;
        $criticalFailed      = false;
        $incorrectQuestions  = [];

        // Desgloses
        $byCategory   = [];
        // CORRECCIÓN 3: byDifficulty dinámico, no hardcodeado
        $byDifficulty = [];

        foreach ($questions as $question) {
            $qid        = $question['id'];
            $maxPoints += 1;

            $userAnswer = isset($answers[$qid]) ? $answers[$qid]->user_answer : null;
            $isAnswered = !is_null($userAnswer);
            $isCritical = $question['critical']    ?? false;
            $type       = $question['type'];
            $category   = $question['category']   ?? 'general';
            $difficulty = $question['difficulty']  ?? 'media';

            // CORRECCIÓN 3: inicializar dificultad dinámica si no existe
            if (!isset($byDifficulty[$difficulty])) {
                $byDifficulty[$difficulty] = ['correct' => 0, 'incorrect' => 0];
            }
            if (!isset($byCategory[$category])) {
                $byCategory[$category] = ['correct' => 0, 'incorrect' => 0];
            }

            // CORRECCIÓN 2: calcular puntaje SOLO si hay respuesta
            // Si no respondió, el puntaje es 0 directamente sin llamar al calculador
            if (!$isAnswered) {
                $questionPoints = 0;
                $isCorrect      = false;
            } else {
                $questionPoints = $this->calculateQuestionScore($question, $userAnswer, $allowPartial);

                // CORRECCIÓN 2: isCorrect es true solo con puntaje completo (1.0)
                // El puntaje parcial suma puntos pero la pregunta se cuenta como incorrecta
                // para el detalle de retroalimentación — consistente con la UI
                $isCorrect = ($questionPoints >= 1.0);
            }

            // CORRECCIÓN 2: sumar DESPUÉS de determinar isCorrect
            $totalPoints += $questionPoints;

            // Actualizar contadores y desgloses
            if ($isCorrect) {
                $totalCorrect++;
                $byCategory[$category]['correct']++;
                $byDifficulty[$difficulty]['correct']++;
            } else {
                $totalWrong++;
                $byCategory[$category]['incorrect']++;
                $byDifficulty[$difficulty]['incorrect']++;

                $incorrectQuestions[] = [
                    'questionId'    => $qid,
                    'questionText'  => $question['question'],
                    'userAnswer'    => $this->formatUserAnswer($userAnswer, $type),
                    'correctAnswer' => $this->formatCorrectAnswer($question),
                    'explanation'   => $question['explanation'] ?? 'Sin explicación disponible.',
                    'category'      => $category,
                    'difficulty'    => $difficulty,
                    'critical'      => $isCritical,
                    'partialScore'  => ($questionPoints > 0 && !$isCorrect) ? round($questionPoints, 2) : null,
                ];
            }

            // Verificar pregunta crítica fallada
            if ($isCritical && !$isCorrect) {
                $criticalFailed = true;
            }
        }

        // Calcular porcentaje y resultado final
        $percentage = $maxPoints > 0 ? round(($totalPoints / $maxPoints) * 100, 2) : 0;
        $passed     = ($percentage >= $passingScore) && !$criticalFailed;

        // Tiempo usado calculado en backend (fuente de verdad)
        $timeUsedSeconds = null;
        if ($attempt->started_at && $attempt->completed_at) {
            $timeUsedSeconds = $attempt->completed_at->diffInSeconds($attempt->started_at);
        } elseif ($attempt->created_at && $attempt->completed_at) {
            $timeUsedSeconds = $attempt->completed_at->diffInSeconds($attempt->created_at);
        }

        $detail = [
            'summary' => [
                'totalQuestions'  => $maxPoints,
                'answered'        => $totalCorrect + $totalWrong,
                'unanswered'      => $maxPoints - ($totalCorrect + $totalWrong),
                'correct'         => $totalCorrect,
                'incorrect'       => $totalWrong,
                'criticalFailed'  => $criticalFailed,
                'timeUsedSeconds' => $timeUsedSeconds,
            ],
            'byCategory'         => $byCategory,
            'byDifficulty'       => $byDifficulty,
            'incorrectQuestions' => $incorrectQuestions,
        ];

        return [
            'score'            => $totalPoints,
            'percentage'       => $percentage,
            'passed'           => $passed,
            'time_used_seconds'=> $timeUsedSeconds,
            'total_correct'    => $totalCorrect,
            'total_wrong'      => $totalWrong,
            'detail'           => $detail,
        ];
    }

    /**
     * Resultado vacío para casos de error en el schema.
     */
    private function emptyResult(): array
    {
        return [
            'score'             => 0,
            'percentage'        => 0,
            'passed'            => false,
            'time_used_seconds' => null,
            'total_correct'     => 0,
            'total_wrong'       => 0,
            'detail'            => [
                'summary' => [
                    'totalQuestions'  => 0,
                    'answered'        => 0,
                    'unanswered'      => 0,
                    'correct'         => 0,
                    'incorrect'       => 0,
                    'criticalFailed'  => false,
                    'timeUsedSeconds' => null,
                ],
                'byCategory'         => [],
                'byDifficulty'       => [],
                'incorrectQuestions' => [],
            ],
        ];
    }

    /**
     * Calcula el puntaje de una pregunta individual según su tipo.
     * Retorna un float entre 0 y 1.
     */
    private function calculateQuestionScore(array $question, mixed $userAnswer, bool $allowPartial): float
    {
        if (is_null($userAnswer)) {
            return 0;
        }

        $type          = $question['type'];
        $correctAnswer = $question['correctAnswer'];

        switch ($type) {
            case 'single_choice':
                return ($userAnswer == $correctAnswer) ? 1.0 : 0.0;

            case 'true_false':
                // Normalizar ambos a booleano antes de comparar
                $userBool    = filter_var($userAnswer, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                $correctBool = filter_var($correctAnswer, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                return ($userBool === $correctBool) ? 1.0 : 0.0;

            case 'multiple_choice':
                $selected = is_array($userAnswer) ? $userAnswer : json_decode($userAnswer, true);
                $correct  = is_array($correctAnswer) ? $correctAnswer : json_decode($correctAnswer, true);
                if (!is_array($selected) || !is_array($correct) || empty($correct)) {
                    return 0.0;
                }
                if ($allowPartial) {
                    $intersection = array_intersect($selected, $correct);
                    return min(1.0, count($intersection) / count($correct));
                } else {
                    sort($selected);
                    sort($correct);
                    return ($selected === $correct) ? 1.0 : 0.0;
                }

            case 'ordering':
                $ordered      = is_array($userAnswer)    ? $userAnswer    : json_decode($userAnswer, true);
                $correctOrder = is_array($correctAnswer) ? $correctAnswer : json_decode($correctAnswer, true);
                if (!is_array($ordered) || !is_array($correctOrder) || empty($correctOrder)) {
                    return 0.0;
                }
                if ($allowPartial) {
                    $correctPositions = 0;
                    $total = count($correctOrder);
                    for ($i = 0; $i < min(count($ordered), $total); $i++) {
                        if ($ordered[$i] === $correctOrder[$i]) {
                            $correctPositions++;
                        }
                    }
                    return $total > 0 ? $correctPositions / $total : 0.0;
                } else {
                    return ($ordered === $correctOrder) ? 1.0 : 0.0;
                }

            case 'matching':
                $matches        = is_array($userAnswer)    ? $userAnswer    : json_decode($userAnswer, true);
                $correctMatches = is_array($correctAnswer) ? $correctAnswer : json_decode($correctAnswer, true);
                if (!is_array($matches) || !is_array($correctMatches) || empty($correctMatches)) {
                    return 0.0;
                }
                $totalPairs   = count($correctMatches);
                $correctPairs = 0;
                foreach ($correctMatches as $left => $right) {
                    if (isset($matches[$left]) && $matches[$left] == $right) {
                        $correctPairs++;
                    }
                }
                // Matching siempre tiene puntaje parcial por par
                return $totalPairs > 0 ? $correctPairs / $totalPairs : 0.0;

            default:
                Log::warning("EvaluationEngineService: tipo desconocido '$type'");
                return 0.0;
        }
    }

    /**
     * Formatea la respuesta del usuario para mostrarla en el detalle de errores.
     */
    private function formatUserAnswer(mixed $userAnswer, string $type): string
    {
        if (is_null($userAnswer)) {
            return 'No respondida';
        }

        $decoded = is_array($userAnswer) ? $userAnswer : json_decode($userAnswer, true);

        return match ($type) {
            'multiple_choice' => implode(', ', (array) $decoded),
            'ordering'        => implode(' → ', (array) $decoded),
            'matching'        => implode('; ', array_map(
                fn($l, $r) => "$l → $r",
                array_keys((array) $decoded),
                array_values((array) $decoded)
            )),
            'true_false'      => $decoded ? 'Verdadero' : 'Falso',
            default           => (string) ($decoded ?? $userAnswer),
        };
    }

    /**
     * Formatea la respuesta correcta para mostrarla en el detalle.
     */
    private function formatCorrectAnswer(array $question): string
    {
        $correct = $question['correctAnswer'];
        $type    = $question['type'];

        return match ($type) {
            'multiple_choice' => implode(', ', is_array($correct) ? $correct : json_decode($correct, true) ?? []),
            'ordering'        => implode(' → ', is_array($correct) ? $correct : json_decode($correct, true) ?? []),
            'matching'        => implode('; ', array_map(
                fn($l, $r) => "$l → $r",
                array_keys(is_array($correct) ? $correct : json_decode($correct, true) ?? []),
                array_values(is_array($correct) ? $correct : json_decode($correct, true) ?? [])
            )),
            'true_false'      => $correct ? 'Verdadero' : 'Falso',
            default           => (string) $correct,
        };
    }
}