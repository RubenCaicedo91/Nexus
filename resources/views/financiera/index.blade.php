@extends('layouts.app')

@section('title', 'Gestión Financiera')

@section('content')
<div class="container">

    <!-- Banner superior -->
    <div class="p-4 mb-4 text-center text-white rounded" 
         style="background: linear-gradient(90deg, #0c3a5f, #1a73e8);">
        <h1 class="fw-bold">💰 <strong>Módulo de Gestión Financiera</strong> 📊</h1>
        <p class="mb-0">Administra los pagos, estados de cuenta y reportes financieros</p>
    </div>

    <div class="row g-3">

        <!-- Registrar Pago -->
        <div class="col-md-4">
            <div class="card border-primary h-100 shadow-lg">
                <div class="card-header text-white text-center" style="background-color: #00264d;">
                    <i class="fas fa-money-check-alt me-2"></i> Registrar Pago
                </div>
                <div class="card-body">
                    <p class="mb-3">💵 Permite registrar los pagos realizados por los estudiantes o usuarios.</p>
                    <a href="{{ route('financiera.formularioPago') }}" 
                       class="btn text-center w-100 text-white" 
                       style="background-color: #00264d;">
                        💰 Registrar Pago
                    </a>
                </div>
            </div>
        </div>

        <!-- Estado de Cuenta -->
        <div class="col-md-4">
            <div class="card border-info h-100 shadow-lg">
                <div class="card-header text-white text-center" style="background-color: #0077b6;">
                    <i class="fas fa-file-invoice-dollar me-2"></i> Estado de Cuenta
                </div>
                <div class="card-body">
                    <p class="mb-3">📋 Consulta el estado de cuenta actual de cada usuario o estudiante.</p>
                    <a href="{{ route('financiera.estadoCuenta.search') }}" 
                       class="btn text-center w-100 text-white" 
                       style="background-color: #0077b6;">
                        📄 Consultar Estado
                    </a>
                </div>
            </div>
        </div>

        <!-- Reporte Financiero -->
        <div class="col-md-4">
            <div class="card border-success h-100 shadow-lg">
                <div class="card-header text-white text-center" style="background-color: #0b6623;">
                    <i class="fas fa-chart-pie me-2"></i> Reporte Financiero
                </div>
                <div class="card-body">
                    <p class="mb-3">📊 Genera reportes financieros detallados para análisis institucional.</p>
                    <a href="{{ route('financiera.reporte') }}" 
                       class="btn text-center w-100 text-white" 
                       style="background-color: #0b6623;">
                        📈 Generar Reporte
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection