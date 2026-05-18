<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasImages;
use Illuminate\Support\Str;

class Topic extends Model
{
    use HasFactory, HasImages;

    protected $fillable = [
        'series_id', 'title', 'slug', 'description', 'icon',
        'color', 'order', 'is_public', 'exam_category'
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'order' => 'integer',
    ];

    // Relaciones
    public function series()
    {
        return $this->belongsTo(Series::class);
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class)->orderBy('order');
    }

    public function quizAttempts()
    {
        return $this->hasMany(TopicQuizAttempt::class);
    }

    public function lessonImports()
    {
        return $this->hasMany(LessonImport::class);
    }

    // Helper: Obtener preguntas del examen de la serie para mini quiz
    public function getQuizQuestions(int $limit = 5): array
    {
        $series = $this->series;
        $exam = $series->exams()->where('is_final_exam', true)->first();
        if (!$exam || empty($exam->json_schema['questions'])) {
            return [];
        }

        $filtered = array_filter($exam->json_schema['questions'], function ($q) {
            return isset($q['category']) && $q['category'] === $this->exam_category;
        });

        // Re-indexar y mezclar
        $filtered = array_values($filtered);
        shuffle($filtered);

        return array_slice($filtered, 0, $limit);
    }

    // Boot para auto-generar slug
    protected static function booted()
    {
        static::creating(function ($topic) {
            if (empty($topic->slug)) {
                $slug = Str::slug($topic->title);
                $original = $slug;
                $count = 1;
                while (static::where('series_id', $topic->series_id)->where('slug', $slug)->exists()) {
                    $slug = $original . '-' . $count++;
                }
                $topic->slug = $slug;
            }
        });
    }
}