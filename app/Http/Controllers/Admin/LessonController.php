<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Topic;
use App\Models\Series;
use App\Models\Lesson;
use App\Models\LessonImport;
use App\Services\LessonImportService;
use App\Services\TopicContentValidator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Controlador de Lecciones para el panel de administración.
 * 
 * Gestiona las lecciones (contenido Markdown) dentro de un topic.
 * También maneja la importación masiva de topics y lecciones desde archivos JSON.
 * 
 * @package App\Http\Controllers\Admin
 */
class LessonController extends Controller
{
    protected LessonImportService $importService;
    protected TopicContentValidator $validator;

    public function __construct(LessonImportService $importService, TopicContentValidator $validator)
    {
        $this->importService = $importService;
        $this->validator = $validator;
    }

    /**
     * Muestra el listado de lecciones de un topic (vista Inertia).
     *
     * @param  \App\Models\Topic  $topic
     * @return \Inertia\Response
     */
    public function index(Topic $topic)
    {
        $lessons = $topic->lessons()
            ->orderBy('order')
            ->get()
            ->map(fn($lesson) => [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'slug' => $lesson->slug,
                'order' => $lesson->order,
                'duration_minutes' => $lesson->duration_minutes,
                'is_preview' => $lesson->is_preview,
                'created_at' => $lesson->created_at->format('Y-m-d H:i'),
            ]);

        return inertia('Admin/Lessons/Index', [
            'topic' => [
                'id' => $topic->id,
                'title' => $topic->title,
                'series' => [
                    'id' => $topic->series->id,
                    'title' => $topic->series->title,
                ],
            ],
            'lessons' => $lessons,
        ]);
    }

    /**
     * Almacena una nueva lección (creación individual).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Topic  $topic
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, Topic $topic)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string|min:50',
            'order' => 'nullable|integer|min:0',
            'duration_minutes' => 'nullable|integer|min:1|max:120',
            'is_preview' => 'boolean',
        ]);

        // Auto-generar slug
        $slug = Str::slug($validated['title']);
        $original = $slug;
        $count = 1;
        while (Lesson::where('topic_id', $topic->id)->where('slug', $slug)->exists()) {
            $slug = $original . '-' . $count++;
        }
        $validated['slug'] = $slug;
        $validated['topic_id'] = $topic->id;
        $validated['order'] = $validated['order'] ?? $topic->lessons()->max('order') + 1;

        $topic->lessons()->create($validated);

        return redirect()->route('admin.topics.show', [
    'series' => $topic->series_id,
    'topic'  => $topic->id,
])->with('success', 'Lección creada correctamente.');
    }

    /**
     * Muestra el formulario de edición de una lección (vista Inertia).
     *
     * @param  \App\Models\Lesson  $lesson
     * @return \Inertia\Response
     */
    public function edit(Lesson $lesson)
    {
        return inertia('Admin/Lessons/Edit', [
            'lesson' => [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'content' => $lesson->content,
                'order' => $lesson->order,
                'duration_minutes' => $lesson->duration_minutes,
                'is_preview' => $lesson->is_preview,
                'topic_id' => $lesson->topic_id,
            ],
            'topic' => [
                'id' => $lesson->topic->id,
                'title' => $lesson->topic->title,
                'series' => $lesson->topic->series->title,
            ],
        ]);
    }

    /**
     * Actualiza una lección existente.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Lesson  $lesson
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Lesson $lesson)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string|min:50',
            'order' => 'nullable|integer|min:0',
            'duration_minutes' => 'nullable|integer|min:1|max:120',
            'is_preview' => 'boolean',
        ]);

        // Si el título cambió, actualizar slug
        if ($validated['title'] !== $lesson->title) {
            $slug = Str::slug($validated['title']);
            $original = $slug;
            $count = 1;
            while (Lesson::where('topic_id', $lesson->topic_id)
                ->where('slug', $slug)
                ->where('id', '!=', $lesson->id)
                ->exists()
            ) {
                $slug = $original . '-' . $count++;
            }
            $validated['slug'] = $slug;
        }

        $lesson->update($validated);

        return redirect()->route('admin.topics.show', $lesson->topic)
            ->with('success', 'Lección actualizada correctamente.');
    }

    /**
     * Elimina una lección.
     *
     * @param  \App\Models\Lesson  $lesson
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Lesson $lesson)
    {
        $topic = $lesson->topic;
        $lesson->delete();

        return redirect()->route('admin.topics.show', $topic)
            ->with('success', 'Lección eliminada correctamente.');
    }

    /**
     * Importa un archivo JSON y lo asocia a un topic existente.
     * El archivo debe cumplir el contrato "topic_only".
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Topic  $topic
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function import(Request $request, Topic $topic)
    {
        $request->validate([
            'file' => 'required|file|mimes:json|max:5120', // Solo JSON
        ]);

        $file = $request->file('file');
        $content = file_get_contents($file->getRealPath());

        // Validar con TopicContentValidator
        $availableCategories = $this->getAvailableCategories($topic->series);
        $validation = $this->validator->validate($content, 'topic_only', $availableCategories);

        if (!$validation['valid']) {
            // Si la validación falla, retornamos errores
            if ($request->wantsJson()) {
                return response()->json($validation, 422);
            }
            return back()->with('validation_errors', $validation['errors'])->withInput();
        }

        try {
            // Usar el servicio de importación (método importTopic)
            $import = $this->importService->importTopic($file, $topic, Auth::id());

            if ($import->status === 'success') {
                $message = "Importación exitosa: {$import->lessons_created} lecciones creadas.";
                if ($request->wantsJson()) {
                    return response()->json(['success' => true, 'message' => $message]);
                }
                return redirect()->route('admin.topics.show', $topic)
                    ->with('success', $message);
            } else {
                throw new \Exception('Falló la importación.');
            }
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return back()->with('error', 'Error durante la importación: ' . $e->getMessage());
        }
    }

    /**
     * Importa una serie completa (topics + lessons) desde un archivo JSON.
     * El archivo debe cumplir el contrato "series_full".
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Series  $series
     * @return \Illuminate\Http\RedirectResponse
     */
    public function importSeries(Request $request, Series $series)
    {
        $request->validate([
            'file' => 'required|file|mimes:json|max:5120',
        ]);

        $file = $request->file('file');
        $content = file_get_contents($file->getRealPath());

        // Validar estructura "series_full"
        $availableCategories = $this->getAvailableCategories($series);
        $validation = $this->validator->validate($content, 'series_full', $availableCategories);

        if (!$validation['valid']) {
            return back()->with('validation_errors', $validation['errors'])->withInput();
        }

        try {
            $import = $this->importService->importSeries($file, $series, Auth::id());

            if ($import->status === 'success') {
                return redirect()->route('admin.series.show', $series)
                    ->with('success', "Importación completada: {$import->topics_created} temas y {$import->lessons_created} lecciones creadas.");
            } else {
                throw new \Exception('Falló la importación.');
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Error durante la importación: ' . $e->getMessage());
        }
    }

    /**
     * Muestra el historial de importaciones de una serie (vista Inertia).
     *
     * @param  \App\Models\Series  $series
     * @return \Inertia\Response
     */
    public function importHistory(Series $series)
    {
        $imports = LessonImport::where('series_id', $series->id)
            ->with('importer')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($import) => [
                'id' => $import->id,
                'filename' => $import->filename,
                'type' => $import->type,
                'status' => $import->status,
                'topics_created' => $import->topics_created,
                'lessons_created' => $import->lessons_created,
                'validation_errors' => $import->validation_errors,
                'imported_by' => $import->importer->name,
                'imported_at' => $import->imported_at ? $import->imported_at->format('Y-m-d H:i') : null,
                'created_at' => $import->created_at->format('Y-m-d H:i'),
            ]);

        return inertia('Admin/Imports/History', [
            'series' => [
                'id' => $series->id,
                'title' => $series->title,
            ],
            'imports' => $imports,
        ]);
    }

    /**
     * Obtiene las categorías disponibles del examen final de la serie.
     *
     * @param  \App\Models\Series  $series
     * @return array
     */
    private function getAvailableCategories(Series $series): array
    {
        $finalExam = $series->exams()->where('is_final_exam', true)->first();
        if (!$finalExam || empty($finalExam->json_schema['questions'])) {
            return [];
        }
        $categories = [];
        foreach ($finalExam->json_schema['questions'] as $question) {
            if (!empty($question['category'])) {
                $categories[] = $question['category'];
            }
        }
        return array_values(array_unique($categories));
    }

    public function reorder(Request $request, Topic $topic)
{
    $request->validate(['order' => 'required|array', 'order.*' => 'integer']);
    foreach ($request->order as $index => $id) {
        Lesson::where('id', $id)->where('topic_id', $topic->id)
              ->update(['order' => $index + 1]);
    }
    return back()->with('success', 'Orden actualizado.');
}
}