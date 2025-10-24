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
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Intentar obtener el rol 'Acudiente'
        $rol = RolesModel::where('nombre', 'Acudiente')->first();
        $rolesId = $rol ? $rol->id : null;

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'roles_id' => $rolesId,
        ]);

        // Loguear el usuario recién creado
        Auth::login($user);

        return redirect('/dashboard');
    }

}
