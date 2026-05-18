<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Series;
use App\Models\Topic;
use App\Models\Exam;
use App\Models\TopicQuizAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Controlador de Topics para el estudiante autenticado.
 * 
 * Permite:
 * - Ver una serie con sus topics públicos y lecciones de vista previa.
 * - Ver un topic completo (todas las lecciones, sin restricción de is_preview).
 * - Realizar mini quizzes asociados al topic.
 * - Guardar el resultado del mini quiz en topic_quiz_attempts.
 */
class TopicController extends Controller
{
    /**
     * Muestra una serie (agrupador de topics) para el estudiante.
     */
    public function seriesIndex($slug)
    {
        $series = Series::where('slug', $slug)
            ->with(['topics' => function ($query) {
                $query->where('is_public', true)
                    ->orderBy('order')
                    ->with(['lessons' => function ($q) {
                        $q->orderBy('order');
                    }]);
            }])
            ->firstOrFail();

        $finalExam = $series->exams()->where('is_final_exam', true)->first();

        return inertia('Student/Series/Show', [
            'series' => [
                'id' => $series->id,
                'title' => $series->title,
                'slug' => $series->slug,
                'description' => $series->description,
                'long_description' => $series->long_description,
                'cover_url' => $series->cover_url,
                'difficulty' => $series->difficulty,
                'estimated_hours' => $series->estimated_hours,
                'topics' => $series->topics->map(fn($topic) => [
                    'id' => $topic->id,
                    'title' => $topic->title,
                    'slug' => $topic->slug,
                    'icon' => $topic->icon,
                    'color' => $topic->color,
                    'description' => $topic->description,
                    'lessons' => $topic->lessons->map(fn($lesson) => [
                        'id' => $lesson->id,
                        'title' => $lesson->title,
                        'slug' => $lesson->slug,
                        'duration_minutes' => $lesson->duration_minutes,
                        'is_preview' => $lesson->is_preview,
                        'excerpt' => $lesson->excerpt(120),
                    ]),
                ]),
            ],
            'final_exam' => $finalExam ? [
                'id' => $finalExam->id,
                'title' => $finalExam->title,
                'version' => $finalExam->version,
            ] : null,
        ]);
    }

    /**
     * Muestra un topic completo con todas sus lecciones.
     */
    public function show($topicSlug)
    {
        $topic = Topic::where('slug', $topicSlug)
            ->with(['series', 'lessons' => function ($query) {
                $query->orderBy('order');
            }])
            ->firstOrFail();

        $finalExam = $topic->series->exams()->where('is_final_exam', true)->first();

        $previousAttempts = TopicQuizAttempt::where('topic_id', $topic->id)
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($attempt) => [
                'id' => $attempt->id,
                'percentage' => $attempt->percentage,
                'completed_at' => $attempt->completed_at?->format('Y-m-d H:i'),
            ]);

        return inertia('Student/Topic/Show', [
            'topic' => [
                'id' => $topic->id,
                'title' => $topic->title,
                'slug' => $topic->slug,
                'description' => $topic->description,
                'icon' => $topic->icon,
                'color' => $topic->color,
                'cover_url' => $topic->cover_url,
                'series' => [
                    'id' => $topic->series->id,
                    'title' => $topic->series->title,
                    'slug' => $topic->series->slug,
                ],
                'lessons' => $topic->lessons->map(fn($lesson) => [
                    'id' => $lesson->id,
                    'title' => $lesson->title,
                    'slug' => $lesson->slug,
                    'content' => $lesson->content,
                    'duration_minutes' => $lesson->duration_minutes,
                    'order' => $lesson->order,
                ]),
            ],
            'final_exam' => $finalExam ? ['id' => $finalExam->id, 'title' => $finalExam->title] : null,
            'previous_attempts' => $previousAttempts,
        ]);
    }

    /**
     * Inicia un mini quiz para el topic.
     */
    public function startQuiz($topicSlug)
    {
        $topic = Topic::where('slug', $topicSlug)->firstOrFail();
        $questions = $topic->getQuizQuestions(5);

        if (empty($questions)) {
            return response()->json([
                'success' => false,
                'message' => 'No hay preguntas disponibles para este tema. Verifica que el examen final tenga preguntas con la categoría "' . $topic->exam_category . '".'
            ], 404);
        }

        $quizQuestions = [];
        foreach ($questions as $q) {
            $quizQuestions[] = [
                'id' => $q['id'],
                'text' => $q['text'],
                'type' => $q['type'],
                'options' => $q['options'] ?? [],
                'pairs' => $q['pairs'] ?? [],
                'items' => $q['items'] ?? [],
            ];
        }

        $token = Str::random(32);
        cache([$token => [
            'topic_id' => $topic->id,
            'questions' => $questions,
            'expires_at' => now()->addMinutes(30),
        ]], 600);

        return response()->json([
            'success' => true,
            'questions' => $quizQuestions,
            'token' => $token,
        ]);
    }

    /**
     * Envía las respuestas del mini quiz, calcula el puntaje y guarda en topic_quiz_attempts.
     */
    public function submitQuiz(Request $request, $topicSlug)
    {
        $request->validate([
            'token' => 'required|string',
            'answers' => 'required|array',
            'answers.*.questionId' => 'required|string',
            'answers.*.userAnswer' => 'required',
        ]);

        $topic = Topic::where('slug', $topicSlug)->firstOrFail();

        // Recuperar datos del quiz desde cache
        $quizData = cache($request->token);

        // Validación de tipo para eliminar advertencia de Intelephense
        if (!is_array($quizData) || !isset($quizData['topic_id']) || $quizData['topic_id'] != $topic->id) {
            return response()->json([
                'success' => false,
                'message' => 'El quiz ha expirado o los datos son inválidos. Vuelve a iniciarlo.'
            ], 422);
        }

        $questions = $quizData['questions'];
        if (!is_array($questions) || empty($questions)) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontraron preguntas para este quiz.'
            ], 422);
        }

        $totalQuestions = count($questions);
        $totalCorrect = 0;
        $answersDetail = [];

        // Crear mapa de preguntas por id
        $questionsById = [];
        foreach ($questions as $q) {
            $questionsById[$q['id']] = $q;
        }

        foreach ($request->answers as $answer) {
            $qid = $answer['questionId'];
            $userAnswer = $answer['userAnswer'];
            $question = $questionsById[$qid] ?? null;
            if (!$question) {
                continue;
            }
            $isCorrect = $this->checkAnswer($question, $userAnswer);
            if ($isCorrect) {
                $totalCorrect++;
            }
            $answersDetail[] = [
                'questionId' => $qid,
                'userAnswer' => $userAnswer,
                'correctAnswer' => $question['correctAnswer'] ?? null,
                'isCorrect' => $isCorrect,
            ];
        }

        $percentage = ($totalQuestions > 0) ? round(($totalCorrect / $totalQuestions) * 100, 2) : 0;

        $finalExam = $topic->series->exams()->where('is_final_exam', true)->first();
        $examId = $finalExam ? $finalExam->id : null;

        $attempt = TopicQuizAttempt::create([
            'topic_id' => $topic->id,
            'user_id' => Auth::id(),
            'session_token' => null,
            'exam_id' => $examId,
            'percentage' => $percentage,
            'total_questions' => $totalQuestions,
            'total_correct' => $totalCorrect,
            'total_wrong' => $totalQuestions - $totalCorrect,
            'answers' => $answersDetail,
            'question_ids' => array_keys($questionsById),
            'completed_at' => now(),
        ]);

        cache()->forget($request->token);

        return response()->json([
            'success' => true,
            'result' => [
                'percentage' => $percentage,
                'total_correct' => $totalCorrect,
                'total_questions' => $totalQuestions,
                'passed' => $percentage >= 60,
                'attempt_id' => $attempt->id,
            ],
            'message' => 'Quiz completado. Has obtenido ' . $totalCorrect . '/' . $totalQuestions . ' correctas.',
        ]);
    }

    /**
     * Compara la respuesta del usuario con la correcta según el tipo de pregunta.
     */
    private function checkAnswer(array $question, $userAnswer): bool
    {
        $correct = $question['correctAnswer'] ?? null;
        if ($correct === null) return false;

        switch ($question['type']) {
            case 'single_choice':
            case 'true_false':
                return $userAnswer == $correct;
            case 'multiple_choice':
                if (!is_array($userAnswer) || !is_array($correct)) return false;
                sort($userAnswer);
                sort($correct);
                return $userAnswer == $correct;
            case 'ordering':
                if (!is_array($userAnswer) || !is_array($correct)) return false;
                return $userAnswer == $correct;
            case 'matching':
                // Si ambos son arrays asociativos, se comparan como JSON
                if (!is_array($userAnswer) || !is_array($correct)) return false;
                return $userAnswer == $correct;
            default:
                return false;
        }
    }
}