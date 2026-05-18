<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Exam;
use App\Models\Series;

class ExamSeeder extends Seeder
{
    public function run(): void
    {
        $licencia = Series::where('title', 'Licencia de Conducir Clase B')->first();
        $algebra  = Series::where('title', 'Álgebra')->first();

        if ($licencia) {

            // Versión 2018 — examen de práctica (NO es el examen madre)
            Exam::create([
                'series_id'     => $licencia->id,
                'title'         => 'Licencia Clase B - Versión 2018',
                'description'   => 'Examen oficial de 35 preguntas de opción única.',
                'version'       => '2018',
                'type'          => 'single_choice',
                'is_final_exam' => false,
                'status'        => 'published',
                'json_schema'   => [
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
                            'explanation'   => 'En zonas urbanas la velocidad máxima es 40 km/h.',
                        ],
                        [
                            'id'            => 'Q2',
                            'type'          => 'single_choice',
                            'question'      => '¿Qué significa una señal triangular con borde rojo?',
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
            ]);

            // Examen madre 2022 — is_final_exam = true
            // Tiene preguntas de TODAS las categorías: Normas, Señalética,
            // Conducción segura, Mecánica básica, Primeros auxilios.
            // Estas categorías deben coincidir con topic.exam_category en TopicSeeder.
            Exam::create([
                'series_id'     => $licencia->id,
                'title'         => 'Licencia Clase B - Examen Completo 2022',
                'description'   => 'Examen madre con preguntas de todos los temas. Replica el examen oficial vigente.',
                'version'       => '2022',
                'type'          => 'single_choice',
                'is_final_exam' => true,
                'status'        => 'published',
                'json_schema'   => [
                    'exam' => [
                        'id'               => 'lic_b_2022_final',
                        'title'            => 'Licencia Clase B - Examen Completo 2022',
                        'version'          => '2022',
                        'passingScore'     => 80,
                        'timeLimitMinutes' => 50,
                        'shuffleQuestions' => true,
                    ],
                    'questions' => [
                        // ── Normas ──────────────────────────────────────────
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
                            'category'      => 'Normas',
                            'difficulty'    => 'media',
                            'critical'      => true,
                            'explanation'   => 'Se recomienda al menos 2 segundos de distancia con el vehículo delantero.',
                        ],
                        [
                            'id'            => 'Q2',
                            'type'          => 'single_choice',
                            'question'      => '¿Está permitido adelantar en una curva sin visibilidad?',
                            'options'       => [
                                ['id' => 'A', 'text' => 'Sí, con precaución'],
                                ['id' => 'B', 'text' => 'No, está prohibido'],
                                ['id' => 'C', 'text' => 'Solo de día'],
                            ],
                            'correctAnswer' => 'B',
                            'category'      => 'Normas',
                            'difficulty'    => 'media',
                            'critical'      => true,
                            'explanation'   => 'Adelantar en curvas sin visibilidad está totalmente prohibido.',
                        ],
                        [
                            'id'            => 'Q3',
                            'type'          => 'single_choice',
                            'question'      => '¿Cuál es la velocidad máxima en una carretera de doble vía sin separador?',
                            'options'       => [
                                ['id' => 'A', 'text' => '80 km/h'],
                                ['id' => 'B', 'text' => '100 km/h'],
                                ['id' => 'C', 'text' => '120 km/h'],
                            ],
                            'correctAnswer' => 'A',
                            'category'      => 'Normas',
                            'difficulty'    => 'media',
                            'critical'      => false,
                            'explanation'   => 'En carreteras de doble vía sin separador central el límite es 80 km/h.',
                        ],
                        // ── Señalética ──────────────────────────────────────
                        [
                            'id'            => 'Q4',
                            'type'          => 'single_choice',
                            'question'      => '¿Qué indica una señal octogonal roja?',
                            'options'       => [
                                ['id' => 'A', 'text' => 'Ceda el paso'],
                                ['id' => 'B', 'text' => 'Pare'],
                                ['id' => 'C', 'text' => 'Velocidad máxima'],
                            ],
                            'correctAnswer' => 'B',
                            'category'      => 'Señalética',
                            'difficulty'    => 'baja',
                            'critical'      => true,
                            'explanation'   => 'La señal octogonal roja es el PARE — detención obligatoria.',
                        ],
                        [
                            'id'            => 'Q5',
                            'type'          => 'single_choice',
                            'question'      => '¿Qué tipo de señal advierte sobre un cruce de peatones?',
                            'options'       => [
                                ['id' => 'A', 'text' => 'Señal reglamentaria'],
                                ['id' => 'B', 'text' => 'Señal preventiva'],
                                ['id' => 'C', 'text' => 'Señal informativa'],
                            ],
                            'correctAnswer' => 'B',
                            'category'      => 'Señalética',
                            'difficulty'    => 'media',
                            'critical'      => false,
                            'explanation'   => 'Las señales preventivas advierten condiciones de la vía, como cruces peatonales.',
                        ],
                        // ── Conducción segura ───────────────────────────────
                        [
                            'id'            => 'Q6',
                            'type'          => 'single_choice',
                            'question'      => '¿Qué es la conducción defensiva?',
                            'options'       => [
                                ['id' => 'A', 'text' => 'Conducir rápido para anticiparse al tráfico'],
                                ['id' => 'B', 'text' => 'Anticipar y reaccionar a riesgos antes de que ocurran'],
                                ['id' => 'C', 'text' => 'Ceder siempre el paso sin importar la señal'],
                            ],
                            'correctAnswer' => 'B',
                            'category'      => 'Conducción segura',
                            'difficulty'    => 'baja',
                            'critical'      => false,
                            'explanation'   => 'La conducción defensiva anticipa situaciones de riesgo para evitarlas.',
                        ],
                        [
                            'id'            => 'Q7',
                            'type'          => 'single_choice',
                            'question'      => '¿Cuál es el efecto del alcohol en la conducción?',
                            'options'       => [
                                ['id' => 'A', 'text' => 'Mejora los reflejos'],
                                ['id' => 'B', 'text' => 'No afecta si se consume poco'],
                                ['id' => 'C', 'text' => 'Reduce los reflejos y la concentración'],
                            ],
                            'correctAnswer' => 'C',
                            'category'      => 'Conducción segura',
                            'difficulty'    => 'baja',
                            'critical'      => true,
                            'explanation'   => 'El alcohol reduce reflejos y concentración incluso en cantidades pequeñas.',
                        ],
                        // ── Mecánica básica ─────────────────────────────────
                        [
                            'id'            => 'Q8',
                            'type'          => 'single_choice',
                            'question'      => '¿Con qué frecuencia se recomienda revisar la presión de neumáticos?',
                            'options'       => [
                                ['id' => 'A', 'text' => 'Cada 6 meses'],
                                ['id' => 'B', 'text' => 'Solo al cambiarlos'],
                                ['id' => 'C', 'text' => 'Al menos una vez al mes'],
                            ],
                            'correctAnswer' => 'C',
                            'category'      => 'Mecánica básica',
                            'difficulty'    => 'baja',
                            'critical'      => false,
                            'explanation'   => 'La presión debe revisarse mensualmente o antes de viajes largos.',
                        ],
                        // ── Primeros auxilios ───────────────────────────────
                        [
                            'id'            => 'Q9',
                            'type'          => 'single_choice',
                            'question'      => '¿Qué significa el protocolo PAS?',
                            'options'       => [
                                ['id' => 'A', 'text' => 'Pausar, Avisar, Socorrer'],
                                ['id' => 'B', 'text' => 'Proteger, Avisar, Socorrer'],
                                ['id' => 'C', 'text' => 'Parar, Asistir, Señalizar'],
                            ],
                            'correctAnswer' => 'B',
                            'category'      => 'Primeros auxilios',
                            'difficulty'    => 'media',
                            'critical'      => false,
                            'explanation'   => 'PAS: Proteger la zona, Avisar a emergencias, Socorrer a heridos.',
                        ],
                    ],
                ],
            ]);
        }

        if ($algebra) {
            Exam::create([
                'series_id'     => $algebra->id,
                'title'         => 'Álgebra I',
                'description'   => 'Ecuaciones lineales y cuadráticas.',
                'version'       => '1.0',
                'type'          => 'multiple_choice',
                'is_final_exam' => true,
                'status'        => 'published',
                'json_schema'   => [
                    'exam' => [
                        'id'                => 'alg1',
                        'title'             => 'Álgebra I',
                        'version'           => '1.0',
                        'passingScore'      => 70,
                        'timeLimitMinutes'  => 60,
                        'shuffleQuestions'  => false,
                        'allowPartialScore' => true,
                    ],
                    'questions' => [
                        [
                            'id'            => 'Q1',
                            'type'          => 'multiple_choice',
                            'question'      => '¿Cuáles son las soluciones de x² - 5x + 6 = 0?',
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
                            'explanation'   => 'Factorizando: (x-2)(x-3)=0, soluciones x=2 y x=3.',
                        ],
                    ],
                ],
            ]);
        }
    }
}