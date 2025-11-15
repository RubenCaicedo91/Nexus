<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\RolesModel;

class CrearUsuario extends Controller
{
   public function showRegistrationForm()
    {
        return view('registro.registro');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'first_name' => 'required_without:name|string|max:255',
            'second_name' => 'nullable|string|max:255',
            'first_last' => 'required_without:name|string|max:255',
            'second_last' => 'nullable|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'document_type' => ['nullable','regex:/^(R\\.?C|C\\.?C|T\\.?I)$/i'],
            'document_number' => 'nullable|string|max:50',
            'celular' => 'nullable|string|max:30',
        ]);

        // Intentar obtener el rol 'Acudiente'
        $rol = RolesModel::where('nombre', 'Acudiente')->first();
        $rolesId = $rol ? $rol->id : null;

        // Construir nombre completo legacy si no se envía name
        $fullName = $request->input('name');
        if (empty($fullName)) {
            $parts = [];
            if ($request->filled('first_name')) $parts[] = $request->first_name;
            if ($request->filled('second_name')) $parts[] = $request->second_name;
            if ($request->filled('first_last')) $parts[] = $request->first_last;
            if ($request->filled('second_last')) $parts[] = $request->second_last;
            $fullName = implode(' ', $parts);
        }

        $userData = [
            'name' => $fullName,
            'first_name' => $request->first_name ?? null,
            'second_name' => $request->second_name ?? null,
            'first_last' => $request->first_last ?? null,
            'second_last' => $request->second_last ?? null,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'roles_id' => $rolesId,
            'document_type' => $request->document_type ?? null,
            'document_number' => $request->document_number ?? null,
            'celular' => $request->celular ?? null,
        ];

        $user = User::create($userData);

        // Loguear el usuario recién creado
        Auth::login($user);

        return redirect('/dashboard');
    }

}
