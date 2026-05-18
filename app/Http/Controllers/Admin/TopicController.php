<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Series;
use App\Models\Topic;
use App\Models\Exam;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Controlador de Topics para el panel de administración.
 * 
 * Gestiona las unidades temáticas dentro de una serie.
 * Los topics organizan las lecciones y están vinculados a categorías
 * del examen (exam_category) para generar mini quizzes.
 * 
 * @package App\Http\Controllers\Admin
 */
class TopicController extends Controller
{
    /**
     * Muestra el listado de topics de una serie, con ordenamiento drag & drop.
     *
     * @param  \App\Models\Series  $series
     * @return \Inertia\Response
     */
    public function index(Series $series)
    {
        $topics = $series->topics()
            ->withCount('lessons')
            ->orderBy('order')
            ->get()
            ->map(function ($topic) {
                return [
                    'id' => $topic->id,
                    'title' => $topic->title,
                    'slug' => $topic->slug,
                    'icon' => $topic->icon,
                    'color' => $topic->color,
                    'order' => $topic->order,
                    'is_public' => $topic->is_public,
                    'exam_category' => $topic->exam_category,
                    'lessons_count' => $topic->lessons_count,
                    'cover_url' => $topic->cover_url, // desde trait HasImages
                ];
            });

        return inertia('Admin/Topics/Index', [
            'series' => [
                'id' => $series->id,
                'title' => $series->title,
            ],
            'topics' => $topics,
        ]);
    }

    /**
     * Muestra el formulario para crear un nuevo topic dentro de una serie.
     *
     * @param  \App\Models\Series  $series
     * @return \Inertia\Response
     */
    public function create(Series $series)
    {
        // Obtener categorías únicas del examen final de la serie (para el campo exam_category)
        $examCategories = $this->getExamCategories($series);

        return inertia('Admin/Topics/Create', [
            'series' => [
                'id' => $series->id,
                'title' => $series->title,
            ],
            'examCategories' => $examCategories,
        ]);
    }

    /**
     * Almacena un nuevo topic en la base de datos.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Series  $series
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, Series $series)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:10',
            'color' => 'nullable|string|max:20',
            'order' => 'nullable|integer|min:0',
            'is_public' => 'boolean',
            'exam_category' => 'nullable|string|max:255',
        ]);

        // Generar slug único dentro de la serie
        $slug = Str::slug($validated['title']);
        $originalSlug = $slug;
        $counter = 1;
        while (Topic::where('series_id', $series->id)->where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }
        $validated['slug'] = $slug;
        $validated['series_id'] = $series->id;
        $validated['order'] = $validated['order'] ?? Topic::where('series_id', $series->id)->max('order') + 1;

        $topic = $series->topics()->create($validated);

        // Si se subió una imagen de portada, procesarla
        if ($request->hasFile('cover_image')) {
            try {
                $topic->replaceCover($request->file('cover_image'), $request->input('cover_alt', ''));
            } catch (\Exception $e) {
                return back()->with('error', 'Error al subir la imagen: ' . $e->getMessage())->withInput();
            }
        }

        return redirect()->route('admin.topics.index', $series)
            ->with('success', 'Topic creado correctamente.');
    }

    /**
     * Muestra los detalles de un topic específico (con sus lecciones).
     *
     * @param  \App\Models\Topic  $topic
     * @return \Inertia\Response
     */
    public function show(Topic $topic)
    {
        $topic->load(['lessons' => function ($query) {
            $query->orderBy('order');
        }]);

        return inertia('Admin/Topics/Show', [
            'topic' => [
                'id' => $topic->id,
                'title' => $topic->title,
                'slug' => $topic->slug,
                'description' => $topic->description,
                'icon' => $topic->icon,
                'color' => $topic->color,
                'order' => $topic->order,
                'is_public' => $topic->is_public,
                'exam_category' => $topic->exam_category,
                'cover_url' => $topic->cover_url,
                'lessons' => $topic->lessons->map(function ($lesson) {
                    return [
                        'id' => $lesson->id,
                        'title' => $lesson->title,
                        'slug' => $lesson->slug,
                        'order' => $lesson->order,
                        'duration_minutes' => $lesson->duration_minutes,
                        'is_preview' => $lesson->is_preview,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Muestra el formulario de edición de un topic.
     *
     * @param  \App\Models\Topic  $topic
     * @return \Inertia\Response
     */
    public function edit(Topic $topic)
    {
        $examCategories = $this->getExamCategories($topic->series);

        return inertia('Admin/Topics/Edit', [
            'topic' => [
                'id' => $topic->id,
                'title' => $topic->title,
                'description' => $topic->description,
                'icon' => $topic->icon,
                'color' => $topic->color,
                'order' => $topic->order,
                'is_public' => $topic->is_public,
                'exam_category' => $topic->exam_category,
                'cover_url' => $topic->cover_url,
            ],
            'examCategories' => $examCategories,
        ]);
    }

    /**
     * Actualiza los datos de un topic.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Topic  $topic
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Topic $topic)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:10',
            'color' => 'nullable|string|max:20',
            'order' => 'nullable|integer|min:0',
            'is_public' => 'boolean',
            'exam_category' => 'nullable|string|max:255',
        ]);

        // Actualizar slug solo si el título cambió
        if ($validated['title'] !== $topic->title) {
            $slug = Str::slug($validated['title']);
            $originalSlug = $slug;
            $counter = 1;
            while (Topic::where('series_id', $topic->series_id)->where('slug', $slug)->where('id', '!=', $topic->id)->exists()) {
                $slug = $originalSlug . '-' . $counter++;
            }
            $validated['slug'] = $slug;
        }

        $topic->update($validated);

        // Si se subió una nueva imagen, reemplazar la anterior
        if ($request->hasFile('cover_image')) {
            try {
                $topic->replaceCover($request->file('cover_image'), $request->input('cover_alt', ''));
            } catch (\Exception $e) {
                return back()->with('error', 'Error al actualizar la imagen: ' . $e->getMessage())->withInput();
            }
        }

        return redirect()->route('admin.topics.index', $topic->series)
            ->with('success', 'Topic actualizado correctamente.');
    }

    /**
     * Elimina un topic (junto con sus lecciones en cascada).
     *
     * @param  \App\Models\Topic  $topic
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Topic $topic)
    {
        $seriesId = $topic->series_id;
        $topic->deleteCover();
        $topic->delete();

        return redirect()->route('admin.topics.index', $seriesId)
            ->with('success', 'Topic eliminado correctamente.');
    }

    /**
     * Reordena los topics de una serie (drag & drop).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Series  $series
     * @return \Illuminate\Http\JsonResponse
     */
    public function reorder(Request $request, Series $series): JsonResponse
    {
        $request->validate([
            'orderedIds' => 'required|array',
            'orderedIds.*' => 'integer|exists:topics,id',
        ]);

        foreach ($request->orderedIds as $index => $id) {
            Topic::where('id', $id)->where('series_id', $series->id)->update(['order' => $index + 1]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Subir imagen de portada para el topic (endpoint AJAX).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Topic  $topic
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadCover(Request $request, Topic $topic): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,webp|max:2048',
            'alt_text' => 'nullable|string|max:255'
        ]);

        try {
            $image = $topic->replaceCover($request->file('image'), $request->input('alt_text', ''));
            return response()->json([
                'success' => true,
                'url' => $image->url,
                'message' => 'Imagen subida correctamente.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Eliminar imagen de portada del topic (endpoint AJAX).
     *
     * @param  \App\Models\Topic  $topic
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteCover(Topic $topic): JsonResponse
    {
        $topic->deleteCover();
        return response()->json([
            'success' => true,
            'message' => 'Imagen eliminada correctamente.'
        ]);
    }

    /**
     * Obtiene las categorías únicas del examen final de la serie
     * para llenar el select de exam_category en el formulario.
     *
     * @param  \App\Models\Series  $series
     * @return array
     */
    private function getExamCategories(Series $series): array
    {
        // Buscar el examen final de la serie (is_final_exam = true)
        $finalExam = $series->exams()->where('is_final_exam', true)->first();
        if (!$finalExam || empty($finalExam->json_schema)) {
            return [];
        }

        // Extraer categorías de las preguntas
        $categories = [];
        if (isset($finalExam->json_schema['questions']) && is_array($finalExam->json_schema['questions'])) {
            foreach ($finalExam->json_schema['questions'] as $question) {
                if (!empty($question['category'])) {
                    $categories[] = $question['category'];
                }
            }
        }

        return array_values(array_unique($categories));
    }
}