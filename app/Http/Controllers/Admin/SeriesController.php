<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Series;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Controlador de Series para el panel de administración.
 *
 * Gestiona los agrupadores temáticos de evaluaciones.
 * Solo accesible por usuarios con rol 'admin'.
 *
 * Extendido en Sesión 4 para soportar:
 * - Nuevos campos: slug, long_description, difficulty, estimated_hours, is_featured, published_at
 * - Sistema de imágenes polimórfico (uploadCover, deleteCover)
 *
 * CORRECCIÓN Sesión 5:
 * - index() → agrega withCount('topics') y topics_count en el map
 *   para que Series/Index.vue muestre el contador de topics por serie
 */
class SeriesController extends Controller
{
    public function index()
    {
        $series = Series::withCount(['exams', 'topics'])  // ← agregado 'topics'
            ->orderBy('title')
            ->get()
            ->map(function ($series) {
                return [
                    'id'           => $series->id,
                    'title'        => $series->title,
                    'slug'         => $series->slug,
                    'description'  => $series->description,
                    'domain'       => $series->domain,
                    'difficulty'   => $series->difficulty,
                    'is_featured'  => $series->is_featured,
                    'published_at' => $series->published_at?->format('Y-m-d'),
                    'exams_count'  => $series->exams_count,
                    'topics_count' => $series->topics_count,  // ← agregado
                    'cover_url'    => $series->cover_url,
                    'created_at'   => $series->created_at->format('Y-m-d H:i'),
                ];
            });

        return inertia('Admin/Series/Index', [
            'series' => $series,
        ]);
    }

    public function create()
    {
        return inertia('Admin/Series/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'           => 'required|string|max:255|unique:series,title',
            'description'     => 'nullable|string',
            'domain'          => 'required|string|max:255',
            'long_description'=> 'nullable|string',
            'difficulty'      => 'required|in:básico,intermedio,avanzado',
            'estimated_hours' => 'nullable|numeric|min:0|max:999.9',
            'is_featured'     => 'boolean',
            'published_at'    => 'nullable|date',
        ]);

        $series = Series::create($validated);

        if ($request->hasFile('cover_image')) {
            try {
                $series->replaceCover(
                    $request->file('cover_image'),
                    $request->input('cover_alt', '')
                );
            } catch (\Exception $e) {
                return back()
                    ->with('error', 'Error al subir la imagen: ' . $e->getMessage())
                    ->withInput();
            }
        }

        return redirect()->route('admin.series.index')
            ->with('success', 'Serie creada correctamente.');
    }

    public function show(Series $series)
    {
        $series->load(['exams' => function ($query) {
            $query->orderBy('created_at', 'desc');
        }]);

        return inertia('Admin/Series/Show', [
            'series' => [
                'id'               => $series->id,
                'title'            => $series->title,
                'slug'             => $series->slug,
                'description'      => $series->description,
                'long_description' => $series->long_description,
                'domain'           => $series->domain,
                'difficulty'       => $series->difficulty,
                'estimated_hours'  => $series->estimated_hours,
                'is_featured'      => $series->is_featured,
                'published_at'     => $series->published_at?->format('Y-m-d'),
                'cover_url'        => $series->cover_url,
                'created_at'       => $series->created_at->format('Y-m-d H:i'),
                'exams'            => $series->exams->map(function ($exam) {
                    return [
                        'id'         => $exam->id,
                        'title'      => $exam->title,
                        'version'    => $exam->version,
                        'type'       => $exam->type,
                        'status'     => $exam->status,
                        'created_at' => $exam->created_at->format('Y-m-d H:i'),
                    ];
                }),
            ],
        ]);
    }

    public function edit(Series $series)
    {
        return inertia('Admin/Series/Edit', [
            'series' => [
                'id'               => $series->id,
                'title'            => $series->title,
                'slug'             => $series->slug,
                'description'      => $series->description,
                'long_description' => $series->long_description,
                'domain'           => $series->domain,
                'difficulty'       => $series->difficulty,
                'estimated_hours'  => $series->estimated_hours,
                'is_featured'      => $series->is_featured,
                'published_at'     => $series->published_at?->format('Y-m-d'),
                'cover_url'        => $series->cover_url,
            ],
        ]);
    }

    public function update(Request $request, Series $series)
    {
        $validated = $request->validate([
            'title'            => 'required|string|max:255|unique:series,title,' . $series->id,
            'description'      => 'nullable|string',
            'domain'           => 'required|string|max:255',
            'long_description' => 'nullable|string',
            'difficulty'       => 'required|in:básico,intermedio,avanzado',
            'estimated_hours'  => 'nullable|numeric|min:0|max:999.9',
            'is_featured'      => 'boolean',
            'published_at'     => 'nullable|date',
        ]);

        $series->update($validated);

        if ($request->hasFile('cover_image')) {
            try {
                $series->replaceCover(
                    $request->file('cover_image'),
                    $request->input('cover_alt', '')
                );
            } catch (\Exception $e) {
                return back()
                    ->with('error', 'Error al actualizar la imagen: ' . $e->getMessage())
                    ->withInput();
            }
        }

        return redirect()->route('admin.series.index')
            ->with('success', 'Serie actualizada correctamente.');
    }

    public function destroy(Series $series)
    {
        if ($series->exams()->count() > 0) {
            return back()->with('error', 'No se puede eliminar la serie porque tiene exámenes asociados.');
        }

        $series->deleteCover();
        $series->delete();

        return redirect()->route('admin.series.index')
            ->with('success', 'Serie eliminada correctamente.');
    }

    public function uploadCover(Request $request, Series $series): JsonResponse
    {
        $request->validate([
            'image'    => 'required|image|mimes:jpeg,png,webp|max:2048',
            'alt_text' => 'nullable|string|max:255',
        ]);

        try {
            $image = $series->replaceCover(
                $request->file('image'),
                $request->input('alt_text', '')
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

    public function deleteCover(Series $series): JsonResponse
    {
        $series->deleteCover();
        return response()->json([
            'success' => true,
            'message' => 'Imagen eliminada correctamente.',
        ]);
    }
}