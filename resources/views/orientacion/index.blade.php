@extends('layouts.app')

@section('content')
<div class="container">

    <!-- Banner superior -->
    <div class="p-4 mb-4 text-center text-white rounded" style="background: linear-gradient(90deg, #007bff, #3783f5ff);">
        <h1 class="fw-bold">🌟 Gestión de Orientación 🌟</h1>
        <p class="mb-0">Acompañando el bienestar estudiantil con citas, informes y seguimientos 🤝✨</p>
    </div>

    <!-- Tarjetas del módulo -->
    <div class="row g-3">
        <!-- Citas -->
        <div class="col-md-4">
            <div class="card border-primary h-100 shadow-lg">
                <div class="card-header bg-primary text-white text-center">
                    <i class="fa-solid fa-paper-plane me-2"></i> Citas
                    </div>
                <div class="card-body">
                    <p class="mb-3">📌 Solicitar y gestionar citas de orientación de manera sencilla.</p>
                    <a href="{{ route('orientacion.citas') }}" class="btn btn-primary w-100">Ir a Citas ➡️</a>
                </div>
            </div>
        </div>

        <!-- Informes -->
        <div class="col-md-4">
            <div class="card border-success h-100 shadow-sm">
                <div class="card-header bg-success text-white">📝 Informes</div>
                <div class="card-body">
                    <p class="mb-3">📖 Generar informes psicosociales vinculados a citas atendidas.</p>
                    <a href="{{ route('orientacion.informes') }}" class="btn btn-success w-100">Ir a Informes ➡️</a>
                </div>
            </div>
        </div>

        <!-- Seguimientos -->
        <div class="col-md-4">
            <div class="card border-info h-100 shadow-sm">
                <div class="card-header bg-info text-white">📊 Seguimientos</div>
                <div class="card-body">
                    <p class="mb-3">🔍 Registrar y consultar seguimientos para dar continuidad al proceso.</p>
                    <a href="{{ route('orientacion.seguimientos') }}" class="btn btn-info w-100">Ir a Seguimientos ➡️</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
