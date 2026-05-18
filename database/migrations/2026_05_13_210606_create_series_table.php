<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla series: agrupador temático de evaluaciones.
     *
     * CORRECCIÓN: eliminados todos los ->after() — son inválidos en Schema::create().
     * ->after() solo funciona en Schema::table() (modificar tabla existente).
     * En Schema::create(), el orden de columnas lo define el orden del código.
     *
     * Incluye todos los campos extendidos directamente para evitar
     * tener una migración separada add_columns_to_series_table.
     */
    public function up(): void
    {
        Schema::create('series', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique()->nullable();
            $table->text('description')->nullable();
            $table->string('cover_image', 500)->nullable();
            $table->text('long_description')->nullable();
            $table->string('domain');
            $table->enum('difficulty', ['basico', 'intermedio', 'avanzado'])->default('basico');
            $table->decimal('estimated_hours', 4, 1)->nullable();
            $table->boolean('is_featured')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('series');
    }
};