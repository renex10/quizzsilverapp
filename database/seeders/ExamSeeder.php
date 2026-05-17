<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Exam;
use App\Models\Series;

/**
 * CORRECCIÓN: eliminado type 'mixed' que no existe en el sistema.
 * La versión 2022 usa type 'single_choice' ya que el motor evalúa
 * por pregunta individual — cada pregunta declara su propio type.
 * El campo type del examen indica el tipo predominante / principal.
 */
class ExamSeeder extends Seeder
{
    public function run(): void
    {
        $licencia = Series::where('title', 'Licencia de Conducir Clase B')->first();
        $algebra  = Series::where('title', 'Álgebra')->first();

        if ($licencia) {
            // Versión 2018 — single_choice puro
            Exam::create([
                'series_id'   => $licencia->id,
                'title'       => 'Licencia Clase B - Versión 2018',
                'description' => 'Examen oficial de 35 preguntas de opción única. Incluye señaléticas y normas.',
                'version'     => '2018',
                'type'        => 'single_choice',
                'json_schema' => [
                    'exam' => [
                        'id'               => 'lic_b_2018',
                        'title'            => 'Licencia Clase B - Versión 2018',
                        'version'          => '2018',
                        'passingScore'     => 80,
                        'timeLimitMinutes' => 45,
                        'shuffleQuestions' => true,
                    ],
                    'questions' => [
                        [
                            'id'            => 'Q1',
                            'type'          => 'single_choice',
                            'question'      => '¿Cuál es la velocidad máxima en una zona urbana?',
                            'options'       => [
                                ['id' => 'A', 'text' => '30 km/h'],
                                ['id' => 'B', 'text' => '40 km/h'],
                                ['id' => 'C', 'text' => '50 km/h'],
                                ['id' => 'D', 'text' => '60 km/h'],
                            ],
                            'correctAnswer' => 'B',
                            'category'      => 'Normas',
                            'difficulty'    => 'baja',
                            'critical'      => true,
                            'explanation'   => 'Según la ley de tránsito, en zonas urbanas la velocidad máxima es de 40 km/h.',
                        ],
                        [
                            'id'            => 'Q2',
                            'type'          => 'single_choice',
                            'question'      => '¿Qué significa una señal de tránsito de forma triangular con borde rojo?',
                            'options'       => [
                                ['id' => 'A', 'text' => 'Advertencia de peligro'],
                                ['id' => 'B', 'text' => 'Prohibición'],
                                ['id' => 'C', 'text' => 'Obligación'],
                                ['id' => 'D', 'text' => 'Información'],
                            ],
                            'correctAnswer' => 'A',
                            'category'      => 'Señalética',
                            'difficulty'    => 'media',
                            'critical'      => false,
                            'explanation'   => 'Las señales triangulares con borde rojo indican advertencia de peligro.',
                        ],
                    ],
                ],
                'status' => 'published',
            ]);

            // CORRECCIÓN: versión 2022 era 'mixed' — ahora 'single_choice'
            // Las preguntas true_false dentro del JSON son válidas porque el
            // motor evalúa por question['type'], no por exam['type'].
            // El campo type del examen es informativo/de agrupación.
            // Si en el futuro se quiere evaluar exámenes mixtos formalmente,
            // se debe crear el tipo 'mixed' en el sistema completo.
            Exam::create([
                'series_id'   => $licencia->id,
                'title'       => 'Licencia Clase B - Versión 2022',
                'description' => 'Nueva versión con preguntas de opción única y verdadero/falso.',
                'version'     => '2022',
                'type'        => 'single_choice',
                'json_schema' => [
                    'exam' => [
                        'id'               => 'lic_b_2022',
                        'title'            => 'Licencia Clase B - Versión 2022',
                        'version'          => '2022',
                        'passingScore'     => 80,
                        'timeLimitMinutes' => 50,
                        'shuffleQuestions' => true,
                    ],
                    'questions' => [
                        [
                            'id'            => 'Q1',
                            'type'          => 'single_choice',
                            'question'      => '¿Cuál es la distancia de seguridad mínima recomendada?',
                            'options'       => [
                                ['id' => 'A', 'text' => '1 segundo'],
                                ['id' => 'B', 'text' => '2 segundos'],
                                ['id' => 'C', 'text' => '3 segundos'],
                            ],
                            'correctAnswer' => 'B',
                            'category'      => 'Conducción segura',
                            'difficulty'    => 'media',
                            'critical'      => true,
                            'explanation'   => 'Se recomienda al menos 2 segundos de distancia con el vehículo delantero.',
                        ],
                        [
                            'id'            => 'Q2',
                            'type'          => 'single_choice',
                            'question'      => '¿Está permitido adelantar en una curva sin visibilidad si se hace con precaución?',
                            'options'       => [
                                ['id' => 'A', 'text' => 'Sí, con precaución'],
                                ['id' => 'B', 'text' => 'No, está prohibido'],
                                ['id' => 'C', 'text' => 'Solo de día'],
                            ],
                            'correctAnswer' => 'B',
                            'category'      => 'Normas',
                            'difficulty'    => 'media',
                            'critical'      => true,
                            'explanation'   => 'Está totalmente prohibido adelantar en curvas sin visibilidad por el alto riesgo de colisión.',
                        ],
                    ],
                ],
                'status' => 'draft',
            ]);
        }

        if ($algebra) {
            Exam::create([
                'series_id'   => $algebra->id,
                'title'       => 'Álgebra I',
                'description' => 'Ecuaciones lineales y cuadráticas.',
                'version'     => '1.0',
                'type'        => 'multiple_choice',
                'json_schema' => [
                    'exam' => [
                        'id'               => 'alg1',
                        'title'            => 'Álgebra I',
                        'version'          => '1.0',
                        'passingScore'     => 70,
                        'timeLimitMinutes' => 60,
                        'shuffleQuestions' => false,
                        'allowPartialScore' => true,
                    ],
                    'questions' => [
                        [
                            'id'            => 'Q1',
                            'type'          => 'multiple_choice',
                            'question'      => '¿Cuáles son las soluciones de la ecuación x² - 5x + 6 = 0?',
                            'options'       => [
                                ['id' => 'A', 'text' => 'x = 2'],
                                ['id' => 'B', 'text' => 'x = 3'],
                                ['id' => 'C', 'text' => 'x = -2'],
                                ['id' => 'D', 'text' => 'x = -3'],
                            ],
                            'correctAnswer' => ['A', 'B'],
                            'category'      => 'Ecuaciones',
                            'difficulty'    => 'baja',
                            'critical'      => false,
                            'explanation'   => 'Factorizando (x-2)(x-3)=0, las soluciones son x=2 y x=3.',
                        ],
                    ],
                ],
                'status' => 'published',
            ]);
        }
    }
}