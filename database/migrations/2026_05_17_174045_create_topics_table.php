<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('series_id')->constrained('series')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('icon', 10)->nullable();               // Emoji: "🚦"
            $table->string('color', 20)->default('#6366f1');
            $table->unsignedSmallInteger('order')->default(0);
            $table->boolean('is_public')->default(false);
            $table->string('exam_category')->nullable();          // PUENTE con preguntas del examen
            $table->timestamps();

            $table->unique(['series_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('topics');
    }
};