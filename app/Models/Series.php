<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasImages;                 // ← NUEVO
use Illuminate\Support\Str;               // ← para slug

class Series extends Model
{
    use HasFactory, HasImages;             // ← añadir HasImages

    protected $fillable = [
        'title', 'description', 'domain',
        'slug', 'long_description',        // ← nuevos campos
        'difficulty', 'estimated_hours',
        'is_featured', 'published_at',
        'cover_image'                      // ← legacy, opcional
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
        'estimated_hours' => 'decimal:1',
    ];

    // Relación existente
    public function exams()
    {
        return $this->hasMany(Exam::class);
    }

    // Auto-generar slug al crear/actualizar (si no está presente)
    protected static function booted()
    {
        static::creating(function ($series) {
            if (empty($series->slug)) {
                $series->slug = Str::slug($series->title);
                // Asegurar unicidad
                $original = $series->slug;
                $count = 1;
                while (static::where('slug', $series->slug)->exists()) {
                    $series->slug = $original . '-' . $count++;
                }
            }
        });
    }
}