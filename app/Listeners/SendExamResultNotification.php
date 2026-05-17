<?php

namespace App\Listeners;

use App\Events\ExamCompleted;
use App\Mail\ExamResultMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/**
 * Envía un correo electrónico al estudiante con el resumen de resultados.
 * Utiliza el Mailable ExamResultMail.
 */
class SendExamResultNotification
{
    /**
     * Handle the event.
     */
    public function handle(ExamCompleted $event): void
    {
        $attempt = $event->attempt;
        $user = $attempt->user;
        $exam = $attempt->exam;
        $result = $event->examResult;

        try {
            Mail::to($user->email)->send(new ExamResultMail($user, $exam, $attempt, $result));
        } catch (\Exception $e) {
            // Registrar error pero no detener el flujo
            Log::error('Error al enviar correo de resultado de examen: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'attempt_id' => $attempt->id,
            ]);
        }
    }
}