@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm rounded overflow-hidden">

        <!-- Encabezado oscuro -->
        <div class="bg-dark text-white p-3">
            <h2 class="fw-semibold mb-0">
                <i class="bi bi-bar-chart-line-fill me-2 text-warning"></i> Reporte Financiero General
            </h2>
            <p class="small mb-0 text-light">Visualiza el resumen de pagos registrados en el sistema.</p>
        </div>

        <!-- Separador -->
        <hr class="m-0">

        <!-- Contenido -->
        <div class="p-4 bg-light">
            @if($reporte->isEmpty())
                <div class="alert alert-info">
                    No hay pagos registrados en el sistema.
                </div>
            @else
                <table class="table table-hover table-striped align-middle">
                    <thead class="table-secondary">
                        <tr>
                            <th>ID Estudiante</th>
                            <th>Concepto</th>
                            <th>Monto</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reporte as $pago)
                            <tr>
                                <td>{{ $pago->estudiante_id }}</td>
                                <td>{{ ucfirst($pago->concepto) }}</td>
                                <td>${{ number_format($pago->monto, 0, ',', '.') }}</td>
                                <td>{{ $pago->created_at->format('d/m/Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="mt-3">
                    <strong>Total General:</strong>
                    ${{ number_format($reporte->sum('monto'), 0, ',', '.') }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection