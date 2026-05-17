<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * MIGRACIÓN DESACTIVADA.
     *
     * role_id e is_active ya fueron incluidos directamente en
     * 0001_01_01_000000_create_users_table.php.
     * Esta migración se mantiene vacía para no romper el historial.
     */
    public function up(): void
    {
        // vacío intencionalmente
    }

    public function down(): void
    {
        // vacío intencionalmente
    }
};