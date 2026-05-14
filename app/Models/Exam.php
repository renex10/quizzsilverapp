<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = [
        'series_id', 'title', 'description', 'version',
        'type', 'json_schema', 'status',
    ];

    protected $casts = [
        'json_schema' => 'array', // convierte automáticamente a/desde array
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