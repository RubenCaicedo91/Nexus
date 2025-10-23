@extends('layouts.app')

@section('title', 'Dashboard - Colegio')

@section('content')
<!-- Welcome Section -->
<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3 text-dark">¡Bienvenido, {{ Auth::user()->name }}!</h1>
        <p class="text-muted">SuperUsuario</p>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="text-primary mb-2">
                    <i class="fas fa-file-invoice fa-2x"></i>
                </div>
                <h5 class="card-title">Asignar Coordinadores</h5>
                <small class="text-muted">Lista Coordinadores</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="text-success mb-2">
                    <i class="fas fa-check-circle fa-2x"></i>
                </div>
                <h5 class="card-title">Informe General</h5>
                <small class="text-muted">Detallado</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="text-warning mb-2">
                    <i class="fas fa-clock fa-2x"></i>
                </div>
                <h5 class="card-title">Pendientes</h5>
                <small class="text-muted">Por Aprobar</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="text-info mb-2">
                    <i class="fas fa-dollar-sign fa-2x"></i>
                </div>
                <h5 class="card-title">Informacion Institucional</h5>
                <small class="text-muted">Datos</small>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-rocket me-2"></i>Acciones Rápidas
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <a href="#" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-plus-circle me-2"></i>
                            Nueva Coordinador
                        </a>
                    </div>
                    <div class="col-md-4 mb-3">
                        <a href="#" class="btn btn-outline-primary btn-lg w-100">
                            <i class="fas fa-user-plus me-2"></i>
                            Agregar Estudiante
                        </a>
                    </div>
                    <!-- Botón rápido para Roles -->
                    <div class="col-md-4 mb-3">
                        <a href="{{ route('roles.index') }}" class="btn btn-outline-primary btn-lg w-100">
                            <i class="fas fa-users-cog me-2"></i>
                            Gestionar Roles
                        </a>
                    </div>
                    <!-- Botón rápido para Matriculas -->
                    <div class="col-md-4 mb-3">
                        <a href="{{ route('matriculas.index') }}" class="btn btn-outline-info btn-lg w-100">
                            <i class="fas fa-user-graduate me-2"></i>
                            Gestionar Matrículas
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-history me-2"></i>Actividad Reciente
                </h5>
            </div>
            <div class="card-body">
                <div class="text-center py-4">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No hay actividad reciente para mostrar.</p>
                    <p class="text-muted">¡Comienza creando tu primera cuenta de cobro!</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
