<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CrearUsuario;
use App\Http\Controllers\RolController;
use App\Http\Controllers\GestionAcademicaController;
use App\Http\Controllers\InstitucionController; // 👈 Importa el nuevo controlador

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

    // Rutas para administrar roles
    Route::resource('roles', RolController::class);
    Route::get('roles-permisos', [RolController::class, 'permisosDisponibles'])->name('roles.permisos');
    // Gestión académica (páginas básicas)
    Route::get('gestion-academica', [GestionAcademicaController::class, 'index'])->name('gestion.index');
    Route::get('gestion-academica/crear-curso', [GestionAcademicaController::class, 'crearCurso'])->name('gestion.crearCurso');
    Route::get('gestion-academica/editar-curso', [GestionAcademicaController::class, 'editarCurso'])->name('gestion.editarCurso');
    Route::get('gestion-academica/horarios', [GestionAcademicaController::class, 'horarios'])->name('gestion.horarios');

    // 👇 NUEVAS RUTAS DE GESTIÓN INSTITUCIONAL
    Route::get('/institucion', [InstitucionController::class, 'index'])->name('institucion.index');
    Route::post('/institucion', [InstitucionController::class, 'store'])->name('institucion.store');
    Route::put('/institucion/{id}', [InstitucionController::class, 'update'])->name('institucion.update');

    // Vista para gestionar la institución
    Route::view('/institucion/gestion', 'institucion')->name('institucion.gestion');


});
