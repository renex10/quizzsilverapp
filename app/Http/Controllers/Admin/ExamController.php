<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Series;
use App\Services\ExamValidatorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Controlador de Exámenes para el panel de administración.
 *
 * Gestiona las evaluaciones (versiones concretas dentro de una serie).
 * Incluye creación mediante validación de JSON generado por IA,
 * siguiendo los contratos definidos en el requerimiento.
 */
class ExamController extends Controller
{
    protected ExamValidatorService $validatorService;

    public function __construct(ExamValidatorService $validatorService)
    {
        $this->validatorService = $validatorService;
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    /**
     * Muestra el listado de exámenes con filtros opcionales.
     */
    public function index(Request $request)
    {
        $query = Exam::with('series');

        if ($seriesId = $request->get('series_id')) {
            $query->where('series_id', $seriesId);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Búsqueda por título
        if ($search = $request->get('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        $exams = $query->orderBy('created_at', 'desc')->paginate(15);

        $exams->getCollection()->transform(function ($exam) {
            return [
                'id'         => $exam->id,
                'title'      => $exam->title,
                'version'    => $exam->version,
                'type'       => $exam->type,
                'status'     => $exam->status,
                'series'     => [
                    'id'    => $exam->series->id,
                    'title' => $exam->series->title,
                ],
                'created_at' => $exam->created_at->format('Y-m-d H:i'),
            ];
        });

        $series = Series::orderBy('title')->get(['id', 'title']);

        return inertia('Admin/Exams/Index', [
            'exams'      => $exams,
            'filters'    => [
                'series_id' => $seriesId ?? '',
                'status'    => $status    ?? '',
                'search'    => $request->get('search', ''),
            ],
            'seriesList' => $series,
        ]);
    }

    // =========================================================================
    // CREATE
    // =========================================================================

    /**
     * Devuelve datos para el modal de creación (series y tipos disponibles).
     */
    public function create()
    {
        $series = Series::orderBy('title')->get(['id', 'title', 'domain']);
        $types  = [
            ['value' => 'single_choice',   'label' => 'Opción única',        'description' => 'Una respuesta correcta entre 2-6 opciones'],
            ['value' => 'multiple_choice', 'label' => 'Opción múltiple',     'description' => 'Una o más respuestas correctas, con puntaje parcial opcional'],
            ['value' => 'true_false',      'label' => 'Verdadero / Falso',   'description' => 'Afirmación verdadera o falsa'],
            ['value' => 'ordering',        'label' => 'Ordenamiento',        'description' => 'Ordenar elementos secuencialmente'],
            ['value' => 'matching',        'label' => 'Relacionar columnas', 'description' => 'Emparejar elementos de dos columnas'],
        ];

        return response()->json([
            'series' => $series,
            'types'  => $types,
        ]);
    }

    // =========================================================================
    // STORE
    // =========================================================================

    /**
     * Almacena un nuevo examen después de validar el JSON.
     *
     * Devuelve JSON de errores (no redirect) para que el modal Inertia
     * los capture en onError sin causar NS_BINDING_ABORTED.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'series_id'              => 'nullable|exists:series,id',
            'new_series'             => 'nullable|array',
            'new_series.title'       => 'required_without:series_id|string|max:255',
            'new_series.domain'      => 'required_without:series_id|string|max:255',
            'new_series.description' => 'nullable|string',
            'title'                  => 'required|string|max:255',
            'description'            => 'nullable|string',
            'version'                => 'required|string|max:50',
            'type'                   => ['required', Rule::in(['single_choice', 'multiple_choice', 'true_false', 'ordering', 'matching'])],
            'json_schema'            => 'required|string',
            'status'                 => ['nullable', Rule::in(['draft', 'published'])],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación en los campos del formulario.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Validar el contrato JSON según el tipo
        $validationResult = $this->validatorService->validate(
            $request->json_schema,
            $request->type
        );

        if (!$validationResult->isValid()) {
            return response()->json([
                'message' => 'El JSON no cumple el contrato para el tipo seleccionado.',
                'errors'  => ['json_schema' => $validationResult->getErrors()],
                'layer'   => $validationResult->getLayer(),
            ], 422);
        }

        // Usar 'published' si no se envió status
        $status = $request->filled('status') ? $request->status : 'published';

        DB::beginTransaction();
        try {
            // Resolver series_id — usar existente o crear nueva
            if ($request->filled('series_id')) {
                $seriesId = $request->series_id;
            } else {
                $newSeriesData = $request->new_series;

                $existingSeries = Series::where('title', $newSeriesData['title'])->first();
                if ($existingSeries) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Ya existe una serie con ese título.',
                        'errors'  => [
                            'new_series.title' => ['Ya existe una serie con ese título. Seleccionala de la lista.'],
                        ],
                    ], 422);
                }

                $series   = Series::create([
                    'title'       => $newSeriesData['title'],
                    'description' => $newSeriesData['description'] ?? '',
                    'domain'      => $newSeriesData['domain'],
                ]);
                $seriesId = $series->id;
            }

            $exam = Exam::create([
                'series_id'   => $seriesId,
                'title'       => $request->title,
                'description' => $request->description,
                'version'     => $request->version,
                'type'        => $request->type,
                'json_schema' => json_decode($request->json_schema, true),
                'status'      => $status,
            ]);

            DB::commit();

            return redirect()->route('admin.exams.index')
                ->with('success', 'Examen "' . $exam->title . '" creado correctamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ExamController@store error', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Error interno al guardar el examen.',
                'errors'  => ['general' => [$e->getMessage()]],
            ], 500);
        }
    }

    // =========================================================================
    // SHOW
    // =========================================================================

    /**
     * Muestra los detalles de un examen sin exponer respuestas correctas.
     *
     * Manejo defensivo del json_schema que puede estar como:
     * - array PHP  (cast correcto del modelo)
     * - string JSON (doble serialización al guardar en versiones anteriores)
     * - array con questions como strings (serialización incorrecta legacy)
     */
    public function show(Exam $exam)
    {
        $exam->load('series');

        $schema = $exam->json_schema;

        // Si llegó como string por doble encode, decodificar
        if (is_string($schema)) {
            $schema = json_decode($schema, true);
        }

        // Schema inválido o corrupto
        if (!is_array($schema) || !isset($schema['questions'])) {
            return inertia('Admin/Exams/Show', [
                'exam' => [
                    'id'          => $exam->id,
                    'title'       => $exam->title,
                    'description' => $exam->description,
                    'version'     => $exam->version,
                    'type'        => $exam->type,
                    'status'      => $exam->status,
                    'series'      => $exam->series->title,
                    'created_at'  => $exam->created_at->format('Y-m-d H:i'),
                ],
                'questions'   => [],
                'examConfig'  => [],
                'schemaError' => 'El JSON de esta evaluación está corrupto o en formato inválido.',
            ]);
        }

        // Preparar preguntas sin datos sensibles
        $publicQuestions = array_map(function ($q) {
            // Si la pregunta llegó serializada como string, deserializar
            if (is_string($q)) {
                $q = json_decode($q, true);
            }

            if (!is_array($q)) {
                return null;
            }

            unset($q['correctAnswer']);
            unset($q['explanation']);
            return $q;
        }, $schema['questions']);

        // Filtrar preguntas nulas (corruptas)
        $publicQuestions = array_values(array_filter($publicQuestions));

        return inertia('Admin/Exams/Show', [
            'exam' => [
                'id'          => $exam->id,
                'title'       => $exam->title,
                'description' => $exam->description,
                'version'     => $exam->version,
                'type'        => $exam->type,
                'status'      => $exam->status,
                'series'      => $exam->series->title,
                'created_at'  => $exam->created_at->format('Y-m-d H:i'),
            ],
            'questions'  => $publicQuestions,
            'examConfig' => $schema['exam'] ?? [],
        ]);
    }

    // =========================================================================
    // EDIT / UPDATE
    // =========================================================================

    /**
     * Muestra el formulario de edición (solo metadatos, no el JSON).
     */
    public function edit(Exam $exam)
    {
        $series = Series::orderBy('title')->get(['id', 'title']);

        return inertia('Admin/Exams/Edit', [
            'exam' => [
                'id'          => $exam->id,
                'series_id'   => $exam->series_id,
                'title'       => $exam->title,
                'description' => $exam->description,
                'version'     => $exam->version,
                'status'      => $exam->status,
            ],
            'seriesList' => $series,
        ]);
    }

    /**
     * Actualiza los metadatos del examen.
     * Para cambiar el JSON se debe crear una nueva versión del examen.
     */
    public function update(Request $request, Exam $exam)
    {
        $validator = Validator::make($request->all(), [
            'series_id'   => 'required|exists:series,id',
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'version'     => 'required|string|max:50',
            'status'      => ['required', Rule::in(['draft', 'published'])],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $exam->update($validator->validated());

        return redirect()->route('admin.exams.index')
            ->with('success', 'Examen actualizado correctamente.');
    }

    // =========================================================================
    // DESTROY
    // =========================================================================

    /**
     * Elimina un examen solo si no tiene intentos asociados.
     */
    public function destroy(Exam $exam)
    {
        if ($exam->attempts()->count() > 0) {
            return back()->with('error', 'No se puede eliminar el examen porque tiene intentos registrados.');
        }

        $exam->delete();

        return redirect()->route('admin.exams.index')
            ->with('success', 'Examen eliminado correctamente.');
    }

    // =========================================================================
    // VALIDACIÓN Y GUÍAS JSON
    // =========================================================================

    /**
     * Valida un JSON contra el contrato del tipo especificado.
     * Endpoint auxiliar usado por el Paso 5 del modal.
     */
    public function validateJson(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'json_schema' => 'required|string',
            'type'        => ['required', Rule::in(['single_choice', 'multiple_choice', 'true_false', 'ordering', 'matching'])],
        ]);

        if ($validator->fails()) {
            return response()->json(['valid' => false, 'errors' => $validator->errors()], 422);
        }

        $result = $this->validatorService->validate($request->json_schema, $request->type);

        return response()->json([
            'valid'  => $result->isValid(),
            'errors' => $result->getErrors(),
            'layer'  => $result->getLayer(),
        ]);
    }

    /**
     * Devuelve la plantilla JSON de ejemplo para el tipo especificado.
     * Usado en el Paso 4 del modal.
     */
    public function getJsonGuide(string $type)
    {
        $templates = [
            'single_choice'   => $this->getSingleChoiceTemplate(),
            'multiple_choice' => $this->getMultipleChoiceTemplate(),
            'true_false'      => $this->getTrueFalseTemplate(),
            'ordering'        => $this->getOrderingTemplate(),
            'matching'        => $this->getMatchingTemplate(),
        ];

        if (!isset($templates[$type])) {
            return response()->json(['error' => 'Tipo no soportado'], 400);
        }

        return response()->json([
            'type'     => $type,
            'template' => $templates[$type],
        ]);
    }

    // =========================================================================
    // PLANTILLAS JSON PRIVADAS
    // =========================================================================

    private function getSingleChoiceTemplate(): string
    {
        return json_encode([
            'exam'      => [
                'id'               => 'string_unico',
                'title'            => 'Título de la evaluación',
                'version'          => '2024',
                'passingScore'     => 80,
                'timeLimitMinutes' => 45,
                'shuffleQuestions' => true,
            ],
            'questions' => [[
                'id'            => 'Q1',
                'type'          => 'single_choice',
                'question'      => 'Texto de la pregunta',
                'options'       => [
                    ['id' => 'A', 'text' => 'Opción A'],
                    ['id' => 'B', 'text' => 'Opción B'],
                    ['id' => 'C', 'text' => 'Opción C'],
                ],
                'correctAnswer' => 'B',
                'category'      => 'categoria_tematica',
                'difficulty'    => 'baja',
                'critical'      => false,
                'explanation'   => 'Explicación de por qué B es correcta',
            ]],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    private function getMultipleChoiceTemplate(): string
    {
        return json_encode([
            'exam'      => [
                'id'                => 'string_unico',
                'title'             => 'Título de la evaluación',
                'version'           => '2024',
                'passingScore'      => 75,
                'timeLimitMinutes'  => 60,
                'shuffleQuestions'  => true,
                'allowPartialScore' => true,
            ],
            'questions' => [[
                'id'            => 'Q1',
                'type'          => 'multiple_choice',
                'question'      => '¿Cuáles de los siguientes son correctos?',
                'options'       => [
                    ['id' => 'A', 'text' => 'Opción A'],
                    ['id' => 'B', 'text' => 'Opción B'],
                    ['id' => 'C', 'text' => 'Opción C'],
                    ['id' => 'D', 'text' => 'Opción D'],
                ],
                'correctAnswer' => ['A', 'C'],
                'category'      => 'categoria_tematica',
                'difficulty'    => 'media',
                'critical'      => false,
                'explanation'   => 'A y C son correctas porque...',
            ]],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    private function getTrueFalseTemplate(): string
    {
        return json_encode([
            'exam'      => [
                'id'               => 'string_unico',
                'title'            => 'Título de la evaluación',
                'version'          => '2024',
                'passingScore'     => 70,
                'timeLimitMinutes' => 30,
                'shuffleQuestions' => true,
            ],
            'questions' => [[
                'id'            => 'Q1',
                'type'          => 'true_false',
                'question'      => 'Afirmación que debe evaluarse como verdadera o falsa',
                'correctAnswer' => true,
                'category'      => 'categoria_tematica',
                'difficulty'    => 'baja',
                'critical'      => false,
                'explanation'   => 'Es verdadera porque...',
            ]],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    private function getOrderingTemplate(): string
    {
        return json_encode([
            'exam'      => [
                'id'                => 'string_unico',
                'title'             => 'Título de la evaluación',
                'version'           => '2024',
                'passingScore'      => 80,
                'timeLimitMinutes'  => 40,
                'shuffleQuestions'  => true,
                'allowPartialScore' => true,
            ],
            'questions' => [[
                'id'            => 'Q1',
                'type'          => 'ordering',
                'question'      => 'Ordena los siguientes pasos correctamente',
                'options'       => [
                    ['id' => 'A', 'text' => 'Paso A'],
                    ['id' => 'B', 'text' => 'Paso B'],
                    ['id' => 'C', 'text' => 'Paso C'],
                    ['id' => 'D', 'text' => 'Paso D'],
                ],
                'correctAnswer' => ['C', 'A', 'D', 'B'],
                'category'      => 'categoria_tematica',
                'difficulty'    => 'alta',
                'critical'      => false,
                'explanation'   => 'El orden correcto es C → A → D → B porque...',
            ]],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    private function getMatchingTemplate(): string
    {
        return json_encode([
            'exam'      => [
                'id'               => 'string_unico',
                'title'            => 'Título de la evaluación',
                'version'          => '2024',
                'passingScore'     => 80,
                'timeLimitMinutes' => 40,
                'shuffleQuestions' => false,
            ],
            'questions' => [[
                'id'          => 'Q1',
                'type'        => 'matching',
                'question'    => 'Relaciona cada elemento con su definición correcta',
                'leftColumn'  => [
                    ['id' => 'L1', 'text' => 'Concepto 1'],
                    ['id' => 'L2', 'text' => 'Concepto 2'],
                    ['id' => 'L3', 'text' => 'Concepto 3'],
                ],
                'rightColumn' => [
                    ['id' => 'R1', 'text' => 'Definición 1'],
                    ['id' => 'R2', 'text' => 'Definición 2'],
                    ['id' => 'R3', 'text' => 'Definición 3'],
                ],
                'correctAnswer' => ['L1' => 'R3', 'L2' => 'R1', 'L3' => 'R2'],
                'category'      => 'categoria_tematica',
                'difficulty'    => 'media',
                'critical'      => false,
                'explanation'   => 'La relación correcta es porque...',
            ]],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}