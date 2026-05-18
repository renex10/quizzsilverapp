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
                'title'            => 'Licencia de Conducir Clase B',
                'description'      => 'Evaluaciones oficiales para obtener la licencia de conducir clase B (automóviles).',
                'long_description' => 'Prepárate para el examen teórico de licencia de conducir Clase B con evaluaciones que cubren normativa vial, señalética, conducción segura, mecánica básica y primeros auxilios. Contenido actualizado según la ley de tránsito vigente.',
                'domain'           => 'Seguridad Vial',
                'difficulty'       => 'intermedio',
                'estimated_hours'  => 8.5,
                'is_featured'      => true,
                'published_at'     => now(),
                // slug se auto-genera desde el modelo Series
            ],
            [
                'title'            => 'Álgebra',
                'description'      => 'Evaluaciones de álgebra básica y avanzada, desde ecuaciones lineales hasta matrices.',
                'long_description' => 'Domina el álgebra desde cero. Ecuaciones lineales, cuadráticas, sistemas de ecuaciones y funciones. Ideal para estudiantes de enseñanza media y primer año universitario.',
                'domain'           => 'Matemáticas',
                'difficulty'       => 'basico',
                'estimated_hours'  => 5.0,
                'is_featured'      => false,
                'published_at'     => now(),
            ],
            [
                'title'            => 'Anatomía Humana',
                'description'      => 'Conocimientos del cuerpo humano para estudiantes de ciencias de la salud.',
                'long_description' => 'Estudia los sistemas del cuerpo humano: óseo, muscular, nervioso, circulatorio y más. Orientado a estudiantes de enfermería, medicina y carreras afines.',
                'domain'           => 'Salud',
                'difficulty'       => 'avanzado',
                'estimated_hours'  => 12.0,
                'is_featured'      => false,
                'published_at'     => null, // aún no publicada
            ],
        ];

        foreach ($series as $s) {
            Series::create($s);
        }
    }
}