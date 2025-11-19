@extends('layouts.app')

@section('title', 'Reportes de Pensiones')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1">
                        <i class="fas fa-chart-bar text-success me-2"></i>
                        Reportes de Pensiones
                    </h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="{{ route('pensiones.index') }}">Pensiones</a></li>
                            <li class="breadcrumb-item active">Reportes</li>
                        </ol>
                    </nav>
                </div>
                <div class="btn-group">
                    <a href="{{ route('pensiones.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                    @php
                        $isEstudiante = auth()->check() && optional(auth()->user()->role)->nombre && stripos(optional(auth()->user()->role)->nombre, 'estudiante') !== false;
                    @endphp
                    @if(! $isEstudiante)
                        <button type="button" class="btn btn-success" onclick="exportarExcel()">
                            <i class="fas fa-file-excel"></i> Exportar Excel
                        </button>
                        <button type="button" class="btn btn-danger" onclick="exportarPDF()">
                            <i class="fas fa-file-pdf"></i> Exportar PDF
                        </button>
                    @endif
                </div>
            </div>

            <!-- Filtros de Reporte -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-filter me-2"></i>
                        Filtros de Reporte
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('pensiones.reporte') }}" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Estado</label>
                            <select name="estado" class="form-select">
                                <option value="">Todos los estados</option>
                                <option value="pendiente" {{ request('estado') == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                                <option value="pagada" {{ request('estado') == 'pagada' ? 'selected' : '' }}>Pagada</option>
                                <option value="vencida" {{ request('estado') == 'vencida' ? 'selected' : '' }}>Vencida</option>
                                <option value="anulada" {{ request('estado') == 'anulada' ? 'selected' : '' }}>Anulada</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Mes</label>
                            <select name="mes" class="form-select">
                                <option value="">Todos</option>
                                @for($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}" {{ request('mes') == $i ? 'selected' : '' }}>
                                        {{ DateTime::createFromFormat('!m', $i)->format('F') }}
                                    </option>
                                @endfor
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Año</label>
                            <select name="año" class="form-select">
                                @for($year = date('Y'); $year >= 2024; $year--)
                                    <option value="{{ $year }}" {{ (request('año', date('Y')) == $year) ? 'selected' : '' }}>
                                        {{ $year }}
                                    </option>
                                @endfor
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Grado</label>
                            <select name="grado" class="form-select">
                                <option value="">Todos los grados</option>
                                @foreach($pensiones->pluck('grado')->unique()->filter() as $grado)
                                    <option value="{{ $grado }}" {{ request('grado') == $grado ? 'selected' : '' }}>
                                        {{ $grado }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search"></i> Filtrar
                            </button>
                            <a href="{{ route('pensiones.reporte') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Estadísticas Generales -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-primary text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-1">Total Pensiones</h6>
                                    <h3 class="mb-0">{{ $estadisticas['total_pensiones'] }}</h3>
                                </div>
                                <i class="fas fa-list fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-warning text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-1">Pendientes</h6>
                                    <h3 class="mb-0">{{ $estadisticas['pendientes'] }}</h3>
                                    <small>{{ $estadisticas['total_pensiones'] > 0 ? round(($estadisticas['pendientes'] / $estadisticas['total_pensiones']) * 100, 1) : 0 }}%</small>
                                </div>
                                <i class="fas fa-clock fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-danger text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-1">Vencidas</h6>
                                    <h3 class="mb-0">{{ $estadisticas['vencidas'] }}</h3>
                                    <small>{{ $estadisticas['total_pensiones'] > 0 ? round(($estadisticas['vencidas'] / $estadisticas['total_pensiones']) * 100, 1) : 0 }}%</small>
                                </div>
                                <i class="fas fa-exclamation-triangle fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card bg-success text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-1">Pagadas</h6>
                                    <h3 class="mb-0">{{ $estadisticas['pagadas'] }}</h3>
                                    <small>{{ $estadisticas['total_pensiones'] > 0 ? round(($estadisticas['pagadas'] / $estadisticas['total_pensiones']) * 100, 1) : 0 }}%</small>
                                </div>
                                <i class="fas fa-check-circle fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Estadísticas Financieras -->
            <div class="row mb-4">
                <div class="col-lg-4 mb-3">
                    <div class="card h-100">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-calculator me-2"></i>
                                Valor Total
                            </h5>
                        </div>
                        <div class="card-body text-center">
                            <h2 class="text-info mb-0">${{ number_format($estadisticas['valor_total'], 0) }}</h2>
                            <small class="text-muted">Valor total de todas las pensiones</small>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 mb-3">
                    <div class="card h-100">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-money-bill-wave me-2"></i>
                                Recaudado
                            </h5>
                        </div>
                        <div class="card-body text-center">
                            <h2 class="text-success mb-0">${{ number_format($estadisticas['valor_recaudado'], 0) }}</h2>
                            <small class="text-muted">
                                {{ $estadisticas['valor_total'] > 0 ? round(($estadisticas['valor_recaudado'] / $estadisticas['valor_total']) * 100, 1) : 0 }}% del total
                            </small>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 mb-3">
                    <div class="card h-100">
                        <div class="card-header bg-warning text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-hourglass-half me-2"></i>
                                Pendiente
                            </h5>
                        </div>
                        <div class="card-body text-center">
                            <h2 class="text-warning mb-0">${{ number_format($estadisticas['valor_pendiente'], 0) }}</h2>
                            <small class="text-muted">
                                {{ $estadisticas['valor_total'] > 0 ? round(($estadisticas['valor_pendiente'] / $estadisticas['valor_total']) * 100, 1) : 0 }}% del total
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráficos -->
            <div class="row mb-4">
                <div class="col-lg-6 mb-3">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-pie me-2"></i>
                                Distribución por Estado
                            </h5>
                        </div>
                        <div class="card-body">
                            <canvas id="chartEstados" width="400" height="300"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 mb-3">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-bar me-2"></i>
                                Valores por Estado
                            </h5>
                        </div>
                        <div class="card-body">
                            <canvas id="chartValores" width="400" height="300"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Análisis por Grado -->
            @if($pensiones->groupBy('grado')->count() > 1)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-line me-2"></i>
                            Análisis por Grado
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Grado</th>
                                        <th>Total Pensiones</th>
                                        <th>Pagadas</th>
                                        <th>Pendientes</th>
                                        <th>Vencidas</th>
                                        <th>Valor Total</th>
                                        <th>Recaudado</th>
                                        <th>% Recaudo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pensiones->groupBy('grado') as $grado => $pensionesGrado)
                                        @php
                                            $totalGrado = $pensionesGrado->count();
                                            $pagadasGrado = $pensionesGrado->where('estado', 'pagada')->count();
                                            $pendientesGrado = $pensionesGrado->where('estado', 'pendiente')->count();
                                            $vencidasGrado = $pensionesGrado->where('estado', 'vencida')->count();
                                            $valorTotalGrado = $pensionesGrado->sum('valor_total');
                                            $recaudadoGrado = $pensionesGrado->where('estado', 'pagada')->sum('valor_total');
                                            $porcentajeRecaudo = $valorTotalGrado > 0 ? ($recaudadoGrado / $valorTotalGrado) * 100 : 0;
                                        @endphp
                                        <tr>
                                            <td><strong>{{ $grado ?: 'Sin Grado' }}</strong></td>
                                            <td>{{ $totalGrado }}</td>
                                            <td><span class="badge bg-success">{{ $pagadasGrado }}</span></td>
                                            <td><span class="badge bg-warning">{{ $pendientesGrado }}</span></td>
                                            <td><span class="badge bg-danger">{{ $vencidasGrado }}</span></td>
                                            <td>${{ number_format($valorTotalGrado, 0) }}</td>
                                            <td class="text-success">${{ number_format($recaudadoGrado, 0) }}</td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    @php $pct = number_format($porcentajeRecaudo, 1, '.', ''); @endphp
                                                    <div class="progress-bar bg-success" role="progressbar"
                                                         data-width="{{ $pct }}"
                                                         aria-valuenow="{{ $pct }}"
                                                         aria-valuemin="0" aria-valuemax="100">
                                                        {{ $pct }}%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Detalle de Pensiones -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>
                        Detalle de Pensiones
                        <small class="text-muted">({{ $pensiones->count() }} registros)</small>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="tablaPensiones">
                            <thead>
                                <tr>
                                    <th>Estudiante</th>
                                    <th>Grado</th>
                                    <th>Concepto</th>
                                    <th>Período</th>
                                    <th>Valor</th>
                                    <th>Estado</th>
                                    <th>Vencimiento</th>
                                    <th>Pago</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pensiones as $pension)
                                    <tr>
                                        <td>
                                            <div>
                                                <strong>{{ $pension->estudiante->name }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $pension->estudiante->email }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">{{ $pension->grado ?: 'N/A' }}</span>
                                        </td>
                                        <td>{{ $pension->concepto }}</td>
                                        <td>
                                            {{ DateTime::createFromFormat('!m', $pension->mes)->format('M') }} {{ $pension->año }}
                                        </td>
                                        <td>
                                            <strong>${{ number_format($pension->valor_total, 0) }}</strong>
                                            @if($pension->recargo_mora > 0)
                                                <br><small class="text-danger">+${{ number_format($pension->recargo_mora, 0) }} mora</small>
                                            @endif
                                        </td>
                                        <td>
                                            @switch($pension->estado)
                                                @case('pendiente')
                                                    <span class="badge bg-warning">Pendiente</span>
                                                    @break
                                                @case('pagada')
                                                    <span class="badge bg-success">Pagada</span>
                                                    @break
                                                @case('vencida')
                                                    <span class="badge bg-danger">Vencida</span>
                                                    @break
                                                @case('anulada')
                                                    <span class="badge bg-secondary">Anulada</span>
                                                    @break
                                            @endswitch
                                        </td>
                                        <td>
                                            {{ \Carbon\Carbon::parse($pension->fecha_vencimiento)->format('d/m/Y') }}
                                            @if($pension->isVencida() && !$pension->isPagada())
                                                <br><small class="text-danger">{{ $pension->diasVencida() }} días</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($pension->isPagada())
                                                {{ \Carbon\Carbon::parse($pension->fecha_pago)->format('d/m/Y') }}
                                                <br><small class="text-muted">{{ $pension->metodo_pago }}</small>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .progress {
        border-radius: 10px;
    }
    
    .progress-bar {
        border-radius: 10px;
        font-size: 12px;
        line-height: 20px;
    }
    
    .card-header {
        border-bottom: 2px solid rgba(0,0,0,0.1);
    }
    
    .opacity-75 {
        opacity: 0.75;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script type="application/json" id="estadisticas-data">{!! json_encode($estadisticas) !!}</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Datos para los gráficos (parseados desde un tag JSON seguro)
    const estadisticas = JSON.parse(document.getElementById('estadisticas-data').textContent || '{}');
    
    // Gráfico de distribución por estado
    const ctxEstados = document.getElementById('chartEstados').getContext('2d');
    new Chart(ctxEstados, {
        type: 'doughnut',
        data: {
            labels: ['Pendientes', 'Pagadas', 'Vencidas', 'Anuladas'],
            datasets: [{
                data: [
                    estadisticas.pendientes,
                    estadisticas.pagadas,
                    estadisticas.vencidas,
                    estadisticas.total_pensiones - estadisticas.pendientes - estadisticas.pagadas - estadisticas.vencidas
                ],
                backgroundColor: ['#ffc107', '#28a745', '#dc3545', '#6c757d'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Gráfico de valores por estado
    const ctxValores = document.getElementById('chartValores').getContext('2d');
    new Chart(ctxValores, {
        type: 'bar',
        data: {
            labels: ['Recaudado', 'Pendiente'],
            datasets: [{
                label: 'Valor ($)',
                data: [estadisticas.valor_recaudado, estadisticas.valor_pendiente],
                backgroundColor: ['#28a745', '#ffc107'],
                borderColor: ['#1e7e34', '#e0a800'],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });

        // Aplicar anchos a las barras de progreso (usa data-width para evitar Blade en estilos inline)
        document.querySelectorAll('.progress .progress-bar[data-width]').forEach(function(el){
            var w = el.getAttribute('data-width');
            if (w !== null && w !== undefined) {
                // asegurar valor numérico
                var n = parseFloat(w) || 0;
                el.style.width = n + '%';
                el.setAttribute('aria-valuenow', n);
            }
        });
});

// Funciones de exportación
function exportarExcel() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'excel');
    window.location.href = '{{ route("pensiones.reporte") }}?' + params.toString();
}

function exportarPDF() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'pdf');
    window.open('{{ route("pensiones.reporte") }}?' + params.toString(), '_blank');
}
</script>
@endpush
