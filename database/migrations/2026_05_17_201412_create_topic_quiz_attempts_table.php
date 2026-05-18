<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tabla: topic_quiz_attempts
     * Propósito: Almacena los intentos de los mini quizzes asociados a un topic.
     * Diferencia de exam_results: Esta tabla es para quizzes cortos (5 preguntas)
     * que NO afectan la certificación final ni las estadísticas principales.
     * Permite usuarios anónimos (session_token) y autenticados (user_id).
     */
    public function up(): void
    {
        Schema::create('topic_quiz_attempts', function (Blueprint $table) {
            $table->id();
            
            // Relación con el topic (obligatoria)
            $table->foreignId('topic_id')->constrained('topics')->cascadeOnDelete();
            
            // Usuario opcional (NULL para visitantes anónimos)
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Token de sesión para anónimos (ej. UUID generado en frontend)
            $table->string('session_token', 100)->nullable();
            
            // Examen del cual se extrajeron las preguntas (de la serie)
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            
            // Resultados del quiz
            $table->decimal('percentage', 5, 2)->default(0);
            $table->unsignedSmallInteger('total_questions')->default(0);
            $table->unsignedSmallInteger('total_correct')->default(0);
            $table->unsignedSmallInteger('total_wrong')->default(0);
            
            // Respuestas detalladas (JSON) para análisis y retroalimentación
            $table->json('answers')->nullable();  // [{ questionId, userAnswer, correctAnswer, isCorrect }]
            
            // IDs de las preguntas mostradas (orden aleatorio)
            $table->json('question_ids')->nullable();
            
            // Marca temporal de finalización
            $table->timestamp('completed_at')->nullable();
            
            $table->timestamps();
            
            // Índices para búsquedas rápidas
            $table->index(['topic_id', 'user_id']);
            $table->index(['topic_id', 'session_token']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('topic_quiz_attempts');
    }
};