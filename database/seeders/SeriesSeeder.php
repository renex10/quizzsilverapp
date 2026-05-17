<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Series;

class SeriesSeeder extends Seeder
{
    public function run(): void
    {
        $series = [
            [
                'title' => 'Licencia de Conducir Clase B',
                'description' => 'Evaluaciones oficiales para obtener la licencia de conducir clase B (automóviles).',
                'domain' => 'Seguridad Vial',
            ],
            [
                'title' => 'Álgebra',
                'description' => 'Evaluaciones de álgebra básica y avanzada, desde ecuaciones lineales hasta matrices.',
                'domain' => 'Matemáticas',
            ],
            [
                'title' => 'Anatomía Humana',
                'description' => 'Conocimientos del cuerpo humano para estudiantes de ciencias de la salud.',
                'domain' => 'Salud',
            ],
        ];

        foreach ($series as $s) {
            Series::create($s);
        }
    }
}