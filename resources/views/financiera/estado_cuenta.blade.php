@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm rounded overflow-hidden">

        <!-- Encabezado oscuro -->
        <div class="bg-dark text-white p-3">
            <h2 class="fw-semibold mb-0">
                <i class="bi bi-journal-check me-2 text-warning"></i> Estado de Cuenta del Estudiante
            </h2>
            <p class="small mb-0 text-light">Consulta los pagos registrados y el total acumulado.</p>
        </div>

        <!-- Separador -->
        <hr class="m-0">

        <!-- Contenido -->
        <div class="p-4 bg-light">
            @if($pagos->isEmpty())
                <div class="alert alert-info">
                    No se han registrado pagos para este estudiante.
                </div>
            @else
                <table class="table table-hover table-striped align-middle">
                    <thead class="table-secondary">
                        <tr>
                            <th>Concepto</th>
                            <th>Monto</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pagos as $pago)
                            <tr>
                                <td>{{ ucfirst($pago->concepto) }}</td>
                                <td>${{ number_format($pago->monto, 0, ',', '.') }}</td>
                                <td>{{ $pago->created_at->format('d/m/Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="mt-3">
                    <strong>Total Pagado:</strong>
                    ${{ number_format($pagos->sum('monto'), 0, ',', '.') }}
                </div>
            @endif

            <div class="text-end mt-4">
                <a href="{{ route('financiera.formularioPago') }}" class="btn btn-secondary">
                    <i class="bi bi-plus-circle me-1"></i> Registrar otro pago
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
