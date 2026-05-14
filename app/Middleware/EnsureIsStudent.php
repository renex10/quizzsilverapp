<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsStudent
{
    /**
     * Permite acceso a estudiantes y también a administradores (porque el admin puede hacer exámenes).
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        // Si el rol es 'student' o 'admin', se permite
        if (!$user->role || !in_array($user->role->name, ['student', 'admin'])) {
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }

        return $next($request);
    }
}