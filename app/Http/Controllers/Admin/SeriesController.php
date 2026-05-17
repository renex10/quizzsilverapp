<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Series;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Controlador de Series para el panel de administración.
 * 
 * Gestiona los agrupadores temáticos de evaluaciones.
 * Solo accesible por usuarios con rol 'admin'.
 */
class SeriesController extends Controller
{
    /**
     * Muestra el listado de series con el conteo de exámenes asociados.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $series = Series::withCount('exams')
            ->orderBy('title')
            ->get()
            ->map(function ($series) {
                return [
                    'id' => $series->id,
                    'title' => $series->title,
                    'description' => $series->description,
                    'domain' => $series->domain,
                    'exams_count' => $series->exams_count,
                    'created_at' => $series->created_at->format('Y-m-d H:i'),
                ];
            });

        return inertia('Admin/Series/Index', [
            'series' => $series,
        ]);
    }

    /**
     * Muestra el formulario para crear una nueva serie.
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        return inertia('Admin/Series/Create');
    }

    /**
     * Almacena una nueva serie en la base de datos.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255|unique:series,title',
            'description' => 'nullable|string',
            'domain' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        Series::create($validator->validated());

        return redirect()->route('admin.series.index')
            ->with('success', 'Serie creada correctamente.');
    }

    /**
     * Muestra una serie específica con sus exámenes asociados.
     *
     * @param  \App\Models\Series  $series
     * @return \Inertia\Response
     */
    public function show(Series $series)
    {
        $series->load(['exams' => function ($query) {
            $query->orderBy('created_at', 'desc');
        }]);

        return inertia('Admin/Series/Show', [
            'series' => [
                'id' => $series->id,
                'title' => $series->title,
                'description' => $series->description,
                'domain' => $series->domain,
                'created_at' => $series->created_at->format('Y-m-d H:i'),
                'exams' => $series->exams->map(function ($exam) {
                    return [
                        'id' => $exam->id,
                        'title' => $exam->title,
                        'version' => $exam->version,
                        'type' => $exam->type,
                        'status' => $exam->status,
                        'created_at' => $exam->created_at->format('Y-m-d H:i'),
                    ];
                }),
            ],
        ]);
    }

    /**
     * Muestra el formulario de edición de una serie.
     *
     * @param  \App\Models\Series  $series
     * @return \Inertia\Response
     */
    public function edit(Series $series)
    {
        return inertia('Admin/Series/Edit', [
            'series' => [
                'id' => $series->id,
                'title' => $series->title,
                'description' => $series->description,
                'domain' => $series->domain,
            ],
        ]);
    }

    /**
     * Actualiza los datos de la serie.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Series  $series
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Series $series)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255|unique:series,title,' . $series->id,
            'description' => 'nullable|string',
            'domain' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $series->update($validator->validated());

        return redirect()->route('admin.series.index')
            ->with('success', 'Serie actualizada correctamente.');
    }

    /**
     * Elimina una serie. Solo permite eliminar si no tiene exámenes asociados.
     *
     * @param  \App\Models\Series  $series
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Series $series)
    {
        if ($series->exams()->count() > 0) {
            return back()->with('error', 'No se puede eliminar la serie porque tiene exámenes asociados.');
        }

        $series->delete();

        return redirect()->route('admin.series.index')
            ->with('success', 'Serie eliminada correctamente.');
    }
}