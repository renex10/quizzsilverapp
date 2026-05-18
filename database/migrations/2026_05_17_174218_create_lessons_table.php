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
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained('topics')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->longText('content');                     // Markdown
            $table->unsignedSmallInteger('order')->default(0);
            $table->unsignedSmallInteger('duration_minutes')->default(5);
            $table->boolean('is_preview')->default(false);    // Visible sin login
            $table->timestamps();

            $table->unique(['topic_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};