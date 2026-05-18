<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasImages;
use Illuminate\Support\Str;

class Lesson extends Model
{
    use HasFactory, HasImages;

    protected $fillable = [
        'topic_id', 'title', 'slug', 'content', 'order',
        'duration_minutes', 'is_preview'
    ];

    protected $casts = [
        'is_preview' => 'boolean',
        'duration_minutes' => 'integer',
        'order' => 'integer',
    ];

    // Relaciones
    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }

    // Helper: Obtener un extracto del contenido Markdown (primeros N caracteres)
    public function excerpt(int $length = 150): string
    {
        $text = strip_tags($this->content);
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        return mb_substr($text, 0, $length) . '…';
    }

    // Boot para auto-generar slug
    protected static function booted()
    {
        static::creating(function ($lesson) {
            if (empty($lesson->slug)) {
                $slug = Str::slug($lesson->title);
                $original = $slug;
                $count = 1;
                while (static::where('topic_id', $lesson->topic_id)->where('slug', $slug)->exists()) {
                    $slug = $original . '-' . $count++;
                }
                $lesson->slug = $slug;
            }
        });
    }
}