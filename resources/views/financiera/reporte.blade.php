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
            <form method="GET" class="row g-3 mb-4">
                <div class="col-md-4">
                    <label for="curso_id" class="form-label">Curso</label>
                    <select id="curso_id" name="curso_id" class="form-select">
                        <option value="">Todos</option>
                        @if(isset($cursos))
                            @foreach($cursos as $curso)
                                <option value="{{ $curso->id }}" {{ request()->query('curso_id') == $curso->id ? 'selected' : '' }}>{{ $curso->nombre }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="estado" class="form-label">Estado matrícula</label>
                    <select id="estado" name="estado" class="form-select">
                        <option value="">Todos</option>
                        @if(isset($estados))
                            @foreach($estados as $st)
                                <option value="{{ $st }}" {{ request()->query('estado') == $st ? 'selected' : '' }}>{{ $st }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div>
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                        <a href="{{ route('financiera.reporte') }}" class="btn btn-secondary ms-2">Limpiar</a>
                        @php
                            $isEstudiante = auth()->check() && optional(auth()->user()->role)->nombre && stripos(optional(auth()->user()->role)->nombre, 'estudiante') !== false;
                        @endphp
                        @if(! $isEstudiante)
                            <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="btn btn-outline-danger ms-2">Exportar PDF</a>
                            <a href="{{ request()->fullUrlWithQuery(['export' => 'excel']) }}" class="btn btn-outline-primary ms-2">Exportar Excel</a>
                        @endif
                    </div>
                </div>
            </form>

            @if($reporte->isEmpty())
                <div class="alert alert-info">No hay pagos que coincidan con el filtro.</div>
            @else
                <table class="table table-hover table-striped align-middle">
                    <thead class="table-secondary">
                        <tr>
                            <th>Estudiante</th>
                            <th>Concepto</th>
                            <th>Monto</th>
                            <th>Tiene Matrícula</th>
                            <th>Curso</th>
                            <th>Estado Pago</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reporte as $pago)
                            @php
                                $mat = null;
                                if (optional($pago->estudiante)->matriculas) {
                                    $mat = $pago->estudiante->matriculas->sortByDesc('fecha_matricula')->first();
                                }
                                $cursoNombre = $mat && $mat->curso ? $mat->curso->nombre : '-';
                                $estadoMat = $mat ? ($mat->estado ?? '-') : '-';
                            @endphp
                            <tr>
                                <td>{{ optional($pago->estudiante)->name ?? $pago->estudiante_id }}</td>
                                <td>{{ ucfirst($pago->concepto) }}</td>
                                <td>${{ number_format($pago->monto, 0, ',', '.') }}</td>
                                <td>{{ $mat ? 'Sí' : 'No' }}</td>
                                <td>{{ $cursoNombre }}</td>
                                <td>{{ $mat ? ($estadoMat ?: '-') : 'Sin matrícula' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="d-flex justify-content-between align-items-center">
                    <p class="mb-0"><strong>Total General: </strong>${{ number_format($reporte->sum('monto'), 0, ',', '.') }}</p>
                    <div>
                        @if(method_exists($reporte, 'links'))
                            {{ $reporte->links() }}
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection