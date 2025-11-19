@extends('layouts.app')

@section('content')
<div class="container py-5">

    <!-- Encabezado tipo label -->
    <div class="p-4 rounded-3 mb-5 text-center shadow-sm"
        style="background: linear-gradient(90deg, #003366, #0073e6); color: white;">
        <h1 class="fw-bold fs-1">
            🏫 ¡Bienvenido, Administrador del Sistema!
        </h1>
        <p class="fs-5 mt-2">
            Programa para administrar un colegio de forma integral y eficiente.
        </p>
    </div>

    <!-- Tarjetas de módulos -->
    <div class="row g-4 justify-content-center">

        <!-- Gestión Institucional -->
        <div class="col-md-3">
            <div class="card text-center rounded-3 shadow-lg border-2 module-card">
                <div class="card-body">
                    <div class="text-primary mb-2"><i class="fas fa-school fa-2x"></i></div>
                    <h5 class="fw-bold">Gestión Institucional</h5>
                    <p class="text-muted">Administra la información del colegio, áreas y personal administrativo.</p>
                </div>
            </div>
        </div>

        <!-- Gestión Académica -->
        <div class="col-md-3">
            <div class="card text-center rounded-3 shadow-lg border-2 module-card">
                <div class="card-body">
                    <div class="text-success mb-2"><i class="fas fa-chalkboard-teacher fa-2x"></i></div>
                    <h5 class="fw-bold">Gestión Académica</h5>
                    <p class="text-muted">Controla los horarios, matrículas y asignaciones académicas.</p>
                </div>
            </div>
        </div>

        <!-- Gestión de Notas -->
        <div class="col-md-3">
            <div class="card text-center rounded-3 shadow-lg border-2 module-card">
                <div class="card-body">
                    <div class="text-warning mb-2"><i class="fas fa-clipboard-list fa-2x"></i></div>
                    <h5 class="fw-bold">Gestión de Notas</h5>
                    <p class="text-muted">Registra y consulta las calificaciones de los estudiantes.</p>
                </div>
            </div>
        </div>

        <!-- Gestión Disciplinaria -->
        <div class="col-md-3">
            <div class="card text-center rounded-3 shadow-lg border-2 module-card">
                <div class="card-body">
                    <div class="text-danger mb-2"><i class="fas fa-user-graduate fa-2x"></i></div>
                    <h5 class="fw-bold">Gestión Disciplinaria</h5>
                    <p class="text-muted">Lleva el control de sanciones y reconocimientos estudiantiles.</p>
                    @php
                        $isEstudiante = auth()->check() && optional(auth()->user()->role)->nombre && stripos(optional(auth()->user()->role)->nombre, 'estudiante') !== false;
                    @endphp
                    @if(isset($isEstudiante) && $isEstudiante)
                        <a href="{{ route('historial.sanciones', auth()->id()) }}" class="btn btn-outline-light mt-2">Ver mi historial</a>
                    @else
                        <a href="{{ route('gestion-disciplinaria.index') }}" class="btn btn-outline-light mt-2">Abrir módulo</a>
                    @endif
                </div>
            </div>
        </div>

        <!-- Gestión Financiera -->
        <div class="col-md-3">
            <div class="card text-center rounded-3 shadow-lg border-2 module-card">
                <div class="card-body">
                    <div class="text-info mb-2"><i class="fas fa-chart-line fa-2x"></i></div>
                    <h5 class="fw-bold">Gestión Financiera</h5>
                    <p class="text-muted">Administra pagos, pensiones y reportes financieros.</p>
                    @php
                        $isEstudiante = auth()->check() && optional(auth()->user()->role)->nombre && stripos(optional(auth()->user()->role)->nombre, 'estudiante') !== false;
                    @endphp
                    @if(isset($isEstudiante) && $isEstudiante)
                        <a href="{{ route('financiera.index') }}" class="btn btn-outline-light mt-2">Consultar mi cuenta</a>
                    @else
                        <a href="{{ route('financiera.index') }}" class="btn btn-outline-light mt-2">Abrir módulo</a>
                    @endif
                </div>
            </div>
        </div>

        <!-- Comunicaciones -->
        <div class="col-md-3">
            <div class="card text-center rounded-3 shadow-lg border-2 module-card">
                <div class="card-body">
                    <div class="text-primary mb-2"><i class="fas fa-bullhorn fa-2x"></i></div>
                    <h5 class="fw-bold">Comunicaciones</h5>
                    <p class="text-muted">Envía avisos e información a toda la comunidad educativa.</p>
                </div>
            </div>
        </div>

        <!-- Orientación -->
        <div class="col-md-3">
            <div class="card text-center rounded-3 shadow-lg border-2 module-card">
                <div class="card-body">
                    <div class="text-success mb-2"><i class="fas fa-comments fa-2x"></i></div>
                    <h5 class="fw-bold">Orientación</h5>
                    <p class="text-muted">Acompaña a los estudiantes con apoyo académico y emocional.</p>
                </div>
            </div>
        </div>

        <!-- Configuración -->
        <div class="col-md-3">
            <div class="card text-center rounded-3 shadow-lg border-2 module-card">
                <div class="card-body">
                    <div class="text-secondary mb-2"><i class="fas fa-cogs fa-2x"></i></div>
                    <h5 class="fw-bold">Configuración</h5>
                    <p class="text-muted">Gestiona usuarios, roles, permisos y ajustes del sistema.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Frase indicativa -->
    <div class="card mt-5 border-0 shadow-sm" style="background: linear-gradient(90deg, #007bff, #0056b3); color: white;">
    <div class="card-body text-center">
        <h4 class="fw-bold mb-2">
            💡 ¡Recuerda!
        </h4>
        <p class="fs-5 mb-0">
            Para ingresar al módulo de tu interés, utiliza el <strong>menú lateral izquierdo</strong>.
            <i class="fas fa-hand-point-left ms-2"></i>
        </p>
    </div>
</div>

</div>

<!-- Estilos personalizados -->
<style>
    .module-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .module-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }
</style>
@endsection
