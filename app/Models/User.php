<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role_id',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

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

    public function isAdmin()
    {
        return $this->role->name === 'admin';
    }

    public function isStudent()
    {
        return $this->role->name === 'student';
    }
}