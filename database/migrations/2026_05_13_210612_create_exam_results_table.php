<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla exam_results: resultados inmutables de un intento completado.
     */
    public function up(): void
    {
        Schema::create('exam_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('attempts')->onDelete('cascade');
            $table->foreignId('exam_id')->constrained('exams')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('score', 5, 2)->nullable();        // Puntaje obtenido (ej. 28.5)
            $table->decimal('percentage', 5, 2)->nullable();   // Porcentaje 0-100
            $table->boolean('passed')->default(false);         // Aprobado o no
            $table->integer('time_used_seconds')->nullable();  // Tiempo real empleado
            $table->integer('total_correct')->default(0);      // Número de correctas
            $table->integer('total_wrong')->default(0);        // Número de incorrectas
            $table->json('detail')->nullable();                // Desglose: por categoría, dificultad, lista de errores
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_results');
    }
};