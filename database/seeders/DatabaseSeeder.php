<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Llama a los seeders en el orden correcto (respetando dependencias)
        $this->call([
            RoleSeeder::class,          // Primero crea los roles (admin, student)
            AdminUserSeeder::class,     // Luego crea el usuario administrador
            SeriesSeeder::class,        // Ahora series de ejemplo
            ExamSeeder::class,          // Finalmente exámenes de ejemplo (requieren series)
        ]);
    }
}