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

    /**
     * Un usuario pertenece a un rol.
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Un usuario tiene muchos intentos de examen.
     */
    public function attempts()
    {
        return $this->hasMany(Attempt::class);
    }

    /**
     * Un usuario tiene muchos resultados (denormalizado para estadísticas rápidas).
     */
    public function examResults()
    {
        return $this->hasMany(ExamResult::class);
    }

    /*
    | ----------------------------------------------------------------------
    | MÉTODOS DE UTILIDAD
    | ----------------------------------------------------------------------
    */

    /**
     * Determina si el usuario es administrador.
     */
    public function isAdmin(): bool
    {
        return $this->role && $this->role->name === 'admin';
    }

    /**
     * Determina si el usuario es estudiante.
     */
    public function isStudent(): bool
    {
        return $this->role && $this->role->name === 'student';
    }

    
}