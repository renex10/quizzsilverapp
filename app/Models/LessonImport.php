<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'series_id', 'topic_id', 'imported_by', 'filename', 'type',
        'status', 'validation_errors', 'topics_created', 'lessons_created', 'imported_at'
    ];

    protected $casts = [
        'validation_errors' => 'array',
        'imported_at' => 'datetime',
        'topics_created' => 'integer',
        'lessons_created' => 'integer',
    ];

    // Relaciones
    public function series()
    {
        return $this->belongsTo(Series::class);
    }

    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }

    public function importer()
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    // Helpers de estado
    public function markAsSuccess(int $topics = 0, int $lessons = 0): void
    {
        $this->update([
            'status' => 'success',
            'topics_created' => $topics,
            'lessons_created' => $lessons,
            'imported_at' => now(),
        ]);
    }

    public function markAsFailed(array $errors): void
    {
        $this->update([
            'status' => 'failed',
            'validation_errors' => $errors,
            'imported_at' => now(),
        ]);
    }
}
