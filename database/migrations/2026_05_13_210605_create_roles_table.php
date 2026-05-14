<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tabla de roles: admin y student.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();                           // Clave primaria autoincremental
            $table->string('name')->unique();       // 'admin' o 'student', único
            $table->timestamps();                   // created_at, updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};