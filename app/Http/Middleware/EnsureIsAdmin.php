<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->load('role');

        if (!$user->role || $user->role->name !== 'admin') {
            return redirect()->route('student.dashboard')->with('error', 'Acceso denegado. No tienes permisos de administrador.');
        }

        return $next($request);
    }
}