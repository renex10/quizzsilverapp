<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Series;
use App\Models\Topic;

class TopicSeeder extends Seeder
{
    public function run(): void
    {
        $licencia = Series::where('title', 'Licencia de Conducir Clase B')->first();

        if (!$licencia) return;

        $topics = [
            [
                'series_id'     => $licencia->id,
                'title'         => 'Ley de Tránsito y Normativa Vial',
                'slug'          => 'ley-de-transito-y-normativa-vial',
                'description'   => 'Derechos y deberes del conductor, documentación obligatoria, límites de velocidad y prioridades de paso.',
                'icon'          => '📋',
                'color'         => '#6366f1',
                'order'         => 1,
                'is_public'     => true,
                'exam_category' => 'Normas',
            ],
            [
                'series_id'     => $licencia->id,
                'title'         => 'Señales de Tránsito',
                'slug'          => 'senales-de-transito',
                'description'   => 'Señales reglamentarias, preventivas e informativas. Forma, color y significado de cada tipo.',
                'icon'          => '🚦',
                'color'         => '#f59e0b',
                'order'         => 2,
                'is_public'     => true,
                'exam_category' => 'Señalética',
            ],
            [
                'series_id'     => $licencia->id,
                'title'         => 'Conducción Segura',
                'slug'          => 'conduccion-segura',
                'description'   => 'Conducción defensiva, factores de riesgo (alcohol, fatiga, celular) y usuarios vulnerables.',
                'icon'          => '🛡️',
                'color'         => '#10b981',
                'order'         => 3,
                'is_public'     => true,
                'exam_category' => 'Conducción segura',
            ],
            [
                'series_id'     => $licencia->id,
                'title'         => 'Mecánica Básica del Vehículo',
                'slug'          => 'mecanica-basica-del-vehiculo',
                'description'   => 'Frenos, neumáticos, aceite, refrigerante, batería y tablero de instrumentos.',
                'icon'          => '🔧',
                'color'         => '#8b5cf6',
                'order'         => 4,
                'is_public'     => false,
                'exam_category' => 'Mecánica básica',
            ],
            [
                'series_id'     => $licencia->id,
                'title'         => 'Primeros Auxilios y Emergencias',
                'slug'          => 'primeros-auxilios-y-emergencias',
                'description'   => 'Protocolo PAS, llamadas de emergencia, triángulos de seguridad y atención a heridos.',
                'icon'          => '🚑',
                'color'         => '#ef4444',
                'order'         => 5,
                'is_public'     => false,
                'exam_category' => 'Primeros auxilios',
            ],
        ];

        foreach ($topics as $data) {
            Topic::create($data);
        }
    }
}