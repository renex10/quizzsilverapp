<?php

namespace App\Events;

use App\Models\Attempt;
use App\Models\ExamResult;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento disparado cuando un estudiante completa un intento de examen.
 * El listener se encargará de enviar la notificación por correo.
 */
class ExamCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Attempt $attempt;
    public ExamResult $examResult;

    /**
     * Create a new event instance.
     */
    public function __construct(Attempt $attempt, ExamResult $examResult)
    {
        $this->attempt = $attempt;
        $this->examResult = $examResult;
    }
}