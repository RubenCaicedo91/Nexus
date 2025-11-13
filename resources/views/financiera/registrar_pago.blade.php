@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm rounded overflow-hidden">

        <!-- Encabezado oscuro -->
        <div class="bg-dark text-white p-3">
            <h2 class="fw-semibold mb-0">
                <i class="bi bi-cash-coin me-2 text-warning"></i> Registro de Pago Escolar
            </h2>
            <p class="small mb-0 text-light">Completa los datos para registrar un nuevo pago en el sistema.</p>
        </div>

        <!-- Separador -->
        <hr class="m-0">

        <!-- Contenido del formulario -->
        <div class="p-4 bg-light">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            @endif

            <form method="POST" action="{{ route('financiera.registrarPago') }}">
                @csrf

                <div class="mb-4">
                    <span class="text-uppercase text-secondary small">Estudiante</span>
                    <input type="text" name="estudiante_id" id="estudiante_id" class="form-control mt-1" placeholder="Ej. 1023" required>
                </div>

                <div class="mb-4">
                    <span class="text-uppercase text-secondary small">Concepto</span>
                    <select name="concepto" id="concepto" class="form-select mt-1">
                        <option value="matricula">Matrícula</option>
                        <option value="pension">Pensión</option>
                    </select>
                </div>

                <div class="mb-4">
                    <span class="text-uppercase text-secondary small">Monto</span>
                    <input type="number" name="monto" id="monto" class="form-control mt-1" placeholder="$" required>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i> Registrar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
