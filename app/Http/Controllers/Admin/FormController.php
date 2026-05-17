<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Series;
use App\Services\ExamValidatorService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Controlador específico para las peticiones AJAX/API del formulario
 * de creación de exámenes (multipaso). No maneja navegación Inertia,
 * solo devuelve JSON.
 */
class FormController extends Controller
{
    /**
     * Devuelve la lista de series existentes (id, title, domain)
     * para llenar el desplegable en el paso 2 del asistente.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSeriesList()
    {
        $series = Series::select('id', 'title', 'domain')
            ->orderBy('title')
            ->get();

        return response()->json([
            'series' => $series,
        ]);
    }

    /**
     * Valida un JSON contra el contrato del tipo de evaluación especificado.
     * Utiliza el servicio ExamValidatorService.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  ExamValidatorService  $validator
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateJson(Request $request, ExamValidatorService $validator)
    {
        // Validar campos de entrada
        $request->validate([
            'json_schema' => 'required|string',
            'type' => ['required', Rule::in(['single_choice', 'multiple_choice', 'true_false', 'ordering', 'matching'])],
        ]);

        // Ejecutar validación profunda del JSON
        $result = $validator->validate($request->json_schema, $request->type);

        return response()->json([
            'valid' => $result->isValid(),
            'errors' => $result->getErrors(),
            'layer' => $result->getLayer(),  // 'syntax', 'type_validation', 'logical'
        ]);
    }
}