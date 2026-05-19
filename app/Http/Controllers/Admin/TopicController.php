<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Series;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

/**
 * Controlador de Topics para el panel de administración.
 *
 * Gestiona las unidades temáticas dentro de una serie.
 * Los topics organizan las lecciones y están vinculados a categorías
 * del examen (exam_category) para generar mini quizzes.
 *
 * CORRECCIONES APLICADAS:
 * 1. index()             → prop 'serie' en singular
 * 2. getExamCategories() → devuelve { category, count }
 * 3. reorder()           → valida 'order' en lugar de 'orderedIds'
 * 4. show()              → agrega 'series', 'excerpt' y 'content' en lessons
 *                          para alimentar correctamente Topics/Show.vue y LessonModal
 */
class TopicController extends Controller
{
    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(Series $series)
    {
        $topics = $series->topics()
            ->withCount('lessons')
            ->orderBy('order')
            ->get()
            ->map(fn ($topic) => [
                'id'            => $topic->id,
                'title'         => $topic->title,
                'slug'          => $topic->slug,
                'description'   => $topic->description,
                'icon'          => $topic->icon,
                'color'         => $topic->color,
                'order'         => $topic->order,
                'is_public'     => $topic->is_public,
                'exam_category' => $topic->exam_category,
                'lessons_count' => $topic->lessons_count,
                'cover_url'     => $topic->cover_url,
            ]);

        return inertia('Admin/Topics/Index', [
            'serie'  => [
                'id'           => $series->id,
                'title'        => $series->title,
                'domain'       => $series->domain,
                'published_at' => $series->published_at,
            ],
            'topics' => $topics,
        ]);
    }

    // =========================================================================
    // CREATE
    // =========================================================================

    public function create(Series $series)
    {
        $examCategories = $this->getExamCategories($series);
        $nextOrder      = Topic::where('series_id', $series->id)->max('order') + 1;
        $tieneExamen    = $series->exams()->where('is_final_exam', true)
                               ->where('status', 'published')->exists();

        return inertia('Admin/Topics/Create', [
            'serie' => [
                'id'           => $series->id,
                'title'        => $series->title,
                'published_at' => $series->published_at,
            ],
            'examCategories' => $examCategories,
            'tieneExamen'    => $tieneExamen,
            'nextOrder'      => $nextOrder,
        ]);
    }

    // =========================================================================
    // STORE
    // =========================================================================

    public function store(Request $request, Series $series)
    {
        $validated = $request->validate([
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'icon'          => 'nullable|string|max:10',
            'color'         => 'nullable|string|max:20',
            'order'         => 'nullable|integer|min:1',
            'is_public'     => 'boolean',
            'exam_category' => 'nullable|string|max:255',
        ]);

        $slug = Str::slug($validated['title']);
        $base = $slug;
        $i    = 1;
        while (Topic::where('series_id', $series->id)->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }
        $validated['slug']      = $slug;
        $validated['series_id'] = $series->id;
        $validated['order']     = $validated['order']
            ?? Topic::where('series_id', $series->id)->max('order') + 1;

        $topic = $series->topics()->create($validated);

        if ($request->hasFile('cover_image')) {
            try {
                $topic->replaceCover(
                    $request->file('cover_image'),
                    $request->input('cover_alt', $topic->title)
                );
            } catch (\Exception $e) {
                return back()
                    ->with('error', 'Topic creado pero falló la imagen: ' . $e->getMessage())
                    ->withInput();
            }
        }

        return redirect()
            ->route('admin.topics.index', $series)
            ->with('success', 'Topic creado correctamente.');
    }

    // =========================================================================
    // SHOW
    // =========================================================================

    public function show(Series $series, Topic $topic)
    {
        // Cargamos lecciones ordenadas + la serie para el breadcrumb de Show.vue
        $topic->load([
            'lessons' => fn ($q) => $q->orderBy('order'),
            'series',
        ]);

        return inertia('Admin/Topics/Show', [
            'topic' => [
                'id'            => $topic->id,
                'title'         => $topic->title,
                'slug'          => $topic->slug,
                'description'   => $topic->description,
                'icon'          => $topic->icon,
                'color'         => $topic->color,
                'order'         => $topic->order,
                'is_public'     => $topic->is_public,
                'exam_category' => $topic->exam_category,
                'cover_url'     => $topic->cover_url,

                // Serie necesaria para breadcrumb y rutas de navegación en Show.vue
                'series' => [
                    'id'    => $topic->series->id,
                    'title' => $topic->series->title,
                    'slug'  => $topic->series->slug,
                ],

                'lessons' => $topic->lessons->map(fn ($l) => [
                    'id'               => $l->id,
                    'title'            => $l->title,
                    'slug'             => $l->slug,
                    'order'            => $l->order,
                    'duration_minutes' => $l->duration_minutes,
                    'is_preview'       => $l->is_preview,

                    // Extracto para LessonCard — primeras palabras sin Markdown
                    // strip_tags elimina HTML residual antes de truncar
                    'excerpt' => Str::limit(
                        strip_tags(preg_replace('/[#*`>_~\[\]]/u', '', $l->content ?? '')),
                        100
                    ),

                    // Contenido completo — necesario para prerellenar el editor
                    // en LessonModal cuando el admin hace click en "Editar"
                    'content' => $l->content,
                ]),
            ],
        ]);
    }

    // =========================================================================
    // EDIT
    // =========================================================================

    public function edit(Series $series, Topic $topic)
    {
        $examCategories = $this->getExamCategories($topic->series);
        $tieneExamen    = $topic->series->exams()->where('is_final_exam', true)
                               ->where('status', 'published')->exists();
        $previewCount   = $topic->lessons()->where('is_preview', true)->count();

        return inertia('Admin/Topics/Edit', [
            'topic' => [
                'id'            => $topic->id,
                'title'         => $topic->title,
                'description'   => $topic->description,
                'icon'          => $topic->icon,
                'color'         => $topic->color,
                'order'         => $topic->order,
                'is_public'     => $topic->is_public,
                'exam_category' => $topic->exam_category,
                'cover_url'     => $topic->cover_url,
            ],
            'serie' => [
                'id'           => $topic->series->id,
                'title'        => $topic->series->title,
                'published_at' => $topic->series->published_at,
            ],
            'examCategories' => $examCategories,
            'tieneExamen'    => $tieneExamen,
            'previewCount'   => $previewCount,
            'esPrimero'      => $topic->order === 1,
            'nextOrder'      => Topic::where('series_id', $topic->series_id)->max('order'),
        ]);
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    public function update(Request $request, Series $series, Topic $topic)
    {
        $validated = $request->validate([
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'icon'          => 'nullable|string|max:10',
            'color'         => 'nullable|string|max:20',
            'order'         => 'nullable|integer|min:1',
            'is_public'     => 'boolean',
            'exam_category' => 'nullable|string|max:255',
        ]);

        if ($validated['title'] !== $topic->title) {
            $slug = Str::slug($validated['title']);
            $base = $slug;
            $i    = 1;
            while (Topic::where('series_id', $topic->series_id)
                        ->where('slug', $slug)
                        ->where('id', '!=', $topic->id)
                        ->exists()) {
                $slug = $base . '-' . $i++;
            }
            $validated['slug'] = $slug;
        }

        $topic->update($validated);

        if ($request->hasFile('cover_image')) {
            try {
                $topic->replaceCover(
                    $request->file('cover_image'),
                    $request->input('cover_alt', $topic->title)
                );
            } catch (\Exception $e) {
                return back()
                    ->with('error', 'Topic actualizado pero falló la imagen: ' . $e->getMessage())
                    ->withInput();
            }
        }

        return redirect()
            ->route('admin.topics.index', $topic->series)
            ->with('success', 'Topic actualizado correctamente.');
    }

    // =========================================================================
    // DESTROY
    // =========================================================================

    public function destroy(Series $series, Topic $topic)
    {
        $series = $topic->series;
        $topic->deleteCover();
        $topic->delete();

        return redirect()
            ->route('admin.topics.index', $series)
            ->with('success', 'Topic eliminado correctamente.');
    }

    // =========================================================================
    // REORDER
    // =========================================================================

    public function reorder(Request $request, Series $series)
    {
        $request->validate([
            'order'   => 'required|array',
            'order.*' => 'integer|exists:topics,id',
        ]);

        foreach ($request->order as $index => $id) {
            Topic::where('id', $id)
                 ->where('series_id', $series->id)
                 ->update(['order' => $index + 1]);
        }

        return back()->with('success', 'Orden actualizado correctamente.');
    }

    // =========================================================================
    // COVER UPLOAD / DELETE (endpoints AJAX — retornan JsonResponse)
    // =========================================================================

    public function uploadCover(Request $request, Topic $topic): JsonResponse
    {
        $request->validate([
            'image'    => 'required|image|mimes:jpeg,png,webp|max:2048',
            'alt_text' => 'nullable|string|max:255',
        ]);

        try {
            $image = $topic->replaceCover(
                $request->file('image'),
                $request->input('alt_text', $topic->title)
            );
            return response()->json([
                'success' => true,
                'url'     => $image->url,
                'message' => 'Imagen subida correctamente.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function deleteCover(Topic $topic): JsonResponse
    {
        $topic->deleteCover();
        return response()->json([
            'success' => true,
            'message' => 'Imagen eliminada correctamente.',
        ]);
    }

    // =========================================================================
    // HELPER PRIVADO
    // =========================================================================

    private function getExamCategories(Series $series): array
    {
        $finalExam = $series->exams()
            ->where('is_final_exam', true)
            ->where('status', 'published')
            ->first();

        if (!$finalExam || empty($finalExam->json_schema['questions'])) {
            return [];
        }

        $counts = [];
        foreach ($finalExam->json_schema['questions'] as $question) {
            if (!empty($question['category'])) {
                $cat          = $question['category'];
                $counts[$cat] = ($counts[$cat] ?? 0) + 1;
            }
        }

        $result = [];
        foreach ($counts as $category => $count) {
            $result[] = ['category' => $category, 'count' => $count];
        }

        return $result;
    }
}