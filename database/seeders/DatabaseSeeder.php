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
                      RoleSeeder::class,       // 1° — roles del sistema
            AdminUserSeeder::class,  // 2° — usuario admin de prueba
            UserSeeder::class,       // 3° — usuario student de prueba
            SeriesSeeder::class,     // 4° — series temáticas
            ExamSeeder::class,       // 5° — exámenes (requiere series)
            TopicSeeder::class,      // 6° — topics por serie (requiere series y exams)
            LessonSeeder::class,     // 7° — lecciones por topic (requiere topics)

        ]);
    }
}