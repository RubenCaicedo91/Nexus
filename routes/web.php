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
use App\Http\Controllers\UserController;
use App\Http\Controllers\GestionAcademicaController;
use App\Http\Controllers\MateriaController;
use App\Http\Controllers\DocenteCursoController;
use App\Http\Controllers\InstitucionController; // 👈 Importa el nuevo controlador
use App\Http\Controllers\MatriculaController; // Importa el controlador de Matrículas
use App\Http\Controllers\GestionFinancieraController;// Importa el controlador de Gestión Financiera
use App\Http\Controllers\GestionOrientacionController; // Importa el controlador de Gestión de Orientación

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

// Ruta temporal de debug (SIN autenticación)
Route::get('/debug-estudiantes-temp', function() {
    $estudiantes = \App\Models\User::join('roles', 'users.roles_id', '=', 'roles.id')
                  ->where('roles.nombre', '=', 'Estudiante')
                  ->select('users.*', 'roles.nombre as rol_nombre')
                  ->orderBy('users.name')
                  ->get();
    return response()->json([
        'estudiantes' => $estudiantes->toArray(), 
        'total' => $estudiantes->count(),
        'query_sql' => 'SELECT users.*, roles.nombre as rol_nombre FROM users JOIN roles ON users.roles_id = roles.id WHERE roles.nombre = "Estudiante" ORDER BY users.name'
    ]);
});

// Ruta temporal de debug HTML (SIN autenticación)
Route::get('/debug-html-temp', function() {
    $estudiantes = \App\Models\User::join('roles', 'users.roles_id', '=', 'roles.id')
                  ->where('roles.nombre', '=', 'Estudiante')
                  ->select('users.*', 'roles.nombre as rol_nombre')
                  ->orderBy('users.name')
                  ->get();
    $cursos = \App\Models\Curso::orderBy('nombre')->get();
    
    return view('asignaciones.test', compact('estudiantes', 'cursos'));
});

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
    // Rutas para administrar usuarios (listar, crear, editar, asignar roles, eliminar)
    Route::resource('usuarios', UserController::class)->except(['show']);
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

    // Rutas para materias (asignar/modificar docentes)
    Route::get('gestion-academica/cursos/{id}/materias', [MateriaController::class, 'index'])->name('cursos.materias');
    Route::post('gestion-academica/cursos/{id}/materias', [MateriaController::class, 'store'])->name('cursos.materias.store');
    Route::get('gestion-academica/materias/{id}/editar', [MateriaController::class, 'edit'])->name('gestion.materias.edit');
    Route::put('gestion-academica/materias/{id}', [MateriaController::class, 'update'])->name('gestion.materias.update');
    Route::post('gestion-academica/materias/crear', [MateriaController::class, 'storeFromModal'])->name('gestion.materias.create');

    // Endpoint JSON para obtener materias de un curso (usado por modal AJAX)
    Route::get('gestion-academica/cursos/{id}/materias-json', [MateriaController::class, 'materiasJson'])->name('cursos.materias.json');
    // Endpoint JSON para obtener una materia concreta
    Route::get('gestion-academica/materias/{id}/json', [MateriaController::class, 'materiaJson'])->name('materias.json');

    // Rutas para asignar cursos a docentes
    Route::get('gestion-academica/docentes', [DocenteCursoController::class, 'index'])->name('docentes.index');
    Route::get('gestion-academica/docentes/{id}/cursos', [DocenteCursoController::class, 'edit'])->name('docentes.edit');
    Route::put('gestion-academica/docentes/{id}/cursos', [DocenteCursoController::class, 'update'])->name('docentes.update');
    Route::post('gestion-academica/docentes/asignar', [DocenteCursoController::class, 'assign'])->name('docentes.asignar');


    // Vista para gestionar la institución
    Route::view('/institucion/gestion', 'institucion')->name('institucion.gestion');

    // Rutas para administrar matrículas
    Route::resource('matriculas', MatriculaController::class);
    // Servir archivos de matrículas (visualización/descarga) desde el disco configurado
    Route::get('matriculas/{matricula}/archivo/{campo}', [MatriculaController::class, 'archivo'])
        ->name('matriculas.archivo');

    // Rutas de Gestión Financiera
    Route::get('gestion-financiera', [GestionFinancieraController::class, 'index'])->name('financiera.index');
    Route::get('gestion-financiera/registrar-pago', [GestionFinancieraController::class, 'mostrarFormularioPago'])->name('financiera.formularioPago');
    Route::post('gestion-financiera/registrar-pago', [GestionFinancieraController::class, 'registrarPago'])->name('financiera.registrarPago');
    Route::get('gestion-financiera/estado-cuenta/{id}', [GestionFinancieraController::class, 'estadoCuenta'])->name('financiera.estadoCuenta');
    Route::get('gestion-financiera/reporte', [GestionFinancieraController::class, 'generarReporte'])->name('financiera.reporte');

    // Rutas de Gestión de Orientación
    Route::get('gestion-orientacion', [GestionOrientacionController::class, 'index'])->name('orientacion.index');

    // Citas
    Route::get('gestion-orientacion/citas', [GestionOrientacionController::class, 'listarCitas'])->name('orientacion.citas');
    Route::get('gestion-orientacion/citas/crear', [GestionOrientacionController::class, 'crearCita'])->name('orientacion.citas.create');
    Route::post('gestion-orientacion/citas', [GestionOrientacionController::class, 'guardarCita'])->name('orientacion.citas.store');
    Route::patch('gestion-orientacion/citas/{id}/estado', [GestionOrientacionController::class, 'cambiarEstadoCita'])->name('orientacion.citas.estado');

    // Informes
    Route::get('gestion-orientacion/informes', [GestionOrientacionController::class, 'listarInformes'])->name('orientacion.informes');
    Route::get('gestion-orientacion/informes/crear', [GestionOrientacionController::class, 'crearInforme'])->name('orientacion.informes.create');
    Route::post('gestion-orientacion/informes', [GestionOrientacionController::class, 'guardarInforme'])->name('orientacion.informes.store');

    // Seguimientos
    Route::get('gestion-orientacion/seguimientos', [GestionOrientacionController::class, 'listarSeguimientos'])->name('orientacion.seguimientos');
    Route::get('gestion-orientacion/seguimientos/crear', [GestionOrientacionController::class, 'crearSeguimiento'])->name('orientacion.seguimientos.create');
    Route::post('gestion-orientacion/seguimientos', [GestionOrientacionController::class, 'guardarSeguimiento'])->name('orientacion.seguimientos.store');

    // Rutas resource para gestión completa de materias
    Route::resource('materias', \App\Http\Controllers\MateriasController::class);

    // 📋 RUTAS PARA MÓDULO DE ASIGNACIONES DE ESTUDIANTES Y HORARIOS
    Route::prefix('asignaciones')->name('asignaciones.')->group(function () {
        Route::get('/', [\App\Http\Controllers\AsignacionesController::class, 'index'])->name('index');
        Route::get('/crear', [\App\Http\Controllers\AsignacionesController::class, 'create'])->name('create');
        Route::get('/debug-estudiantes', function() {
            $estudiantes = \App\Models\User::join('roles', 'users.roles_id', '=', 'roles.id')
                          ->where('roles.nombre', '=', 'Estudiante')
                          ->select('users.*', 'roles.nombre as rol_nombre')
                          ->orderBy('users.name')
                          ->get();
            return response()->json(['estudiantes' => $estudiantes, 'total' => $estudiantes->count()]);
        })->name('debug');
        Route::post('/', [\App\Http\Controllers\AsignacionesController::class, 'store'])->name('store');
        Route::get('/{asignacion}', [\App\Http\Controllers\AsignacionesController::class, 'show'])->name('show');
        Route::get('/{asignacion}/editar', [\App\Http\Controllers\AsignacionesController::class, 'edit'])->name('edit');
        Route::put('/{asignacion}', [\App\Http\Controllers\AsignacionesController::class, 'update'])->name('update');
        Route::delete('/{asignacion}', [\App\Http\Controllers\AsignacionesController::class, 'destroy'])->name('destroy');
        
        // Endpoints JSON para AJAX
        Route::get('/json/lista', [\App\Http\Controllers\AsignacionesController::class, 'getAsignacionesJson'])->name('json');
        Route::get('/json/curso/{cursoId}/horarios', [\App\Http\Controllers\AsignacionesController::class, 'getCourseSchedule'])->name('curso.horarios');
        Route::get('/json/curso/{cursoId}/estudiantes', [\App\Http\Controllers\AsignacionesController::class, 'getStudentsByCourse'])->name('curso.estudiantes');
        Route::post('/{asignacion}/validar', [\App\Http\Controllers\AsignacionesController::class, 'validateAssignment'])->name('validar');
    });
});

// 🧪 RUTA TEMPORAL DE PRUEBA PARA DEBUG DEL SELECT
Route::get('/test-select-estudiantes', function() {
    $estudiantes = \App\Models\User::join('roles', 'users.roles_id', '=', 'roles.id')
                  ->where('roles.nombre', '=', 'Estudiante')
                  ->select('users.*', 'roles.nombre as rol_nombre')
                  ->orderBy('users.name')
                  ->get();
    
    return view('test-select', compact('estudiantes'));
})->middleware('auth');
