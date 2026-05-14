<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'attempt_id', 'exam_id', 'user_id', 'score', 'percentage',
        'passed', 'time_used_seconds', 'total_correct', 'total_wrong',
        'detail',
    ];

    protected $casts = [
        'detail' => 'array',
        'passed' => 'boolean',
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