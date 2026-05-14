<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla series: agrupador temático de evaluaciones.
     */
    public function up(): void
    {
        Schema::create('series', function (Blueprint $table) {
            $table->id();
            $table->string('title');                // Ej: "Licencia de Conducir Clase B"
            $table->text('description')->nullable(); // Descripción opcional
            $table->string('domain');               // Ej: "Seguridad Vial"
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('series');
    }
};