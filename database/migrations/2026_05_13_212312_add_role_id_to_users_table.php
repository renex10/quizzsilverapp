<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega la columna role_id a la tabla users, referenciando roles.id.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Añade role_id después del campo 'id' (opcional) y con clave foránea
            $table->foreignId('role_id')->after('id')->constrained('roles');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });
    }
};