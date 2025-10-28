<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\RolesModel;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CrearUsuario;
use App\Http\Controllers\RolController;
use App\Http\Controllers\GestionAcademicaController;
use App\Http\Controllers\InstitucionController; // 👈 Importa el nuevo controlador
use App\Http\Controllers\MatriculaController; // Importa el controlador de Matrículas

// Ruta raíz redirige al login
Route::get('/', function () {
    return redirect('/login');
});

// Rutas de autenticación
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Rutas Crear usuarios
Route::get('/registro', [CrearUsuario::class, 'showRegistrationForm'])->name('register');
Route::post('/registro', [CrearUsuario::class, 'register']);

// Rutas protegidas por autenticación
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');

    // Perfil del usuario autenticado (cargando relaciones necesarias)
    Route::get('/perfil', function () {
        $user = User::findOrFail(Auth::id())->load(['role', 'acudientes', 'acudiente']);
        return view('profile', ['user' => $user]);
    })->name('perfil');

    // Editar perfil (formulario) — cargamos relaciones por si es necesario
    Route::get('/perfil/editar', function () {
        $user = User::findOrFail(Auth::id())->load(['role', 'acudientes', 'acudiente']);
        return view('profile_edit', ['user' => $user]);
    })->name('perfil.editar');

    // Actualizar perfil
    Route::put('/perfil', function (Request $request) {
        // Aseguramos obtener el modelo User desde la BD para evitar null o tipos inesperados
        $user = User::findOrFail(Auth::id());

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if (!empty($validated['password'])) {
            // El casteo 'password' => 'hashed' en el modelo User hará el hash automáticamente.
            $user->password = $validated['password'];
        }

        $user->save();

        return redirect()->route('perfil')->with('success', 'Perfil actualizado correctamente.');
    })->name('perfil.update');

    // Crear estudiante — solo para acudientes
    Route::get('/perfil/crear-estudiante', function () {
        $user = User::findOrFail(Auth::id());
        // solo permitir si el usuario es Acudiente
        if (optional($user->role)->nombre !== 'Acudiente') {
            abort(403, 'Acceso no autorizado');
        }
        return view('profile_create_student');
    })->name('perfil.crear_estudiante');

    Route::post('/perfil/crear-estudiante', function (Request $request) {
        $user = User::findOrFail(Auth::id());
        if (optional($user->role)->nombre !== 'Acudiente') {
            abort(403, 'Acceso no autorizado');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        // Obtener id del rol Estudiante
        $rolEstudiante = RolesModel::where('nombre', 'Estudiante')->first();
        $rolId = $rolEstudiante ? $rolEstudiante->id : null;

        $newUser = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'roles_id' => $rolId,
            // acudiente_id lo asignamos explícitamente después para evitar problemas de mass-assignment
        ]);

        // Forzar asignación y guardado del acudiente_id
        $newUser->acudiente_id = $user->id;
        $newUser->save();

        return redirect()->route('perfil')->with('success', 'Estudiante creado correctamente.');
    })->name('perfil.crear_estudiante.post');

    // Rutas para administrar roles
    Route::resource('roles', RolController::class);
    Route::get('roles-permisos', [RolController::class, 'permisosDisponibles'])->name('roles.permisos');
    // Gestión académica (páginas básicas)
    Route::get('gestion-academica', [GestionAcademicaController::class, 'index'])->name('gestion.index');
    Route::get('gestion-academica/crear-curso', [GestionAcademicaController::class, 'crearCurso'])->name('gestion.crearCurso');
    Route::get('gestion-academica/editar-curso', [GestionAcademicaController::class, 'editarCurso'])->name('gestion.editarCurso');
    Route::get('gestion-academica/horarios', [GestionAcademicaController::class, 'horarios'])->name('gestion.horarios');
    Route::post('gestion-academica/horarios', [GestionAcademicaController::class, 'guardarHorario'])->name('horarios.guardar');

    
    // 👇 NUEVAS RUTAS DE GESTIÓN INSTITUCIONAL
    Route::get('/institucion', [InstitucionController::class, 'index'])->name('institucion.index');
    Route::post('/institucion', [InstitucionController::class, 'store'])->name('institucion.store');
    Route::put('/institucion/{id}', [InstitucionController::class, 'update'])->name('institucion.update');
    Route::get('gestion-academica/horarios/{id}/editar', [GestionAcademicaController::class, 'editarHorario'])->name('horarios.editar');
    Route::put('gestion-academica/horarios/{id}', [GestionAcademicaController::class, 'actualizarHorario'])->name('horarios.actualizar');
    Route::delete('gestion-academica/horarios/{id}', [GestionAcademicaController::class, 'eliminarHorario'])->name('horarios.eliminar');


    // 📘 CURSOS
    Route::get('gestion-academica/cursos', [GestionAcademicaController::class, 'panelCursos'])->name('cursos.panel');
    
    Route::post('gestion-academica/cursos', [GestionAcademicaController::class, 'guardarCurso'])->name('guardarCurso');
    Route::get('gestion-academica/cursos/{id}/editar', [GestionAcademicaController::class, 'editarCurso'])->name('editarCurso');
    // Ruta para actualizar un curso (PUT)
    Route::put('gestion-academica/cursos/{id}', [GestionAcademicaController::class, 'actualizarCurso'])->name('actualizarCurso');
    Route::delete('gestion-academica/cursos/{id}', [GestionAcademicaController::class, 'eliminarCurso'])->name('eliminarCurso');


    // Vista para gestionar la institución
    Route::view('/institucion/gestion', 'institucion')->name('institucion.gestion');

    // Rutas para administrar matrículas
    Route::resource('matriculas', MatriculaController::class);
    // Servir archivos de matrículas (visualización/descarga) desde el disco configurado
    Route::get('matriculas/{matricula}/archivo/{campo}', [MatriculaController::class, 'archivo'])
        ->name('matriculas.archivo');
});
