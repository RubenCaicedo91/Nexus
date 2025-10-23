@extends('layouts.app')

@section('title', 'Dashboard - Colegio')

@section('content')
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand text-white" href="{{ route('dashboard') }}">
            <i class="fas fa-school me-2"></i> Nexus Team Education
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-1"></i>{{ Auth::user()->name }}
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="#">
                                <i class="fas fa-user-cog me-1"></i>Perfil
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#">
                                <i class="fas fa-cog me-1"></i>Configuración
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="{{ route('logout') }}" 
                               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="fas fa-sign-out-alt me-1"></i>Cerrar Sesión
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 p-0">
            <div class="sidebar">
                <div class="p-3">
                    <h6 class="text-white-50 text-uppercase">Menú Principal</h6>
                </div>
                <nav class="nav flex-column px-3">
                    <a class="nav-link active" href="{{ route('dashboard') }}">
                        <i class="fas fa-book me-2"></i>Gestion Institucional
                    </a>
                    <a class="nav-link" href="#">
                        <i class="fas fa-chalkboard-teacher me-2"></i>Gestion Academica
                    </a>
                    <a class="nav-link" href="#">
                        <i class="fas fa-user-graduate me-2"></i>Gestion Disciplinaria
                    </a>
                    <a class="nav-link" href="#">
                        <i class="fas fa-chart-line me-2"></i>Gestion Financiera
                    </a>
                    <a class="nav-link" href="#">
                        <i class="fas fa-comment-dots me-2"></i>Orientacion
                    </a>
                    <a class="nav-link" href="#">
                        <i class="fas fa-comments me-2"></i>Modulo de Comunicación
                    </a>
                    <a class="nav-link" href="#">
                        <i class="fas fa-cog me-2"></i>Configuración
                    </a>

                    <!-- Acceso a Roles -->
                    <a class="nav-link" href="{{ route('roles.index') }}">
                        <i class="fas fa-users-cog me-2"></i>Roles
                    </a>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10">
            <div class="main-content p-4">
                <!-- Welcome Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h1 class="h3 text-dark">¡Bienvenido, {{ Auth::user()->name }}!</h1>
                        <p class="text-muted">Gestiona tus cuentas de cobro de manera eficiente</p>
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
                                <h5 class="card-title">Total Cuentas</h5>
                                <h3 class="text-primary">0</h3>
                                <small class="text-muted">Cuentas registradas</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body text-center">
                                <div class="text-success mb-2">
                                    <i class="fas fa-check-circle fa-2x"></i>
                                </div>
                                <h5 class="card-title">Pagadas</h5>
                                <h3 class="text-success">0</h3>
                                <small class="text-muted">Cuentas pagadas</small>
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
                                <h3 class="text-warning">0</h3>
                                <small class="text-muted">Por cobrar</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body text-center">
                                <div class="text-info mb-2">
                                    <i class="fas fa-dollar-sign fa-2x"></i>
                                </div>
                                <h5 class="card-title">Total Facturado</h5>
                                <h3 class="text-info">$0</h3>
                                <small class="text-muted">Este mes</small>
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
                                            Nueva Cuenta de Cobro
                                        </a>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <a href="#" class="btn btn-outline-primary btn-lg w-100">
                                            <i class="fas fa-user-plus me-2"></i>
                                            Agregar Cliente
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
            </div>
        </div>
    </div>
</div>

<!-- Logout Form -->
<form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
    @csrf
</form>
@endsection
