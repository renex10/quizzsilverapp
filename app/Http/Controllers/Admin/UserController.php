<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamResult;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Controlador de gestión de usuarios (admin).
 *
 * Permite listar, ver, crear, editar, activar/desactivar y eliminar usuarios.
 * Incluye historial completo de intentos y preguntas más falladas por usuario.
 */
class UserController extends Controller
{
    // =========================================================================
    // INDEX — listado paginado con filtros
    // =========================================================================

    public function index(Request $request)
    {
        $query = User::with('role');

        // Por defecto muestra solo estudiantes
        if ($roleName = $request->get('role')) {
            $query->whereHas('role', fn ($q) => $q->where('name', $roleName));
        } else {
            $query->whereHas('role', fn ($q) => $q->where('name', 'student'));
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(6)->withQueryString();

        $users->getCollection()->transform(function ($user) {
            /** @var \App\Models\User $user */
            return [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'role'       => $user->role->name,
                'is_active'  => $user->is_active ?? true,
                'created_at' => $user->created_at->format('d/m/Y H:i'),
            ];
        });

        $roles = Role::all(['id', 'name']);

        return inertia('Admin/Users/Index', [
            'users'   => $users,
            'filters' => [
                'role'   => $request->get('role', ''),
                'search' => $request->get('search', ''),
            ],
            'roles' => $roles,
        ]);
    }

    // =========================================================================
    // SHOW — detalle con historial y preguntas más falladas
    // =========================================================================

    public function show(int $id)
    {
        /** @var \App\Models\User $user */
        $user = User::with(['role', 'attempts.exam.series', 'attempts.result'])
            ->findOrFail($id);

        $userData = [
            'id'         => $user->id,
            'name'       => $user->name,
            'email'      => $user->email,
            'role'       => $user->role->name,
            'role_id'    => $user->role_id,
            'is_active'  => $user->is_active ?? true,
            'created_at' => $user->created_at->format('d/m/Y H:i'),
        ];

        $attempts = $user->attempts->map(function ($attempt) {
            return [
                'id'           => $attempt->id,
                'exam_title'   => $attempt->exam->title,
                'series_title' => $attempt->exam->series->title,
                'status'       => $attempt->status,
                'started_at'   => $attempt->started_at?->format('d/m/Y H:i'),
                'completed_at' => $attempt->completed_at?->format('d/m/Y H:i'),
                'percentage'   => $attempt->result?->percentage,
                'passed'       => $attempt->result?->passed,
            ];
        });

        $totalAttempts    = $user->attempts->where('status', 'completed')->count();
        $approvedAttempts = $user->attempts->filter(fn ($a) => $a->result?->passed)->count();
        $approvalRate     = $totalAttempts > 0
            ? round(($approvedAttempts / $totalAttempts) * 100, 2)
            : 0;

        // Preguntas más falladas — consultamos ExamResult directamente
        // en lugar de $user->examResults() para evitar dependencia de la relación
        $examResults = ExamResult::where('user_id', $user->id)
            ->whereNotNull('detail')
            ->get(['detail']);

        $failedQuestions = [];
        foreach ($examResults as $result) {
            $detail = is_array($result->detail)
                ? $result->detail
                : json_decode($result->detail, true);

            if (!isset($detail['incorrectQuestions'])) continue;

            foreach ($detail['incorrectQuestions'] as $incorrect) {
                $key = $incorrect['questionId'] . '|' . ($incorrect['questionText'] ?? '');
                if (!isset($failedQuestions[$key])) {
                    $failedQuestions[$key] = [
                        'question_text' => $incorrect['questionText'] ?? 'Sin texto',
                        'count'         => 0,
                    ];
                }
                $failedQuestions[$key]['count']++;
            }
        }
        usort($failedQuestions, fn ($a, $b) => $b['count'] - $a['count']);

        return inertia('Admin/Users/Show', [
            'user'     => $userData,
            'attempts' => $attempts,
            'stats'    => [
                'total_attempts'    => $totalAttempts,
                'approved_attempts' => $approvedAttempts,
                'approval_rate'     => $approvalRate,
            ],
            'top_failed_questions' => array_values(array_slice($failedQuestions, 0, 10)),
        ]);
    }

    // =========================================================================
    // CREATE / STORE
    // =========================================================================

    public function create()
    {
        $roles = Role::all(['id', 'name']);

        return inertia('Admin/Users/Create', [
            'roles' => $roles,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role_id'  => 'required|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'role_id'   => $request->role_id,
            'is_active' => true,
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    // =========================================================================
    // EDIT / UPDATE
    // =========================================================================

    public function edit(int $id)
    {
        /** @var \App\Models\User $user */
        $user  = User::findOrFail($id);
        $roles = Role::all(['id', 'name']);

        return inertia('Admin/Users/Edit', [
            'user' => [
                'id'      => $user->id,
                'name'    => $user->name,
                'email'   => $user->email,
                'role_id' => $user->role_id,
            ],
            'roles' => $roles,
        ]);
    }

    public function update(Request $request, int $id)
    {
        /** @var \App\Models\User $user */
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => ['required', 'string', 'email', 'max:255',
                           Rule::unique('users')->ignore($user->id)],
            'role_id'  => 'required|exists:roles,id',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.users.show', $user->id)
            ->with('success', 'Usuario actualizado correctamente.');
    }

    // =========================================================================
    // TOGGLE ACTIVE
    // =========================================================================

    public function toggleActive(int $id)
    {
        /** @var \App\Models\User $user */
        $user = User::findOrFail($id);

        // Protección: no desactivar la cuenta propia
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        if ($user->id === $authUser->id) {
            return back()->with('error', 'No podés desactivar tu propia cuenta.');
        }

        $user->is_active = !($user->is_active ?? true);
        $user->save();

        $status = $user->is_active ? 'activada' : 'desactivada';
        return back()->with('success', "Cuenta {$status} correctamente.");
    }

    // =========================================================================
    // DESTROY
    // =========================================================================

    public function destroy(int $id)
    {
        /** @var \App\Models\User $user */
        $user = User::findOrFail($id);

        // Protección: no eliminar la cuenta propia
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        if ($user->id === $authUser->id) {
            return back()->with('error', 'No podés eliminar tu propia cuenta.');
        }

        if ($user->attempts()->count() > 0) {
            return back()->with('error', 'No se puede eliminar porque tiene intentos registrados.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuario eliminado correctamente.');
    }
}