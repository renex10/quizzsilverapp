<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasImages;
use Illuminate\Support\Str;

class Series extends Model
{
    use HasFactory, HasImages;

    protected $fillable = [
        'title', 'description', 'domain',
        'slug', 'long_description',
        'difficulty', 'estimated_hours',
        'is_featured', 'published_at',
        'cover_image',
    ];

    protected $casts = [
        'is_featured'     => 'boolean',
        'published_at'    => 'datetime',
        'estimated_hours' => 'decimal:1',
    ];

    // ─── Relaciones ───────────────────────────────────────────────────────────

    /** Exámenes de la serie */
    public function exams()
    {
        return $this->hasMany(Exam::class);
    }

    /**
     * Topics de la serie, ordenados por su campo order.
     * Requerido por TopicController y TopicSortableList.
     */
    public function topics()
    {
        return $this->hasMany(Topic::class)->orderBy('order');
    }

    /** Historial de importaciones de contenido */
    public function lessonImports()
    {
        return $this->hasMany(LessonImport::class);
    }

    // ─── Boot: auto-generar slug ──────────────────────────────────────────────

    protected static function booted()
    {
        static::creating(function ($series) {
            if (empty($series->slug)) {
                $base  = Str::slug($series->title);
                $slug  = $base;
                $count = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $base . '-' . $count++;
                }
                $series->slug = $slug;
            }
        });
    }
}