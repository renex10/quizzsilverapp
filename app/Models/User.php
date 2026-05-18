<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Atributos asignables masivamente.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'is_active',
    ];

    /**
     * Atributos ocultos para serialización.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Atributos casteados automáticamente.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /*
    | ----------------------------------------------------------------------
    | RELACIONES
    | ----------------------------------------------------------------------
    */

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function attempts()
    {
        return $this->hasMany(Attempt::class);
    }

    public function examResults()
    {
        return $this->hasMany(ExamResult::class);
    }

    /**
     * Relación con los intentos de mini quizzes (topics).
     * Se agrega en la Sesión 4 para la capa de analítica.
     */
    public function topicQuizAttempts()
    {
        return $this->hasMany(TopicQuizAttempt::class);
    }

    /*
    | ----------------------------------------------------------------------
    | MÉTODOS DE UTILIDAD
    | ----------------------------------------------------------------------
    */

    public function isAdmin(): bool
    {
        return $this->role && $this->role->name === 'admin';
    }

    public function isStudent(): bool
    {
        return $this->role && $this->role->name === 'student';
    }
}