<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsAdmin
{
    /**
     * Verifica que el usuario autenticado tenga rol 'admin'.
     * Si no es admin, redirige al dashboard del estudiante.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        // Asumiendo que $user->role es la relación con el modelo Role
        if (!$user->role || $user->role->name !== 'admin') {
            // Redirige a la ruta del dashboard de estudiante (la crearemos después)
            return redirect()->route('student.dashboard')->with('error', 'Acceso denegado.');
        }

        return $next($request);
    }
}