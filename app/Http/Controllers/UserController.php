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
        // Usamos instanceof User para que el analizador de tipos reconozca el método hasPermission
        if ($user instanceof User && $user->hasPermission('gestionar_usuarios')) {
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
            'name' => 'nullable|string|max:255',
            'first_name' => 'required_without:name|string|max:255',
            'second_name' => 'nullable|string|max:255',
            'first_last' => 'required_without:name|string|max:255',
            'second_last' => 'nullable|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'roles_id' => 'nullable',
            'document_type' => ['nullable','regex:/^(R\\.?C|C\\.?C|T\\.?I)$/i'],
            'document_number' => 'nullable|string|max:50',
            'celular' => 'nullable|string|max:30',
        ]);

        // Construir nombre completo si no se envía 'name'
        $fullName = $data['name'] ?? null;
        if (empty($fullName)) {
            $parts = [];
            if (!empty($data['first_name'])) $parts[] = $data['first_name'];
            if (!empty($data['second_name'])) $parts[] = $data['second_name'];
            if (!empty($data['first_last'])) $parts[] = $data['first_last'];
            if (!empty($data['second_last'])) $parts[] = $data['second_last'];
            $fullName = implode(' ', $parts);
        }

        $user = User::create([
            'name' => $fullName,
            'first_name' => $data['first_name'] ?? null,
            'second_name' => $data['second_name'] ?? null,
            'first_last' => $data['first_last'] ?? null,
            'second_last' => $data['second_last'] ?? null,
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'roles_id' => $data['roles_id'] ?? null,
            'document_type' => $data['document_type'] ?? null,
            'document_number' => $data['document_number'] ?? null,
            'celular' => $data['celular'] ?? null,
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
            'name' => 'nullable|string|max:255',
            'first_name' => 'required_without:name|string|max:255',
            'second_name' => 'nullable|string|max:255',
            'first_last' => 'required_without:name|string|max:255',
            'second_last' => 'nullable|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6|confirmed',
            'roles_id' => 'nullable',
            'document_type' => ['nullable','regex:/^(R\\.?C|C\\.?C|T\\.?I)$/i'],
            'document_number' => 'nullable|string|max:50',
            'celular' => 'nullable|string|max:30',
        ]);

        // Construir nombre completo si no se envía 'name'
        $fullName = $data['name'] ?? null;
        if (empty($fullName)) {
            $parts = [];
            if (!empty($data['first_name'])) $parts[] = $data['first_name'];
            if (!empty($data['second_name'])) $parts[] = $data['second_name'];
            if (!empty($data['first_last'])) $parts[] = $data['first_last'];
            if (!empty($data['second_last'])) $parts[] = $data['second_last'];
            $fullName = implode(' ', $parts);
        }

        $user->name = $fullName;
        $user->first_name = $data['first_name'] ?? null;
        $user->second_name = $data['second_name'] ?? null;
        $user->first_last = $data['first_last'] ?? null;
        $user->second_last = $data['second_last'] ?? null;
        $user->email = $data['email'];
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->roles_id = $data['roles_id'] ?? null;
        $user->document_type = $data['document_type'] ?? null;
        $user->document_number = $data['document_number'] ?? null;
        $user->celular = $data['celular'] ?? null;
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

    /**
     * Endpoint JSON para buscar usuarios por nombre o número de documento.
     * Parámetros: q (string)
     */
    public function search(Request $request)
    {
        $this->authorizeManager();
        $q = trim($request->get('q', ''));
        if ($q === '') return response()->json(['data' => []]);

        // Buscar únicamente por número de documento (coincidencia parcial permitida).
        $results = User::where('document_number', 'like', "%{$q}%")
            ->select('id','name','first_name','first_last','document_number')
            ->orderBy('document_number')
            ->limit(50)
            ->get();

        return response()->json(['data' => $results]);
    }
}
