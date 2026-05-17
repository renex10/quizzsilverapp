<?php

namespace App\Services;

use App\Services\ValidationResult;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Servicio encargado de validar el JSON de un examen según el tipo de evaluación.
 * 
 * Realiza tres capas de validación:
 * 1. Sintáctica (JSON válido y estructura básica)
 * 2. Por tipo de evaluación (contrato específico: campos, tipos, respuestas correctas)
 * 3. Lógica de negocio (IDs únicos, puntajes, etc.)
 */
class ExamValidatorService
{
    /**
     * Valida un string JSON contra el tipo de evaluación especificado.
     *
     * @param string $jsonString
     * @param string $type (single_choice, multiple_choice, true_false, ordering, matching)
     * @return ValidationResult
     */
    public function validate(string $jsonString, string $type): ValidationResult
    {
        // Capa 1: Validación sintáctica
        $syntaxResult = $this->validateSyntax($jsonString);
        if (!$syntaxResult->isValid()) {
            return $syntaxResult;
        }

        $data = json_decode($jsonString, true);

        // Capa 2: Validación por tipo
        $typeResult = $this->validateByType($data, $type);
        if (!$typeResult->isValid()) {
            return $typeResult;
        }

        // Capa 3: Validación lógica (negocio)
        $logicResult = $this->validateLogic($data, $type);
        if (!$logicResult->isValid()) {
            return $logicResult;
        }

        return new ValidationResult(true, [], 'all');
    }

    /**
     * Capa 1: Verifica que el string sea JSON válido y tenga estructura mínima.
     *
     * @param string $jsonString
     * @return ValidationResult
     */
    private function validateSyntax(string $jsonString): ValidationResult
    {
        $data = json_decode($jsonString, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new ValidationResult(false, ['El JSON no es válido: ' . json_last_error_msg()], 'syntax');
        }

        // Verificar campos raíz
        if (!isset($data['exam']) || !isset($data['questions'])) {
            return new ValidationResult(false, ['El JSON debe contener las claves "exam" y "questions".'], 'syntax');
        }

        if (!is_array($data['questions']) || empty($data['questions'])) {
            return new ValidationResult(false, ['"questions" debe ser un array no vacío.'], 'syntax');
        }

        return new ValidationResult(true, [], 'syntax');
    }

    /**
     * Capa 2: Valida según el tipo específico (single_choice, multiple_choice, etc.)
     *
     * @param array $data
     * @param string $type
     * @return ValidationResult
     */
    private function validateByType(array $data, string $type): ValidationResult
    {
        $errors = [];

        // Validar estructura de cada pregunta según el tipo
        foreach ($data['questions'] as $index => $q) {
            $prefix = "Pregunta " . ($index + 1) . " (ID: {$q['id']})";

            // Campos comunes
            $requiredFields = ['id', 'type', 'question', 'category', 'difficulty', 'critical', 'explanation'];
            foreach ($requiredFields as $field) {
                if (!array_key_exists($field, $q)) {
                    $errors[] = "$prefix: Falta el campo '$field'.";
                }
            }

            if (($q['type'] ?? null) !== $type) {
                $errors[] = "$prefix: El campo 'type' debe ser '$type'.";
            }

            // Validaciones específicas por tipo
            switch ($type) {
                case 'single_choice':
                    $errors = array_merge($errors, $this->validateSingleChoice($q, $prefix));
                    break;
                case 'multiple_choice':
                    $errors = array_merge($errors, $this->validateMultipleChoice($q, $prefix));
                    break;
                case 'true_false':
                    $errors = array_merge($errors, $this->validateTrueFalse($q, $prefix));
                    break;
                case 'ordering':
                    $errors = array_merge($errors, $this->validateOrdering($q, $prefix));
                    break;
                case 'matching':
                    $errors = array_merge($errors, $this->validateMatching($q, $prefix));
                    break;
            }
        }

        if (!empty($errors)) {
            return new ValidationResult(false, $errors, 'type_validation');
        }

        return new ValidationResult(true, [], 'type_validation');
    }

    // Métodos auxiliares para cada tipo

    private function validateSingleChoice(array $q, string $prefix): array
    {
        $errors = [];
        if (!isset($q['options']) || count($q['options']) < 2 || count($q['options']) > 6) {
            $errors[] = "$prefix: 'options' debe tener entre 2 y 6 elementos.";
        }
        if (!isset($q['correctAnswer']) || !is_string($q['correctAnswer'])) {
            $errors[] = "$prefix: 'correctAnswer' debe ser un string.";
        }
        // Verificar que correctAnswer exista en las opciones
        $optionIds = array_column($q['options'] ?? [], 'id');
        if (!in_array($q['correctAnswer'] ?? '', $optionIds)) {
            $errors[] = "$prefix: 'correctAnswer' no coincide con ningún ID de las opciones.";
        }
        return $errors;
    }

    private function validateMultipleChoice(array $q, string $prefix): array
    {
        $errors = [];
        if (!isset($q['options']) || count($q['options']) < 2) {
            $errors[] = "$prefix: 'options' debe tener al menos 2 elementos.";
        }
        if (!isset($q['correctAnswer']) || !is_array($q['correctAnswer'])) {
            $errors[] = "$prefix: 'correctAnswer' debe ser un array de IDs.";
        }
        $optionIds = array_column($q['options'] ?? [], 'id');
        foreach ($q['correctAnswer'] ?? [] as $ans) {
            if (!in_array($ans, $optionIds)) {
                $errors[] = "$prefix: 'correctAnswer' contiene '$ans' que no es un ID válido.";
            }
        }
        return $errors;
    }

    private function validateTrueFalse(array $q, string $prefix): array
    {
        $errors = [];
        if (!isset($q['correctAnswer']) || !is_bool($q['correctAnswer'])) {
            $errors[] = "$prefix: 'correctAnswer' debe ser booleano (true/false).";
        }
        return $errors;
    }

    private function validateOrdering(array $q, string $prefix): array
    {
        $errors = [];
        if (!isset($q['options']) || count($q['options']) < 2) {
            $errors[] = "$prefix: 'options' debe tener al menos 2 elementos.";
        }
        if (!isset($q['correctAnswer']) || !is_array($q['correctAnswer'])) {
            $errors[] = "$prefix: 'correctAnswer' debe ser un array ordenado de IDs.";
        }
        $optionIds = array_column($q['options'] ?? [], 'id');
        foreach ($q['correctAnswer'] ?? [] as $ans) {
            if (!in_array($ans, $optionIds)) {
                $errors[] = "$prefix: 'correctAnswer' contiene '$ans' que no es un ID válido.";
            }
        }
        if (count($q['correctAnswer'] ?? []) !== count($optionIds)) {
            $errors[] = "$prefix: 'correctAnswer' debe contener exactamente todos los IDs de las opciones.";
        }
        return $errors;
    }

    private function validateMatching(array $q, string $prefix): array
    {
        $errors = [];
        if (!isset($q['leftColumn']) || !isset($q['rightColumn'])) {
            $errors[] = "$prefix: Debe tener 'leftColumn' y 'rightColumn'.";
        }
        if (count($q['leftColumn']) !== count($q['rightColumn'])) {
            $errors[] = "$prefix: 'leftColumn' y 'rightColumn' deben tener la misma cantidad de elementos.";
        }
        if (!isset($q['correctAnswer']) || !is_array($q['correctAnswer'])) {
            $errors[] = "$prefix: 'correctAnswer' debe ser un objeto con pares left->right.";
        }
        // Verificar que las claves de correctAnswer existan en leftColumn y los valores en rightColumn
        $leftIds = array_column($q['leftColumn'] ?? [], 'id');
        $rightIds = array_column($q['rightColumn'] ?? [], 'id');
        foreach ($q['correctAnswer'] as $left => $right) {
            if (!in_array($left, $leftIds)) {
                $errors[] = "$prefix: La clave '$left' en correctAnswer no existe en leftColumn.";
            }
            if (!in_array($right, $rightIds)) {
                $errors[] = "$prefix: El valor '$right' en correctAnswer no existe en rightColumn.";
            }
        }
        return $errors;
    }

    /**
     * Capa 3: Validaciones lógicas de negocio.
     * - IDs de preguntas únicos
     * - IDs de opciones únicos dentro de cada pregunta
     * - passingScore entre 1 y 100
     * - timeLimitMinutes > 0
     * - Si hay preguntas críticas, el examen debe ser aprobable sin ellas (advertencia)
     * - El puntaje máximo alcanzable permite aprobar (advertencia)
     *
     * @param array $data
     * @param string $type
     * @return ValidationResult
     */
    private function validateLogic(array $data, string $type): ValidationResult
    {
        $errors = [];

        // 1. IDs de preguntas únicos
        $questionIds = array_column($data['questions'], 'id');
        if (count($questionIds) !== count(array_unique($questionIds))) {
            $errors[] = 'Los IDs de las preguntas deben ser únicos.';
        }

        // 2. Opciones únicas dentro de cada pregunta
        foreach ($data['questions'] as $q) {
            if (isset($q['options']) && is_array($q['options'])) {
                $optIds = array_column($q['options'], 'id');
                if (count($optIds) !== count(array_unique($optIds))) {
                    $errors[] = "En la pregunta {$q['id']}, los IDs de las opciones deben ser únicos.";
                }
            }
        }

        // 3. Validar campos del examen
        $exam = $data['exam'];
        if (isset($exam['passingScore']) && ($exam['passingScore'] < 1 || $exam['passingScore'] > 100)) {
            $errors[] = 'El campo "passingScore" debe estar entre 1 y 100.';
        }
        if (isset($exam['timeLimitMinutes']) && $exam['timeLimitMinutes'] <= 0) {
            $errors[] = 'El campo "timeLimitMinutes" debe ser mayor a 0.';
        }

        // 4. Preguntas críticas - Solo advertencia (no bloqueante)
        $criticalQuestions = array_filter($data['questions'], fn($q) => $q['critical'] ?? false);
        if (!empty($criticalQuestions)) {
            // Podrías agregar una advertencia, pero no es error
        }

        // 5. Verificar que el puntaje máximo permite aprobar (advertencia)
        $maxPossibleScore = count($data['questions']);
        $passingScorePercent = $exam['passingScore'] ?? 70;
        if (($passingScorePercent / 100) * $maxPossibleScore > $maxPossibleScore) {
            // Esto no debería pasar, pero por si acaso
            $errors[] = 'El passingScore es mayor al puntaje máximo posible.';
        }

        if (!empty($errors)) {
            return new ValidationResult(false, $errors, 'logical');
        }

        return new ValidationResult(true, [], 'logical');
    }
}