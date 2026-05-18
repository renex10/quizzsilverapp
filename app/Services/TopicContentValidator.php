<?php

/**
 * Servicio: TopicContentValidator
 * 
 * Fecha y hora de creación: 17 de mayo de 2026, 22:00 hrs (UTC-4)
 * 
 * Contexto:
 * Este validador se encarga de verificar la estructura y el contenido de los archivos JSON
 * utilizados para importar Topics y Lessons en el banco de conocimiento.
 * 
 * Sigue el mismo patrón que el ExamValidatorService (ya existente en el proyecto).
 * 
 * Dos tipos de contrato soportados:
 * - "series_full":  importa una serie completa (con sus topics y lessons).
 * - "topic_only":   importa un topic con sus lessons (dentro de una serie existente).
 * 
 * El validador devuelve un array con:
 *   - 'valid'   => bool
 *   - 'layer'   => 'structure' | 'semantic' | 'business' (para ayudar en el frontend)
 *   - 'errors'  => array con cada error: path, message, suggestion
 * 
 * Uso típico:
 *   $validator = new TopicContentValidator();
 *   $result = $validator->validate($jsonString, 'topic_only');
 *   if (!$result['valid']) {
 *       // mostrar errores al usuario
 *   }
 */

namespace App\Services;

use Illuminate\Support\Facades\Validator as LaravelValidator;
use Illuminate\Validation\Rule;

class TopicContentValidator
{
    /**
     * Valida un JSON de importación.
     *
     * @param string $jsonString  El contenido JSON (string).
     * @param string $type        'series_full' o 'topic_only'.
     * @param array $availableCategories  Lista de categorías válidas para exam_category (opcional).
     * @return array  ['valid' => bool, 'layer' => string, 'errors' => array]
     */
    public function validate(string $jsonString, string $type, array $availableCategories = []): array
    {
        // 1. Decodificar JSON
        $data = json_decode($jsonString, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'valid' => false,
                'layer' => 'structure',
                'errors' => [[
                    'path' => 'root',
                    'message' => 'JSON inválido: ' . json_last_error_msg(),
                    'suggestion' => 'Verifica que el archivo sea un JSON válido y esté en UTF-8 sin BOM.'
                ]]
            ];
        }

        // 2. Validar según el tipo de contrato
        if ($type === 'series_full') {
            return $this->validateSeriesFull($data, $availableCategories);
        } elseif ($type === 'topic_only') {
            return $this->validateTopicOnly($data, $availableCategories);
        } else {
            return [
                'valid' => false,
                'layer' => 'structure',
                'errors' => [[
                    'path' => 'type',
                    'message' => "Tipo de contrato no soportado: {$type}",
                    'suggestion' => 'Usa "series_full" o "topic_only".'
                ]]
            ];
        }
    }

    /**
     * Valida el contrato "series_full".
     */
    private function validateSeriesFull(array $data, array $availableCategories): array
    {
        $errors = [];

        // --- Validación de la serie raíz ---
        if (!isset($data['series']) || !is_array($data['series'])) {
            $errors[] = [
                'path' => 'series',
                'message' => 'El objeto "series" es requerido.',
                'suggestion' => 'Asegúrate de que el JSON tenga una clave "series" con los campos title, difficulty, etc.'
            ];
        } else {
            $seriesRules = [
                'title' => 'required|string|max:255',
                'difficulty' => ['required', Rule::in(['básico', 'intermedio', 'avanzado'])],
                'estimated_hours' => 'nullable|numeric|min:0|max:999.9',
            ];
            $seriesValidator = LaravelValidator::make($data['series'], $seriesRules);
            if ($seriesValidator->fails()) {
                foreach ($seriesValidator->errors()->all() as $msg) {
                    $errors[] = [
                        'path' => 'series',
                        'message' => $msg,
                        'suggestion' => 'Revisa los campos obligatorios y sus formatos.'
                    ];
                }
            }
        }

        // --- Validación de topics (debe ser un array no vacío) ---
        if (!isset($data['topics']) || !is_array($data['topics']) || empty($data['topics'])) {
            $errors[] = [
                'path' => 'topics',
                'message' => 'Se requiere al menos un topic dentro del array "topics".',
                'suggestion' => 'Incluye un arreglo con uno o más objetos topic.'
            ];
        } else {
            foreach ($data['topics'] as $index => $topic) {
                $topicErrors = $this->validateTopicItem($topic, $index, $availableCategories);
                $errors = array_merge($errors, $topicErrors);
            }
        }

        return $this->buildResult($errors);
    }

    /**
     * Valida el contrato "topic_only".
     */
    private function validateTopicOnly(array $data, array $availableCategories): array
    {
        $errors = [];

        if (!isset($data['topic']) || !is_array($data['topic'])) {
            $errors[] = [
                'path' => 'topic',
                'message' => 'El objeto "topic" es requerido.',
                'suggestion' => 'Asegúrate de tener una clave "topic" con los campos title, order, etc.'
            ];
        } else {
            $topicErrors = $this->validateTopicItem($data['topic'], 0, $availableCategories);
            $errors = array_merge($errors, $topicErrors);
        }

        return $this->buildResult($errors);
    }

    /**
     * Valida un objeto topic individual (incluye sus lessons).
     *
     * @param array $topic
     * @param int $index  Índice dentro del arreglo (para reportar path)
     * @param array $availableCategories
     * @return array  Lista de errores encontrados
     */
    private function validateTopicItem(array $topic, int $index, array $availableCategories): array
    {
        $errors = [];
        $pathPrefix = "topics[{$index}]";

        // Reglas básicas del topic
        $topicRules = [
            'title' => 'required|string|max:255',
            'order' => 'required|integer|min:1',
            'icon' => 'nullable|string|max:10',
            'color' => 'nullable|string|max:20',
            'is_public' => 'boolean',
            'exam_category' => 'nullable|string|max:255',
        ];
        $topicValidator = LaravelValidator::make($topic, $topicRules);
        if ($topicValidator->fails()) {
            foreach ($topicValidator->errors()->all() as $msg) {
                $errors[] = [
                    'path' => $pathPrefix,
                    'message' => $msg,
                    'suggestion' => 'Completa los campos obligatorios (title, order).'
                ];
            }
        }

        // Validar exam_category contra las categorías disponibles (si se proporcionaron)
        if (!empty($topic['exam_category']) && !empty($availableCategories)) {
            if (!in_array($topic['exam_category'], $availableCategories)) {
                $suggestion = 'Categorías disponibles: ' . implode(', ', $availableCategories);
                $errors[] = [
                    'path' => $pathPrefix . '.exam_category',
                    'message' => "La categoría '{$topic['exam_category']}' no existe en el examen de la serie.",
                    'suggestion' => $suggestion,
                ];
            }
        }

        // Validar lessons
        if (!isset($topic['lessons']) || !is_array($topic['lessons']) || empty($topic['lessons'])) {
            $errors[] = [
                'path' => $pathPrefix . '.lessons',
                'message' => 'Se requiere al menos una lección en el topic.',
                'suggestion' => 'Agrega un arreglo "lessons" con uno o más objetos lesson.'
            ];
        } else {
            $orders = [];
            foreach ($topic['lessons'] as $lessonIdx => $lesson) {
                $lessonPath = $pathPrefix . ".lessons[{$lessonIdx}]";
                $lessonRules = [
                    'title' => 'required|string|max:255',
                    'order' => 'required|integer|min:1',
                    'content' => 'required|string|min:50',
                    'duration_minutes' => 'nullable|integer|min:1|max:120',
                    'is_preview' => 'boolean',
                ];
                $lessonValidator = LaravelValidator::make($lesson, $lessonRules);
                if ($lessonValidator->fails()) {
                    foreach ($lessonValidator->errors()->all() as $msg) {
                        $errors[] = [
                            'path' => $lessonPath,
                            'message' => $msg,
                            'suggestion' => 'Revisa que el contenido tenga al menos 50 caracteres y el orden sea un número positivo.'
                        ];
                    }
                }

                // Validar orden único dentro del mismo topic
                if (isset($lesson['order'])) {
                    if (in_array($lesson['order'], $orders)) {
                        $errors[] = [
                            'path' => $lessonPath . '.order',
                            'message' => "El orden {$lesson['order']} está duplicado.",
                            'suggestion' => 'Cada lección debe tener un valor de "order" único dentro del topic.'
                        ];
                    }
                    $orders[] = $lesson['order'];
                }
            }
        }

        return $errors;
    }

    /**
     * Construye el array de resultado final.
     *
     * @param array $errors
     * @return array
     */
    private function buildResult(array $errors): array
    {
        if (empty($errors)) {
            return [
                'valid' => true,
                'layer' => 'structure',
                'errors' => [],
            ];
        }

        // Determinar la capa más crítica (para simplificar, usamos 'structure' si hay errores de estructura,
        // 'business' si hay errores de categoría, etc. Aquí siempre 'structure' por simplicidad)
        return [
            'valid' => false,
            'layer' => 'structure',
            'errors' => $errors,
        ];
    }
}