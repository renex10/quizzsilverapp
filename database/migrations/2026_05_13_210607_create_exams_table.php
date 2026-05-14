<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla exams: cada evaluación concreta (versión) dentro de una serie.
     */
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('series_id')->constrained('series')->onDelete('cascade'); // Pertenece a una serie
            $table->string('title');                // Título de la evaluación
            $table->text('description')->nullable();
            $table->string('version');              // Versión o identificador (2018, Algebra I, etc.)
            $table->string('type');                 // single_choice, multiple_choice, true_false, ordering, matching
            $table->json('json_schema');            // El JSON completo generado por IA (contrato)
            $table->enum('status', ['draft', 'published'])->default('draft'); // Borrador o publicado
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};