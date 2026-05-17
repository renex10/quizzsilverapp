<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'attempt_id',
        'exam_id',
        'user_id',
        'score',
        'percentage',
        'passed',
        'time_used_seconds',
        'total_correct',
        'total_wrong',
        'detail',
    ];

    /**
     * CORRECCIÓN: 'decimal:2' no es un cast de lectura nativo en Laravel —
     * puede devolver string en lugar de float al leer desde la DB.
     * Usar 'float' garantiza que score y percentage sean siempre numéricos.
     */
    protected $casts = [
        'passed'     => 'boolean',
        'detail'     => 'array',
        'score'      => 'float',
        'percentage' => 'float',
    ];

    public function attempt()
    {
        return $this->belongsTo(Attempt::class);
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}