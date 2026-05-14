<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla attempts: registra cada intento de un usuario sobre un examen.
     * Maneja la máquina de estados (pending, active, completed, abandoned).
     */
    public function up(): void
    {
        Schema::create('attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['pending', 'active', 'completed', 'abandoned'])->default('pending');
            $table->timestamp('started_at')->nullable();      // Momento en que se muestra la 1ª pregunta
            $table->timestamp('last_seen_at')->nullable();    // Heartbeat para detectar abandono
            $table->timestamp('completed_at')->nullable();    // Fecha de finalización
            $table->json('question_order')->nullable();       // Orden aleatorio de IDs de preguntas
            $table->string('device_hint')->nullable();        // Información del dispositivo (opcional)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attempts');
    }
};