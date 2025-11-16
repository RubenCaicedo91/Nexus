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
            <form class="row g-2 mb-3" method="get" action="{{ route('financiera.estadoCuenta.search') }}">
                <div class="col-auto">
                    <input type="text" name="documento" class="form-control" placeholder="Número de documento" value="{{ old('documento', $documento ?? '') }}">
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-search me-1"></i> Buscar</button>
                </div>
            </form>

            {{-- Solo mostraremos la tarjeta del estudiante cuando se haya realizado una búsqueda (searched) --}}

            @if(empty($searched))
                {{-- Primera visita: solo se muestra el buscador --}}
            @else
                @if((!isset($estudiante) || !$estudiante) && $pagos->isEmpty())
                    <div class="alert alert-info">
                        No se han encontrado pagos o estudiante. Use el buscador para localizar por número de documento.
                    </div>
                @else
                    @if(isset($estudiante) && $estudiante)
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5 class="card-title">{{ $estudiante->name }}</h5>
                                        <p class="mb-1"><strong>Documento:</strong> {{ $estudiante->document_number }}</p>
                                        <p class="mb-0"><strong>Correo:</strong> {{ $estudiante->email }}</p>
                                    </div>
                                    <div class="col-md-6 text-md-end">
                                        <p class="mb-1"><strong>Curso:</strong> {{ ($matricula && $matricula->curso) ? $matricula->curso->nombre : '-' }}</p>
                                        <p class="mb-1"><strong>Lleva pagado:</strong> ${{ number_format($montoPagado ?? 0, 0, ',', '.') }}</p>
                                        <p class="mb-0"><strong>Faltante:</strong> ${{ number_format($faltante ?? max(0, floatval($valorMatricula ?? 0) - floatval($montoPagado ?? 0)), 0, ',', '.') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
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
                        ${{ number_format($montoPagado ?? $pagos->sum('monto'), 0, ',', '.') }}
                    </div>
                @endif
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
