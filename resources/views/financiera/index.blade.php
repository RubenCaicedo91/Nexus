@extends('layouts.app')

@section('title', 'Gestión Financiera')

@section('content')
<div class="container py-4">
    <h2 class="mb-4">Gestión Financiera</h2>
    <p class="text-muted">Selecciona una opción para continuar:</p>

    <div class="row justify-content-center mt-4">
        <div class="col-md-6 d-grid gap-3">
            <a href="{{ route('financiera.formularioPago') }}" class="btn btn-outline-primary btn-lg">
                <i class="fas fa-money-check-alt me-2"></i> Registrar Pago
            </a>
            <a href="{{ route('financiera.estadoCuenta', ['id' => Auth::id()]) }}" class="btn btn-outline-secondary btn-lg">
                <i class="fas fa-file-invoice-dollar me-2"></i> Consultar Estado de Cuenta
            </a>
            <a href="{{ route('financiera.reporte') }}" class="btn btn-outline-success btn-lg">
                <i class="fas fa-chart-pie me-2"></i> Generar Reporte Financiero
            </a>
        </div>
    </div>
</div>
@endsection