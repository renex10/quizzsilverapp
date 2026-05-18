<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TopicQuizAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'topic_id', 'user_id', 'session_token', 'exam_id', 'percentage',
        'total_questions', 'total_correct', 'total_wrong',
        'answers', 'question_ids', 'completed_at'
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
        'answers' => 'array',
        'question_ids' => 'array',
        'completed_at' => 'datetime',
    ];

    // Relaciones
    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    // Helper: determinar si aprobó el mini quiz (usando passingScore por defecto 60%)
    public function passed(float $passingScore = 60.0): bool
    {
        return $this->percentage >= $passingScore;
    }
}