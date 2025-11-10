@extends('layouts.app')

@section('title', 'Gestión Disciplinaria')

@section('content')
<div class="container">

    <!-- Banner superior -->
    <div class="p-4 mb-4 text-center text-white rounded" 
         style="background: linear-gradient(90deg, #5c0c0c, #a83232);">
        <h1 class="fw-bold">⚖️ <strong>Módulo de Gestión Disciplinaria</strong> 🧾</h1>
        <p class="mb-0">Administra sanciones, historiales y reportes disciplinarios</p>
    </div>

    <div class="row g-3">

        <!-- Registrar Sanción -->
        <div class="col-md-4">
            <div class="card border-danger h-100 shadow-lg">
                <div class="card-header text-white text-center" style="background-color: #7b1113;">
                    <i class="fas fa-gavel me-2"></i> Registrar Sanción
                </div>
                <div class="card-body">
                    <p class="mb-3">⚠️ Registra nuevas sanciones disciplinarias y su respectiva descripción.</p>
                    <a href="{{ route('gestion-disciplinaria.registrar') }}" 
                       class="btn text-center w-100 text-white" 
                       style="background-color: #7b1113;">
                        🧾 Registrar Sanción
                    </a>
                </div>
            </div>
        </div>

        <!-- Historial de Sanciones -->
        <div class="col-md-4">
            <div class="card border-warning h-100 shadow-lg">
                <div class="card-header text-dark text-center" style="background-color: #f6c344;">
                    <i class="fas fa-list-alt me-2"></i> Historial de Sanciones
                </div>
                <div class="card-body">
                    <p class="mb-3">📋 Consulta el historial de sanciones aplicadas a los estudiantes.</p>
                    <a href="{{ route('historial.sanciones', auth()->user()->id) }}" 
                       class="btn text-center w-100 text-dark fw-bold" 
                       style="background-color: #f6c344;">
                        📑 Ver Historial
                    </a>
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
                    <a href="{{ route('gestion-disciplinaria.reporte') }}" 
                       class="btn text-center w-100 text-white" 
                       style="background-color: #343a40;">
                        📈 Generar Reporte
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
