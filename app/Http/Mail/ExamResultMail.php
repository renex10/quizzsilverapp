<?php

namespace App\Mail;

use App\Models\Attempt;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mailable con el resumen de resultados del examen.
 * Se envía al estudiante después de completar un intento.
 * Incluye: nombre del examen, serie, puntaje, porcentaje, aprobación, total correctas/incorrectas,
 * y un enlace para ver el detalle completo en la plataforma.
 */
class ExamResultMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public Exam $exam;
    public Attempt $attempt;
    public ExamResult $result;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, Exam $exam, Attempt $attempt, ExamResult $result)
    {
        $this->user = $user;
        $this->exam = $exam;
        $this->attempt = $attempt;
        $this->result = $result;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Resultado de examen: ' . $this->exam->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Construir enlace al detalle completo en la plataforma
        $detailUrl = route('student.results.show', $this->attempt->id);

        return new Content(
            markdown: 'emails.exam-result', // Usaremos una vista markdown (crear después)
            with: [
                'userName' => $this->user->name,
                'examTitle' => $this->exam->title,
                'seriesTitle' => $this->exam->series->title,
                'percentage' => $this->result->percentage,
                'score' => $this->result->score,
                'passed' => $this->result->passed,
                'totalCorrect' => $this->result->total_correct,
                'totalWrong' => $this->result->total_wrong,
                'detailUrl' => $detailUrl,
            ],
        );
    }
}