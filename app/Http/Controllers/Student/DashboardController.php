<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\Exam;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Muestra el panel del estudiante con:
     * - Resumen de progreso
     * - Últimos intentos
     * - Evaluaciones en progreso
     */
    public function index()
    {
        $user = Auth::user();

        // Total de evaluaciones completadas
        $totalCompleted = Attempt::where('user_id', $user->id)
            ->where('status', 'completed')
            ->count();

        // Porcentaje de aprobación (basado en resultados)
        $totalResults = $user->examResults()->count();
        $approvedCount = $user->examResults()->where('passed', true)->count();
        $approvalRate = $totalResults > 0 ? round(($approvedCount / $totalResults) * 100) : 0;

        // Últimos 5 intentos con detalles del examen
        $recentAttempts = Attempt::with(['exam', 'result'])
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->latest()
            ->limit(5)
            ->get();

        // Intentos abandonados (para ofrecer retomar)
        $abandonedAttempts = Attempt::with('exam')
            ->where('user_id', $user->id)
            ->where('status', 'abandoned')
            ->get();

        return inertia('Student/Dashboard/Index', [
            'totalCompleted' => $totalCompleted,
            'approvalRate' => $approvalRate,
            'recentAttempts' => $recentAttempts,
            'abandonedAttempts' => $abandonedAttempts,
        ]);
    }
}