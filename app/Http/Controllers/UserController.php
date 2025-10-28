<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\RolesModel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    protected function authorizeManager()
    {
    $user = Auth::user();
        // Permitir si tiene permiso gestionar_usuarios o es admin por fallback
        if ($user && method_exists($user, 'hasPermission') && $user->hasPermission('gestionar_usuarios')) {
            return true;
        }
        // Fallback legacy
        if ($user && isset($user->roles_id) && (int)$user->roles_id === 1) return true;
        if ($user && optional($user->role)->nombre) {
            $n = optional($user->role)->nombre;
            if (stripos($n, 'admin') !== false || stripos($n, 'administrador') !== false) return true;
        }
        abort(403, 'Acceso no autorizado');
    }

    public function index()
    {
        $this->authorizeManager();
        $users = User::with('role')->orderBy('name')->paginate(25);
        return view('usuarios.index', compact('users'));
    }

    public function create()
    {
        $this->authorizeManager();
        // Intentar cargar roles desde BD, si no hay, usar fallback
        $roles = RolesModel::all();
        if ($roles->isEmpty()) {
            $roles = collect(RolesModel::obtenerRolesSistema())->map(function ($label, $key) {
                return (object)['id' => $key, 'nombre' => $label];
            });
        }
        return view('usuarios.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $this->authorizeManager();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'roles_id' => 'nullable',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'roles_id' => $data['roles_id'] ?? null,
        ]);

        return redirect()->route('usuarios.index')->with('success', 'Usuario creado correctamente.');
    }

    public function edit($id)
    {
        $this->authorizeManager();
        $user = User::findOrFail($id);
        $roles = RolesModel::all();
        if ($roles->isEmpty()) {
            $roles = collect(RolesModel::obtenerRolesSistema())->map(function ($label, $key) {
                return (object)['id' => $key, 'nombre' => $label];
            });
        }
        return view('usuarios.edit', compact('user', 'roles'));
    }

    public function update(Request $request, $id)
    {
        $this->authorizeManager();
        $user = User::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6|confirmed',
            'roles_id' => 'nullable',
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->roles_id = $data['roles_id'] ?? null;
        $user->save();

        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy($id)
    {
        $this->authorizeManager();
        $user = User::findOrFail($id);
        $user->delete();
        return redirect()->route('usuarios.index')->with('success', 'Usuario eliminado correctamente.');
    }
}
