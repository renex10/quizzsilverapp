<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla attempt_answers: respuestas del usuario a cada pregunta de un intento.
     */
    public function up(): void
    {
        Schema::create('attempt_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('attempts')->onDelete('cascade');
            $table->string('question_id');          // ID de la pregunta (ej. "Q1", "Q2")
            $table->json('user_answer');            // Respuesta en formato según tipo de pregunta
            $table->timestamp('answered_at')->nullable(); // Cuándo respondió
            $table->timestamps();

            // Índice compuesto para búsquedas rápidas por intento y pregunta
            $table->index(['attempt_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attempt_answers');
    }
};