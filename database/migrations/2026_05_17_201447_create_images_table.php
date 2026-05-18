<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tabla: images
     * Propósito: Sistema centralizado de imágenes polimórfico.
     * Puede asociarse a cualquier modelo (Series, Topic, Lesson, Exam, etc.)
     * mediante el campo morphs 'imageable'.
     * Reemplaza el campo 'cover_image' legacy de series y provee manejo
     * unificado de imágenes con soporte para múltiples contextos.
     */
    public function up(): void
    {
        Schema::create('images', function (Blueprint $table) {
            $table->id();

            // Relación polimórfica (imageable_type, imageable_id)
            // Ejemplo: imageable_type = 'App\Models\Series', imageable_id = 1
            $table->morphs('imageable');

            // Información del archivo
            $table->string('filename');          // Nombre original o generado
            $table->string('path');              // Ruta relativa: "images/series/1/cover_xxx.webp"
            $table->string('disk')->default('public');   // 'public' o 's3'
            $table->string('mime_type', 50);     // "image/webp", "image/jpeg"
            $table->unsignedInteger('size');     // Tamaño en bytes

            // Dimensiones (opcional, para optimización frontend)
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();

            // Contexto de uso: 'cover', 'thumbnail', 'content', 'avatar'
            $table->string('context')->default('cover');

            // Texto alternativo para accesibilidad
            $table->string('alt_text')->nullable();

            // Orden para galerías (cuando haya múltiples imágenes)
            $table->unsignedSmallInteger('order')->default(0);

            $table->timestamps();

            // Índices recomendados para búsquedas frecuentes
            $table->index(['imageable_type', 'imageable_id', 'context']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};