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

// Legacy route name used in views for historial sanciones (kept for compatibility)
Route::get('gestion-disciplinaria/historial/{id}', [\App\Http\Controllers\GestionDisciplinariaController::class, 'historialSanciones'])
    ->name('historial.sanciones')
    ->middleware('auth');

// Rutas de notas (auth)
Route::middleware('auth')->group(function () {
    Route::prefix('notas')->name('notas.')->group(function () {
        Route::get('/', [\App\Http\Controllers\NotasController::class, 'index'])->name('index');
        Route::get('/crear', [\App\Http\Controllers\NotasController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\NotasController::class, 'store'])->name('store');
        Route::get('/{nota}/editar', [\App\Http\Controllers\NotasController::class, 'edit'])->name('edit');
        Route::put('/{nota}', [\App\Http\Controllers\NotasController::class, 'update'])->name('update');
        Route::post('/{nota}/aprobar', [\App\Http\Controllers\NotasController::class, 'approve'])->name('approve');
        Route::get('/reporte', [\App\Http\Controllers\NotasController::class, 'reporte'])->name('reporte');
        // Ver notas por matrícula (estudiante)
        Route::get('/matricula/{matricula}/ver', [\App\Http\Controllers\NotasController::class, 'porMatricula'])->name('matricula.ver');
        // Marcar nota como definitiva
        Route::post('/{nota}/definitiva', [\App\Http\Controllers\NotasController::class, 'marcarDefinitiva'])->name('definitiva');
        // Actividades por nota
        Route::get('/{nota}/actividades', [\App\Http\Controllers\ActividadesController::class, 'index'])->name('actividades.index');
        Route::get('/{nota}/actividades/crear', [\App\Http\Controllers\ActividadesController::class, 'create'])->name('actividades.create');
        Route::post('/{nota}/actividades', [\App\Http\Controllers\ActividadesController::class, 'store'])->name('actividades.store');
        Route::delete('/{nota}/actividades/{actividad}', [\App\Http\Controllers\ActividadesController::class, 'destroy'])->name('actividades.destroy');
    });
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
            'name' => ['nullable', 'string', 'max:255'],
            'first_name' => ['required_without:name', 'string', 'max:255'],
            'second_name' => ['nullable', 'string', 'max:255'],
            'first_last' => ['required_without:name', 'string', 'max:255'],
            'second_last' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'document_type' => ['nullable','regex:/^(R\\.?C|C\\.?C|T\\.?I)$/i'],
            'document_number' => ['nullable','string','max:50'],
            'celular' => ['nullable','string','max:30'],
        ]);

        // Obtener id del rol Estudiante
        $rolEstudiante = RolesModel::where('nombre', 'Estudiante')->first();
        $rolId = $rolEstudiante ? $rolEstudiante->id : null;

        // construir nombre legacy si no se envía name
        $fullName = $validated['name'] ?? null;
        if (empty($fullName)) {
            $parts = [];
            if (!empty($validated['first_name'])) $parts[] = $validated['first_name'];
            if (!empty($validated['second_name'])) $parts[] = $validated['second_name'];
            if (!empty($validated['first_last'])) $parts[] = $validated['first_last'];
            if (!empty($validated['second_last'])) $parts[] = $validated['second_last'];
            $fullName = implode(' ', $parts);
        }

        $newUser = User::create([
            'name' => $fullName,
            'first_name' => $validated['first_name'] ?? null,
            'second_name' => $validated['second_name'] ?? null,
            'first_last' => $validated['first_last'] ?? null,
            'second_last' => $validated['second_last'] ?? null,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'roles_id' => $rolId,
            'document_type' => $validated['document_type'] ?? null,
            'document_number' => $validated['document_number'] ?? null,
            'celular' => $validated['celular'] ?? null,
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
    // Endpoint para búsqueda rápida de usuarios por nombre o documento
    Route::get('usuarios/search', [UserController::class, 'search'])->name('usuarios.search');
    // Endpoint para obtener usuarios por rol/grupo (usado por AJAX en comunicación)
    Route::get('comunicacion/usuarios-por-grupo/{rolId}', [UserController::class, 'byRole'])->name('usuarios.byRole');
    // Gestión académica (páginas básicas)
    Route::get('gestion-academica', [GestionAcademicaController::class, 'index'])->name('gestion.index');
    Route::get('gestion-academica/crear-curso', [GestionAcademicaController::class, 'crearCurso'])->name('gestion.crearCurso');
    Route::get('gestion-academica/editar-curso', [GestionAcademicaController::class, 'editarCurso'])->name('gestion.editarCurso');
    Route::get('gestion-academica/horarios', [GestionAcademicaController::class, 'horarios'])->name('gestion.horarios');
    Route::post('gestion-academica/horarios', [GestionAcademicaController::class, 'guardarHorario'])->name('horarios.guardar');

    // RUTAS PARA GESTIÓN DISCIPLINARIA
    Route::prefix('gestion-disciplinaria')->name('gestion-disciplinaria.')->group(function () {
        Route::get('/', [\App\Http\Controllers\GestionDisciplinariaController::class, 'index'])->name('index');
        Route::get('/registrar', [\App\Http\Controllers\GestionDisciplinariaController::class, 'mostrarFormularioSancion'])->name('registrar');
        Route::post('/', [\App\Http\Controllers\GestionDisciplinariaController::class, 'registrarSancion'])->name('store');
        Route::get('/buscar', [\App\Http\Controllers\GestionDisciplinariaController::class, 'buscarPorDocumento'])->name('buscar');
        Route::get('/reporte', [\App\Http\Controllers\GestionDisciplinariaController::class, 'generarReporte'])->name('reporte');
        // CRUD para tipos de sanción
        Route::resource('tipos', \App\Http\Controllers\SancionTipoController::class)->names('tipos');
    });

    
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
    // Ruta para quitar todas las asignaciones de cursos a un docente
    Route::post('gestion-academica/docentes/{id}/quitar-todos', [DocenteCursoController::class, 'removeAll'])->name('docentes.quitarTodos');


    // Vista para gestionar la institución
    Route::view('/institucion/gestion', 'institucion')->name('institucion.gestion');

    // Rutas para administrar matrículas
    Route::resource('matriculas', MatriculaController::class);
    // Endpoint JSON para obtener cursos por nombre base (usado por formulario de matrículas)
    Route::get('matriculas/json/cursos-por-base/{base}', [MatriculaController::class, 'cursosPorBase'])->name('matriculas.json.cursos_por_base');
    // Servir archivos de matrículas (visualización/descarga) desde el disco configurado
    Route::get('matriculas/{matricula}/archivo/{campo}', [MatriculaController::class, 'archivo'])
        ->name('matriculas.archivo');
    // Servir comprobantes específicos por nombre (permite a tesorero ver históricos)
    Route::get('matriculas/{matricula}/comprobante/{filename}', [MatriculaController::class, 'comprobanteFile'])
        ->name('matriculas.comprobanteFile');
    // Validación de pago (solo tesorero)
    Route::post('matriculas/{matricula}/validar-pago', [MatriculaController::class, 'validarPago'])
        ->name('matriculas.validarPago');

    // Rutas de Gestión Financiera
    Route::get('gestion-financiera', [GestionFinancieraController::class, 'index'])->name('financiera.index');
    Route::get('gestion-financiera/registrar-pago', [GestionFinancieraController::class, 'mostrarFormularioPago'])->name('financiera.formularioPago');
    Route::post('gestion-financiera/registrar-pago', [GestionFinancieraController::class, 'registrarPago'])->name('financiera.registrarPago');
    // Actualizar valor de matrícula (solo tesorero/administrador)
    Route::post('gestion-financiera/valor-matricula', [GestionFinancieraController::class, 'actualizarValorMatricula'])->name('financiera.valorMatricula');
    // Buscar estado de cuenta por documento (form) o ver por id
    Route::get('gestion-financiera/estado-cuenta', [GestionFinancieraController::class, 'estadoCuentaSearch'])->name('financiera.estadoCuenta.search');
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
        Route::get('/json/ultima-matricula/{userId}', [\App\Http\Controllers\AsignacionesController::class, 'getLatestMatricula'])->name('json.ultima_matricula');
        Route::get('/json/curso/{cursoId}/horarios', [\App\Http\Controllers\AsignacionesController::class, 'getCourseSchedule'])->name('curso.horarios');
        Route::get('/json/curso/{cursoId}/estudiantes', [\App\Http\Controllers\AsignacionesController::class, 'getStudentsByCourse'])->name('curso.estudiantes');
        Route::get('/json/estudiantes', [\App\Http\Controllers\AsignacionesController::class, 'searchStudents'])->name('json.estudiantes');
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

// 🏥 RUTAS PARA SISTEMA DE GESTIÓN DE CITAS
Route::middleware('auth')->group(function () {
    Route::prefix('citas')->name('citas.')->group(function () {
        // Rutas básicas CRUD
        Route::get('/', [\App\Http\Controllers\CitasController::class, 'index'])->name('index');
        Route::get('/crear', [\App\Http\Controllers\CitasController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\CitasController::class, 'store'])->name('store');
        Route::get('/{cita}', [\App\Http\Controllers\CitasController::class, 'show'])->name('show');
        Route::get('/{cita}/editar', [\App\Http\Controllers\CitasController::class, 'edit'])->name('edit');
        Route::put('/{cita}', [\App\Http\Controllers\CitasController::class, 'update'])->name('update');
        Route::delete('/{cita}', [\App\Http\Controllers\CitasController::class, 'destroy'])->name('destroy');
        
        // Rutas especiales para gestión de citas
        Route::post('/{cita}/programar', [\App\Http\Controllers\CitasController::class, 'programar'])->name('programar');
        Route::post('/{cita}/confirmar', [\App\Http\Controllers\CitasController::class, 'confirmar'])->name('confirmar');
        Route::post('/{cita}/iniciar', [\App\Http\Controllers\CitasController::class, 'iniciar'])->name('iniciar');
        Route::post('/{cita}/completar', [\App\Http\Controllers\CitasController::class, 'completar'])->name('completar');
        Route::post('/{cita}/cancelar', [\App\Http\Controllers\CitasController::class, 'cancelar'])->name('cancelar');
        Route::post('/{cita}/reprogramar', [\App\Http\Controllers\CitasController::class, 'reprogramar'])->name('reprogramar');
        
        // Vista de calendario
        Route::get('/calendario/vista', [\App\Http\Controllers\CitasController::class, 'calendario'])->name('calendario');
        Route::get('/calendario/eventos', [\App\Http\Controllers\CitasController::class, 'citasCalendario'])->name('calendario.eventos');
    });
});

// 📊 RUTAS PARA SISTEMA DE SEGUIMIENTO DE ESTUDIANTES
Route::middleware('auth')->group(function () {
    Route::prefix('seguimientos')->name('seguimientos.')->group(function () {
        // Dashboard y estadísticas
        Route::get('/dashboard', [\App\Http\Controllers\SeguimientosController::class, 'dashboard'])->name('dashboard');
        
        // Rutas básicas CRUD
        Route::get('/', [\App\Http\Controllers\SeguimientosController::class, 'index'])->name('index');
        Route::get('/crear', [\App\Http\Controllers\SeguimientosController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\SeguimientosController::class, 'store'])->name('store');
        Route::get('/{seguimiento}', [\App\Http\Controllers\SeguimientosController::class, 'show'])->name('show');
        Route::get('/{seguimiento}/editar', [\App\Http\Controllers\SeguimientosController::class, 'edit'])->name('edit');
        Route::put('/{seguimiento}', [\App\Http\Controllers\SeguimientosController::class, 'update'])->name('update');
        Route::delete('/{seguimiento}', [\App\Http\Controllers\SeguimientosController::class, 'destroy'])->name('destroy');
        
        // Acciones específicas de seguimiento
        Route::post('/{seguimiento}/sesion', [\App\Http\Controllers\SeguimientosController::class, 'registrarSesion'])->name('registrar-sesion');
        Route::post('/{seguimiento}/estado', [\App\Http\Controllers\SeguimientosController::class, 'cambiarEstado'])->name('cambiar-estado');
        Route::post('/{seguimiento}/padres', [\App\Http\Controllers\SeguimientosController::class, 'informarPadres'])->name('informar-padres');
        
        // Reportes
        Route::get('/estudiante/{estudiante}/reporte', [\App\Http\Controllers\SeguimientosController::class, 'reporteEstudiante'])->name('reporte-estudiante');
        
        // API endpoints
        Route::get('/api/seguimientos', [\App\Http\Controllers\SeguimientosController::class, 'apiSeguimientos'])->name('api');
    });

    // Gestión de Pensiones
    Route::prefix('pensiones')->name('pensiones.')->group(function () {
        Route::get('/', [\App\Http\Controllers\PensionesController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\PensionesController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\PensionesController::class, 'store'])->name('store');
        Route::get('/{pension}', [\App\Http\Controllers\PensionesController::class, 'show'])->name('show');
        Route::get('/{pension}/edit', [\App\Http\Controllers\PensionesController::class, 'edit'])->name('edit');
        Route::put('/{pension}', [\App\Http\Controllers\PensionesController::class, 'update'])->name('update');
        
        // Procesar pago
        Route::post('/{pension}/pago', [\App\Http\Controllers\PensionesController::class, 'procesarPago'])->name('procesar-pago');
        
        // Anular pensión
        Route::post('/{pension}/anular', [\App\Http\Controllers\PensionesController::class, 'anular'])->name('anular');
        
        // Generar pensiones masivas
        Route::post('/generar-masivas', [\App\Http\Controllers\PensionesController::class, 'generarMasivas'])->name('generar-masivas');
        
        // Reportes
        Route::get('/reportes/general', [\App\Http\Controllers\PensionesController::class, 'reporte'])->name('reporte');
        
        // API endpoints
        Route::post('/actualizar-vencidas', [\App\Http\Controllers\PensionesController::class, 'actualizarVencidas'])->name('actualizar-vencidas');
    });
    // 📢 RUTAS DE COMUNICACIÓN
    Route::prefix('comunicacion')->name('comunicacion.')->group(function () {
        Route::get('/', [\App\Http\Controllers\ComunicacionController::class, 'index'])->name('index');

    // Mensajes
        Route::get('/mensajes', [\App\Http\Controllers\ComunicacionController::class, 'listarMensajes'])->name('mensajes');
        Route::post('/mensajes', [\App\Http\Controllers\ComunicacionController::class, 'guardarMensaje'])->name('mensajes.store');
        // Ver mensajes enviados por el remitente
        Route::get('/mensajes/enviados', [\App\Http\Controllers\ComunicacionController::class, 'listarMensajesEnviados'])->name('mensajes.enviados');
        // Ver detalle de un mensaje (remitente o destinatario)
        Route::get('/mensajes/{id}', [\App\Http\Controllers\ComunicacionController::class, 'mostrarMensaje'])->name('mensajes.show');
        // Eliminar un mensaje (solo remitente puede eliminar)
        Route::delete('/mensajes/{id}', [\App\Http\Controllers\ComunicacionController::class, 'eliminarMensaje'])->name('mensajes.destroy');
        // Marcar como no leído (solo destinatario puede hacerlo)
        Route::post('/mensajes/{id}/no-leer', [\App\Http\Controllers\ComunicacionController::class, 'marcarNoLeido'])->name('mensajes.no_leer');
        // Responder a un mensaje (form + envío)
        Route::get('/mensajes/{id}/responder', [\App\Http\Controllers\ComunicacionController::class, 'formResponder'])->name('mensajes.responder.form');
        Route::post('/mensajes/{id}/responder', [\App\Http\Controllers\ComunicacionController::class, 'enviarRespuesta'])->name('mensajes.responder.enviar');

    // Notificaciones
        Route::get('/notificaciones', [\App\Http\Controllers\ComunicacionController::class, 'listarNotificaciones'])->name('notificaciones');
        Route::post('/notificaciones', [\App\Http\Controllers\ComunicacionController::class, 'guardarNotificacion'])->name('notificaciones.store');
        Route::post('/notificaciones/{id}/leer', [\App\Http\Controllers\ComunicacionController::class, 'marcarNotificacionLeida'])->name('notificaciones.leer');

    // Circulares
        Route::get('/circulares', [\App\Http\Controllers\ComunicacionController::class, 'listarCirculares'])->name('circulares');
        Route::get('/circulares/crear', [\App\Http\Controllers\ComunicacionController::class, 'crearCircular'])->name('circulares.create');
        Route::post('/circulares', [\App\Http\Controllers\ComunicacionController::class, 'guardarCircular'])->name('circulares.store');
});

});
