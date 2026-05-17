<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\AttemptAnswer;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Services\EvaluationEngineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Controlador compartido de intentos de examen.
 *
 * NOTA SOBRE NOTIFICACIONES POR CORREO:
 * El evento ExamCompleted está desactivado intencionalmente.
 * Se activará cuando el proyecto esté en producción con un
 * proveedor de correo configurado (Resend, Mailgun, SES, etc.).
 * Ver comentario en el método complete().
 */
class AttemptController extends Controller
{
    // =========================================================================
    // INICIO Y RETOMA
    // =========================================================================

    public function start(int $examId)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $exam = Exam::with('series')->findOrFail($examId);

        if ($exam->status !== 'published') {
            abort(404, 'Examen no disponible.');
        }

        // CORRECCIÓN: manejo defensivo del json_schema
        // Puede llegar como array (cast correcto) o como string (doble serialización legacy)
        $schema = $this->resolveSchema($exam->json_schema);

        if ($schema === null) {
            abort(500, 'El examen tiene un formato de datos inválido. Contactá al administrador.');
        }

        $examConf = $schema['exam'];

        // Escenario A — intento activo o pendiente
        $activeAttempt = Attempt::where('exam_id', $examId)
            ->where('user_id', $user->id)
            ->whereIn('status', ['active', 'pending'])
            ->latest()->first();

        if ($activeAttempt) {
            $activeAttempt->update([
                'status'       => 'active',
                'last_seen_at' => now(),
                'device_hint'  => request()->userAgent(),
            ]);
            return $this->renderActiveView($exam, $activeAttempt, $schema, $examConf, false);
        }

        // Escenario B — intento abandonado
        $abandonedAttempt = Attempt::where('exam_id', $examId)
            ->where('user_id', $user->id)
            ->where('status', 'abandoned')
            ->latest()->first();

        if ($abandonedAttempt) {
            $abandonedAttempt->update([
                'status'       => 'active',
                'last_seen_at' => now(),
                'device_hint'  => request()->userAgent(),
            ]);
            return $this->renderActiveView($exam, $abandonedAttempt, $schema, $examConf, true);
        }

        // Escenario C — nuevo intento
        $shuffle       = $examConf['shuffleQuestions'] ?? false;
        $questionIds   = array_column($schema['questions'], 'id');
        $questionOrder = $shuffle ? $this->shuffleArray($questionIds) : $questionIds;

        $attempt = Attempt::create([
            'exam_id'        => $exam->id,
            'user_id'        => $user->id,
            'status'         => 'pending',
            'question_order' => $questionOrder,
            'device_hint'    => request()->userAgent(),
            'started_at'     => null,
            'last_seen_at'   => now(),
        ]);

        return $this->renderActiveView($exam, $attempt, $schema, $examConf, false);
    }

    public function resume(int $attemptId)
    {
        /** @var \App\Models\User $user */
        $user    = Auth::user();
        $attempt = Attempt::with('exam.series')->findOrFail($attemptId);

        if ($attempt->user_id !== $user->id) abort(403, 'No autorizado.');
        if ($attempt->status === 'completed') return redirect()->route('attempts.result', $attemptId);
        if ($attempt->status === 'abandoned') return redirect()->route('attempts.start', $attempt->exam_id);

        $exam     = $attempt->exam;
        $schema   = $this->resolveSchema($exam->json_schema);

        if ($schema === null) {
            abort(500, 'El examen tiene un formato de datos inválido.');
        }

        $examConf = $schema['exam'];

        $attempt->update([
            'status'       => 'active',
            'last_seen_at' => now(),
            'device_hint'  => request()->userAgent(),
        ]);

        return $this->renderActiveView($exam, $attempt, $schema, $examConf, true);
    }

    // =========================================================================
    // RESPUESTAS EN TIEMPO REAL
    // =========================================================================

    public function storeAnswer(Request $request, int $attemptId)
    {
        $request->validate([
            'question_id' => 'required|string',
            'user_answer' => 'required',
        ]);

        /** @var \App\Models\User $user */
        $user    = Auth::user();
        $attempt = Attempt::findOrFail($attemptId);

        if ($attempt->user_id !== $user->id)
            return response()->json(['error' => 'No autorizado.'], 403);

        if (!in_array($attempt->status, ['active', 'pending']))
            return response()->json(['error' => 'El intento no está activo.'], 409);

        if (is_null($attempt->started_at)) $attempt->started_at = now();
        if ($attempt->status === 'pending') $attempt->status = 'active';
        $attempt->last_seen_at = now();
        $attempt->save();

        $rawAnswer  = $request->user_answer;
        $userAnswer = $rawAnswer;
        if (is_string($rawAnswer)) {
            $decoded = json_decode($rawAnswer, true);
            if (json_last_error() === JSON_ERROR_NONE) $userAnswer = $decoded;
        }

        AttemptAnswer::updateOrCreate(
            ['attempt_id' => $attempt->id, 'question_id' => $request->question_id],
            ['user_answer' => $userAnswer,  'answered_at' => now()]
        );

        return response()->json([
            'success'        => true,
            'answered_count' => AttemptAnswer::where('attempt_id', $attempt->id)->count(),
        ]);
    }

    // =========================================================================
    // HEARTBEAT
    // =========================================================================

    public function heartbeat(int $attemptId)
    {
        /** @var \App\Models\User $user */
        $user    = Auth::user();
        $attempt = Attempt::with('exam')->findOrFail($attemptId);

        if ($attempt->user_id !== $user->id)
            return response()->json(['error' => 'No autorizado.'], 403);

        if ($attempt->status !== 'active')
            return response()->json(['error' => 'Intento no activo.', 'status' => $attempt->status], 409);

        $attempt->update(['last_seen_at' => now()]);

        return response()->json([
            'success'           => true,
            'remaining_seconds' => $this->calculateRemainingSeconds($attempt),
            'server_time'       => now()->toIso8601String(),
        ]);
    }

    // =========================================================================
    // FINALIZACIÓN
    // =========================================================================

    /**
     * Para activar correos en producción:
     *   1. use App\Events\ExamCompleted;
     *   2. Descomentar: event(new ExamCompleted($attempt, $examResult));
     *   3. Completar app/Mail/ExamResultMail.php
     *   4. Configurar MAIL_MAILER en .env
     */
    public function complete(int $attemptId)
    {
        /** @var \App\Models\User $user */
        $user    = Auth::user();
        $attempt = Attempt::with('exam')->findOrFail($attemptId);

        if ($attempt->user_id !== $user->id) abort(403, 'No autorizado.');

        if ($attempt->status === 'completed')
            return redirect()->route('attempts.result', $attemptId);

        if (!in_array($attempt->status, ['active', 'pending']))
            abort(409, 'El intento no puede completarse en su estado actual.');

        DB::beginTransaction();
        try {
            $attempt->update([
                'status'       => 'completed',
                'completed_at' => now(),
            ]);

            $engine     = app(EvaluationEngineService::class);
            $resultData = $engine->calculate($attempt->fresh());

            ExamResult::create([
                'attempt_id'        => $attempt->id,
                'exam_id'           => $attempt->exam_id,
                'user_id'           => $attempt->user_id,
                'score'             => $resultData['score'],
                'percentage'        => $resultData['percentage'],
                'passed'            => $resultData['passed'],
                'time_used_seconds' => $resultData['time_used_seconds'],
                'total_correct'     => $resultData['total_correct'],
                'total_wrong'       => $resultData['total_wrong'],
                'detail'            => $resultData['detail'],
            ]);

            DB::commit();

            // event(new ExamCompleted($attempt, $examResult)); ← activar en producción

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('AttemptController@complete', [
                'attempt_id' => $attemptId,
                'message'    => $e->getMessage(),
            ]);
            abort(500, 'Error al calcular el resultado.');
        }

        return redirect()->route('attempts.result', $attemptId);
    }

    // =========================================================================
    // RESULTADO
    // =========================================================================

    public function result(int $attemptId)
    {
        /** @var \App\Models\User $user */
        $user    = Auth::user();
        $attempt = Attempt::with(['exam.series', 'result'])->findOrFail($attemptId);

        if ($attempt->user_id !== $user->id) abort(403, 'No autorizado.');

        if ($attempt->status !== 'completed' || !$attempt->result)
            return redirect()->route('attempts.start', $attempt->exam_id);

        $result = $attempt->result;
        $schema = $this->resolveSchema($attempt->exam->json_schema);

        // Enriquecer preguntas incorrectas con opciones originales
        $detail = $result->detail;
        if ($schema && isset($detail['incorrectQuestions'])) {
            $questionsById = collect($schema['questions'])->keyBy('id');
            $detail['incorrectQuestions'] = array_map(
                function ($incorrect) use ($questionsById) {
                    $original = $questionsById->get($incorrect['questionId']);
                    if ($original) {
                        $incorrect['options']     = $original['options']     ?? null;
                        $incorrect['leftColumn']  = $original['leftColumn']  ?? null;
                        $incorrect['rightColumn'] = $original['rightColumn'] ?? null;
                    }
                    return $incorrect;
                },
                $detail['incorrectQuestions']
            );
        }

        $payload = [
            'attempt' => [
                'id'           => $attempt->id,
                'exam_id'      => $attempt->exam_id,
                'completed_at' => $attempt->completed_at?->format('Y-m-d H:i'),
            ],
            'exam' => [
                'id'     => $attempt->exam->id,
                'title'  => $attempt->exam->title,
                'series' => $attempt->exam->series->title,
            ],
            'result' => [
                'score'             => $result->score,
                'percentage'        => $result->percentage,
                'passed'            => $result->passed,
                'time_used_seconds' => $result->time_used_seconds,
                'total_correct'     => $result->total_correct,
                'total_wrong'       => $result->total_wrong,
                'detail'            => $detail,
            ],
        ];

        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        $view     = $authUser->isAdmin()
            ? 'Admin/Exams/Result'
            : 'Student/Results/Show';

        return inertia($view, $payload);
    }

    // =========================================================================
    // HELPERS PRIVADOS
    // =========================================================================

    private function renderActiveView(Exam $exam, Attempt $attempt, array $schema, array $examConf, bool $isResumed)
    {
        $questions = $this->prepareQuestions($schema, $attempt->question_order);

        $existingAnswers = AttemptAnswer::where('attempt_id', $attempt->id)
            ->get()
            ->mapWithKeys(fn ($a) => [$a->question_id => $a->user_answer])
            ->toArray();

        $remainingSeconds = $this->calculateRemainingSeconds($attempt, $examConf);

        $payload = [
            'attempt' => [
                'id'             => $attempt->id,
                'status'         => $attempt->status,
                'is_resumed'     => $isResumed,
                'question_order' => $attempt->question_order,
            ],
            'exam' => [
                'id'                 => $exam->id,
                'title'              => $exam->title,
                'series'             => $exam->series->title,
                'type'               => $exam->type,
                'passing_score'      => $examConf['passingScore']      ?? 70,
                'time_limit_minutes' => $examConf['timeLimitMinutes']  ?? null,
                'allow_partial'      => $examConf['allowPartialScore'] ?? false,
            ],
            'questions'        => $questions,
            'existingAnswers'  => $existingAnswers,
            'remainingSeconds' => $remainingSeconds,
        ];

        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        $view     = $authUser->isAdmin()
            ? 'Admin/Exams/Active'
            : 'Student/Attempt/Active';

        return inertia($view, $payload);
    }

    /**
     * CORRECCIÓN CENTRAL: resuelve el json_schema independientemente
     * de cómo fue guardado en la DB.
     *
     * Casos posibles:
     * - array PHP limpio    → cast correcto del modelo Eloquent ✅
     * - string JSON         → doble serialización legacy, decodificar
     * - null o inválido     → devolver null para que el llamador aborte
     */
    private function resolveSchema(mixed $raw): ?array
    {
        // Ya es array — caso ideal
        if (is_array($raw)) {
            return $raw;
        }

        // Es string — intentar decodificar (doble encode legacy)
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        // Inválido
        return null;
    }

    private function prepareQuestions(array $schema, ?array $order): array
    {
        $questions     = $schema['questions'] ?? [];
        $questionsById = [];

        foreach ($questions as $q) {
            // Manejo defensivo: cada pregunta también puede ser string
            if (is_string($q)) {
                $q = json_decode($q, true);
            }
            if (!is_array($q)) continue;

            unset($q['correctAnswer'], $q['explanation']);
            $questionsById[$q['id']] = $q;
        }

        if (!empty($order)) {
            $ordered = [];
            foreach ($order as $qId) {
                if (isset($questionsById[$qId])) $ordered[] = $questionsById[$qId];
            }
            return $ordered;
        }

        return array_values($questionsById);
    }

    private function calculateRemainingSeconds(Attempt $attempt, ?array $examConf = null): ?int
    {
        if ($examConf === null) {
            $raw      = $attempt->exam->json_schema;
            $schema   = $this->resolveSchema($raw);
            $examConf = $schema['exam'] ?? [];
        }

        $timeLimitMinutes = $examConf['timeLimitMinutes'] ?? null;

        if (!$timeLimitMinutes)    return null;
        if (!$attempt->started_at) return $timeLimitMinutes * 60;

        return max(0, ($timeLimitMinutes * 60) - now()->diffInSeconds($attempt->started_at));
    }

    private function shuffleArray(array $array): array
    {
        shuffle($array);
        return $array;
    }
}