<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Series;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controlador del catálogo de evaluaciones para estudiantes.
 *
 * Muestra todas las evaluaciones publicadas agrupadas por serie,
 * permite filtrar por texto, dominio, tipo y estado personal,
 * e incluye información del progreso del estudiante.
 *
 * CORRECCIONES APLICADAS:
 * 1. Eliminado N+1 — toda la información del estudiante se carga en 2 queries
 *    globales en lugar de una query por examen dentro del foreach
 * 2. Filtro personal_status corregido — usa los datos ya cargados en memoria
 * 3. best_passed devuelve null cuando no hay intentos (no false)
 * 4. has_in_progress solo considera 'active' y 'pending' (no 'abandoned')
 * 5. Corregido typo: orWhere('description', 'like', "%{$search}") → "%{$search}%"
 * 6. Eliminado método show() que referenciaba vista inexistente
 */
class CatalogController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // ─────────────────────────────────────────────────────────────────────
        // 1. Cargar TODOS los intentos del usuario en una sola query
        //    Esto elimina el N+1 — no hay queries dentro del foreach
        // ─────────────────────────────────────────────────────────────────────
        $userAttempts = Attempt::with('result')
            ->where('user_id', $user->id)
            ->get()
            ->groupBy('exam_id');

        // Cargar los mejores resultados del usuario en una sola query
        $userBestResults = ExamResult::where('user_id', $user->id)
            ->orderBy('percentage', 'desc')
            ->get()
            ->groupBy('exam_id')
            ->map(fn ($results) => $results->first()); // mejor resultado por examen

        // ─────────────────────────────────────────────────────────────────────
        // 2. Construir la query base de exámenes publicados
        // ─────────────────────────────────────────────────────────────────────
        $examsQuery = Exam::with('series')
            ->where('status', 'published')
            ->orderBy('created_at', 'desc');

        // ─────────────────────────────────────────────────────────────────────
        // 3. Aplicar filtros de búsqueda y categoría (a nivel de DB)
        // ─────────────────────────────────────────────────────────────────────

        // Búsqueda por texto
        if ($search = $request->get('search')) {
            $examsQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%") // CORRECCIÓN: % al final
                  ->orWhereHas('series', function ($sq) use ($search) {
                      $sq->where('title', 'like', "%{$search}%");
                  });
            });
        }

        // Filtro por dominio temático
        if ($domain = $request->get('domain')) {
            $examsQuery->whereHas('series', fn ($q) => $q->where('domain', $domain));
        }

        // Filtro por tipo de evaluación
        if ($type = $request->get('type')) {
            $examsQuery->where('type', $type);
        }

        // Cargar todos los exámenes que pasan los filtros anteriores
        $exams = $examsQuery->get();

        // ─────────────────────────────────────────────────────────────────────
        // 4. Filtro por estado personal — usando datos ya en memoria (sin N+1)
        // ─────────────────────────────────────────────────────────────────────
        if ($personalStatus = $request->get('personal_status')) {
            $exams = $exams->filter(function ($exam) use ($personalStatus, $userAttempts, $userBestResults) {
                $examAttempts   = $userAttempts->get($exam->id, collect());
                $bestResult     = $userBestResults->get($exam->id);
                $hasAnyAttempt  = $examAttempts->isNotEmpty();

                // Intento activo = status 'active' o 'pending' (no abandoned)
                $hasActiveAttempt = $examAttempts
                    ->whereIn('status', ['active', 'pending'])
                    ->isNotEmpty();

                return match ($personalStatus) {
                    'no_attempts'  => !$hasAnyAttempt,
                    'approved'     => $bestResult && $bestResult->passed === true,
                    'failed'       => $hasAnyAttempt && ($bestResult === null || $bestResult->passed === false),
                    'in_progress'  => $hasActiveAttempt,
                    default        => true,
                };
            });
        }

        // ─────────────────────────────────────────────────────────────────────
        // 5. Agrupar por serie y enriquecer con datos del estudiante
        //    Todo usa datos ya cargados en memoria — cero queries adicionales
        // ─────────────────────────────────────────────────────────────────────
        $seriesData = [];

        foreach ($exams as $exam) {
            $seriesId = $exam->series_id;

            if (!isset($seriesData[$seriesId])) {
                $seriesData[$seriesId] = [
                    'series' => [
                        'id'     => $exam->series->id,
                        'title'  => $exam->series->title,
                        'domain' => $exam->series->domain,
                    ],
                    'exams' => [],
                ];
            }

            // Datos del usuario para este examen — todo desde memoria
            $examAttempts   = $userAttempts->get($exam->id, collect());
            $bestResult     = $userBestResults->get($exam->id);
            $attemptsCount  = $examAttempts->count();

            // Intento activo o pendiente (el estudiante puede continuar)
            $activeAttempt = $examAttempts
                ->whereIn('status', ['active', 'pending'])
                ->sortByDesc('created_at')
                ->first();

            // Intento abandonado (puede retomarse desde start)
            $abandonedAttempt = $examAttempts
                ->where('status', 'abandoned')
                ->sortByDesc('created_at')
                ->first();

            // Estado personal calculado para el badge visual
            $personalStatus = $this->calculatePersonalStatus(
                $attemptsCount,
                $bestResult,
                $activeAttempt,
                $abandonedAttempt
            );

            $seriesData[$seriesId]['exams'][] = [
                'id'              => $exam->id,
                'title'           => $exam->title,
                'description'     => $exam->description,
                'version'         => $exam->version,
                'type'            => $exam->type,
                'passing_score'   => $exam->json_schema['exam']['passingScore']      ?? 70,
                'time_limit'      => $exam->json_schema['exam']['timeLimitMinutes']  ?? null,
                'questions_count' => count($exam->json_schema['questions'] ?? []),
                // Datos del estudiante
                'best_score'      => $bestResult?->percentage,                   // null si no hay intentos
                'best_passed'     => $bestResult ? $bestResult->passed : null,   // null si no hay intentos
                'attempts_count'  => $attemptsCount,
                'personal_status' => $personalStatus,
                'active_attempt_id'    => $activeAttempt?->id,    // para botón "Continuar"
                'abandoned_attempt_id' => $abandonedAttempt?->id, // para indicar retomable
            ];
        }

        // ─────────────────────────────────────────────────────────────────────
        // 6. Opciones para los selects de filtro
        // ─────────────────────────────────────────────────────────────────────
        $availableDomains = Series::whereHas('exams', fn ($q) => $q->where('status', 'published'))
            ->distinct()
            ->pluck('domain')
            ->filter()
            ->values();

        $availableTypes = Exam::where('status', 'published')
            ->distinct()
            ->pluck('type')
            ->values();

        // ─────────────────────────────────────────────────────────────────────
        // 7. Devolver a la vista
        // ─────────────────────────────────────────────────────────────────────
        return inertia('Student/Catalog/Index', [
            'seriesGroups'     => array_values($seriesData),
            'filters'          => [
                'search'          => $request->get('search', ''),
                'domain'          => $request->get('domain', ''),
                'type'            => $request->get('type', ''),
                'personal_status' => $request->get('personal_status', ''),
            ],
            'availableDomains' => $availableDomains,
            'availableTypes'   => $availableTypes,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helper: calcular el estado personal del estudiante para el badge visual
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Calcula el estado visual del examen para el estudiante.
     *
     * Estados posibles:
     * - 'new'         → sin intentos previos
     * - 'in_progress' → tiene intento activo o pendiente ahora mismo
     * - 'abandoned'   → tiene intento abandonado recuperable
     * - 'passed'      → su mejor resultado aprobó
     * - 'failed'      → tiene intentos pero ninguno aprobó
     *
     * @param  int          $attemptsCount
     * @param  ExamResult|null  $bestResult
     * @param  Attempt|null     $activeAttempt
     * @param  Attempt|null     $abandonedAttempt
     * @return string
     */
    private function calculatePersonalStatus(
        int $attemptsCount,
        ?ExamResult $bestResult,
        ?Attempt $activeAttempt,
        ?Attempt $abandonedAttempt
    ): string {
        if ($attemptsCount === 0) {
            return 'new';
        }

        if ($activeAttempt) {
            return 'in_progress';
        }

        if ($bestResult?->passed) {
            return 'passed';
        }

        if ($abandonedAttempt) {
            return 'abandoned';
        }

        return 'failed';
    }
}