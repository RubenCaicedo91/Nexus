@extends('layouts.app')

@section('title', 'Gestión Disciplinaria')

@section('content')
<div class="container">

    @if(!empty($isCoordinator) && $isCoordinator)
        <div class="alert alert-danger mt-2"><strong>Acceso restringido:</strong> Tu perfil <strong>Coordinador Académico</strong> no tiene permisos en este módulo. No puedes registrar sanciones, ver historiales ni generar reportes desde aquí.</div>
        @if(session('error'))
            <div class="alert alert-danger mt-2">{{ session('error') }}</div>
        @endif
    @endif

    <!-- Banner superior -->
    <div class="p-4 mb-4 text-center text-white rounded" 
         style="background: linear-gradient(90deg, #171d56ff, #316c86ff);">
        <h1 class="fw-bold">⚖️ <strong>Módulo de Gestión Disciplinaria</strong> 🧾</h1>
        <p class="mb-0">Administra sanciones, historiales y reportes disciplinarios</p>
    </div>

    <div class="row g-3">

        <!-- Registrar Sanción -->
        <div class="col-md-4">
            <div class="card border-danger h-100 shadow-lg">
                <div class="card-header text-white text-center" style="background-color: #331267ff;">
                    <i class="fas fa-gavel me-2"></i> Registrar Sanción
                </div>
                <div class="card-body">
                    <p class="mb-3">⚠️ Registra nuevas sanciones disciplinarias y su respectiva descripción.</p>
                    @if(!empty($isCoordinator) && $isCoordinator)
                        <button class="btn text-center w-100 text-white" style="background-color: #6c757d;" disabled title="No tienes permiso para esta acción" aria-disabled="true">🧾 Registrar Sanción</button>
                    @else
                        <a href="{{ route('gestion-disciplinaria.registrar') }}" 
                           class="btn text-center w-100 text-white" 
                           style="background-color: #1c4e44ff;">
                            🧾 Registrar Sanción
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- Historial de Sanciones -->
        <div class="col-md-4">
            <div class="card border-warning h-100 shadow-lg">
                <div class="card-header text-dark text-center" style="background-color: #4b44aeff;">
                    <i class="fas fa-list-alt me-2"></i> Historial de Sanciones
                </div>
                <div class="card-body">
                    <p class="mb-3">📋 Consulta el historial de sanciones aplicadas a los estudiantes.</p>
                    @if(!empty($isCoordinator) && $isCoordinator)
                        <button class="btn text-center w-100 text-dark fw-bold" style="background-color: #6c757d;" disabled title="No tienes permiso para esta acción" aria-disabled="true">📑 Ver Historial</button>
                    @else
                        <a href="{{ route('historial.sanciones', auth()->user()->id) }}" 
                           class="btn text-center w-100 text-dark fw-bold" 
                           style="background-color: #4b44aeff;">
                            📑 Ver Historial
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- Reporte Disciplinario -->
        <div class="col-md-4">
            <div class="card border-secondary h-100 shadow-lg">
                <div class="card-header text-white text-center" style="background-color: #343a40;">
                    <i class="fas fa-file-alt me-2"></i> Reporte Disciplinario
                </div>
                <div class="card-body">
                    <p class="mb-3">🧾 Genera reportes consolidados de las medidas disciplinarias.</p>
                    @if(!empty($isCoordinator) && $isCoordinator)
                        <button class="btn text-center w-100 text-white" style="background-color: #6c757d;" disabled title="No tienes permiso para esta acción" aria-disabled="true">📈 Generar Reporte</button>
                    @else
                        <a href="{{ route('gestion-disciplinaria.reporte') }}" 
                           class="btn text-center w-100 text-white" 
                           style="background-color: #343a40;">
                            📈 Generar Reporte
                        </a>
                    @endif
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
