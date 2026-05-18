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
        Schema::create('lesson_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('series_id')->nullable()->constrained('series')->nullOnDelete();
            $table->foreignId('topic_id')->nullable()->constrained('topics')->nullOnDelete();
            $table->foreignId('imported_by')->constrained('users')->cascadeOnDelete();
            $table->string('filename');
            $table->enum('type', ['markdown', 'json']);
            $table->enum('status', ['pending', 'processing', 'success', 'failed'])->default('pending');
            $table->json('validation_errors')->nullable();
            $table->unsignedSmallInteger('topics_created')->default(0);
            $table->unsignedSmallInteger('lessons_created')->default(0);
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_imports');
    }
};