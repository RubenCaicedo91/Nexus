@extends('layouts.app')

@section('content')
<div class="container">

    <!-- Banner superior -->
    <div class="p-4 mb-4 text-center text-white rounded" 
         style="background: linear-gradient(90deg, #093666bf, #3771c7ff);">
        <h1 class="fw-bold">📢 <strong>Módulo de Comunicación</strong> 📨</h1>
        <p class="mb-0">Conectamos a la comunidad educativa con mensajes, notificaciones y circulares</p>
    </div>

    <div class="row g-3">

        <!-- Mensajes (Azul oscuro) -->
        <div class="col-md-4">
            <div class="card border-primary h-100 shadow-lg">
                <div class="card-header text-white text-center" style="background-color: #00264d;">
                    <i class="fa-solid fa-paper-plane me-2"></i> Mensajes
                </div>
                <div class="card-body">
                    <p class="mb-3">📌 Gestión de Mensajes - Envío - Bandeja de Entrada.</p>
                    <a href="{{ route('comunicacion.mensajes') }}" 
                       class="btn text-center w-100 text-white" 
                       style="background-color: #00264d;">
                        📧 Mensajes
                    </a>
                </div>
            </div>
        </div>

        <!-- Notificaciones (Amarillo dorado) -->
        <div class="col-md-4">
            <div class="card border-warning h-100 shadow-lg">
                <div class="card-header text-dark text-center" style="background-color: #ffcc00;">
                    <i class="fa-solid fa-bell me-2"></i> Notificaciones
                </div>
                <div class="card-body">
                    <p class="mb-3">🔔 Envío y gestión de notificaciones para toda la comunidad educativa.</p>
                    <a href="{{ route('comunicacion.notificaciones') }}" 
                       class="btn text-center w-100 text-dark fw-bold" 
                       style="background-color: #ffcc00;">
                        📢 Notificaciones
                    </a>
                </div>
            </div>
        </div>

        <!-- Circulares (Verde oscuro) -->
        <div class="col-md-4">
            <div class="card border-success h-100 shadow-lg">
                <div class="card-header text-white text-center" style="background-color: #0b6623;">
                    <i class="fa-solid fa-file-lines me-2"></i> Circulares
                </div>
                <div class="card-body">
                    <p class="mb-3">📄 Administración y publicación de circulares institucionales.</p>
                    <a href="{{ route('comunicacion.circulares') }}" 
                       class="btn text-center w-100 text-white" 
                       style="background-color: #0b6623;">
                        📨 Circulares
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection