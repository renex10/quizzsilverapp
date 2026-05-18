<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla exams: cada evaluación concreta (versión) dentro de una serie.
     *
     * CORRECCIÓN: eliminado ->after('status') — inválido en Schema::create().
     * ->after() solo funciona en Schema::table() al modificar tablas existentes.
     */
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('series_id')->constrained('series')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('version');
            $table->string('type'); // single_choice, multiple_choice, true_false, ordering, matching
            $table->json('json_schema');
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->boolean('is_final_exam')->default(false); // sin ->after()
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};