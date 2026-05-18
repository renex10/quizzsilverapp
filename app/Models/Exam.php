<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasImages;   // ← Trait para imágenes polimórficas

class Exam extends Model
{
    use HasFactory, HasImages;

    protected $fillable = [
        'series_id',
        'title',
        'description',
        'version',
        'type',
        'json_schema',
        'status',
        'is_final_exam',           // ← NUEVO: indica si es el examen final de la serie
    ];

    protected $casts = [
        'json_schema' => 'array',  // convierte automáticamente a/desde array
        'is_final_exam' => 'boolean',
    ];

    public function series()
    {
        return $this->belongsTo(Series::class);
    }

    public function attempts()
    {
        return $this->hasMany(Attempt::class);
    }

    public function examResults()
    {
        return $this->hasMany(ExamResult::class);
    }
}