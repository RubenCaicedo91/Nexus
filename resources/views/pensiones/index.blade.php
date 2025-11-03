@extends('layouts.app')

@section('title', 'Gestión de Pensiones')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-money-bill-wave me-2"></i>
                        Gestión de Pensiones
                    </h4>
                    <div class="btn-group">
                        @if(auth()->user()->roles_id != 4)
                            <a href="{{ route('pensiones.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Nueva Pensión
                            </a>
                            <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#generarMasivasModal">
                                <i class="fas fa-layer-group"></i> Generar Masivas
                            </button>
                        @endif
                        <a href="{{ route('pensiones.reporte') }}" class="btn btn-success">
                            <i class="fas fa-chart-bar"></i> Reportes
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Filtros -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <form method="GET" action="{{ route('pensiones.index') }}" class="row g-3">
                                <div class="col-md-2">
                                    <label class="form-label">Estado</label>
                                    <select name="estado" class="form-select">
                                        <option value="">Todos</option>
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

                                @if(auth()->user()->roles_id != 4)
                                    <div class="col-md-2">
                                        <label class="form-label">Estudiante</label>
                                        <select name="estudiante_id" class="form-select">
                                            <option value="">Todos</option>
                                            @foreach($estudiantes as $estudiante)
                                                <option value="{{ $estudiante->id }}" {{ request('estudiante_id') == $estudiante->id ? 'selected' : '' }}>
                                                    {{ $estudiante->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-2">
                                        <label class="form-label">Grado</label>
                                        <select name="grado" class="form-select">
                                            <option value="">Todos</option>
                                            @foreach($grados as $grado)
                                                <option value="{{ $grado }}" {{ request('grado') == $grado ? 'selected' : '' }}>
                                                    {{ $grado }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif

                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="fas fa-search"></i> Filtrar
                                    </button>
                                    <a href="{{ route('pensiones.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Limpiar
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Resumen estadístico -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6>Total Pensiones</h6>
                                            <h3>{{ $pensiones->total() }}</h3>
                                        </div>
                                        <i class="fas fa-list fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6>Pendientes</h6>
                                            <h3>{{ $pensiones->where('estado', 'pendiente')->count() }}</h3>
                                        </div>
                                        <i class="fas fa-clock fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6>Vencidas</h6>
                                            <h3>{{ $pensiones->where('estado', 'vencida')->count() }}</h3>
                                        </div>
                                        <i class="fas fa-exclamation-triangle fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6>Pagadas</h6>
                                            <h3>{{ $pensiones->where('estado', 'pagada')->count() }}</h3>
                                        </div>
                                        <i class="fas fa-check-circle fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de pensiones -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Estudiante</th>
                                    @if(auth()->user()->roles_id != 4)
                                        <th>Acudiente</th>
                                    @endif
                                    <th>Concepto</th>
                                    <th>Mes/Año</th>
                                    <th>Grado</th>
                                    <th>Valor</th>
                                    <th>Vencimiento</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pensiones as $pension)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar bg-secondary rounded-circle me-2" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                                    <i class="fas fa-user text-white" style="font-size: 14px;"></i>
                                                </div>
                                                <div>
                                                    <strong>{{ $pension->estudiante->name }}</strong>
                                                    <br>
                                                    <small class="text-muted">{{ $pension->estudiante->email }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        @if(auth()->user()->roles_id != 4)
                                            <td>
                                                {{ $pension->acudiente->name ?? 'N/A' }}
                                                @if($pension->acudiente)
                                                    <br><small class="text-muted">{{ $pension->acudiente->email }}</small>
                                                @endif
                                            </td>
                                        @endif
                                        <td>{{ $pension->concepto }}</td>
                                        <td>
                                            {{ DateTime::createFromFormat('!m', $pension->mes)->format('M') }} {{ $pension->año }}
                                        </td>
                                        <td>
                                            <span class="badge bg-info">{{ $pension->grado ?: 'N/A' }}</span>
                                        </td>
                                        <td>
                                            <strong>${{ number_format($pension->valor_total, 0) }}</strong>
                                            @if($pension->descuentos > 0)
                                                <br><small class="text-success">Desc: -${{ number_format($pension->descuentos, 0) }}</small>
                                            @endif
                                            @if($pension->recargo_mora > 0)
                                                <br><small class="text-danger">Mora: +${{ number_format($pension->recargo_mora, 0) }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            {{ \Carbon\Carbon::parse($pension->fecha_vencimiento)->format('d/m/Y') }}
                                            @if($pension->isVencida() && !$pension->isPagada())
                                                <br><small class="text-danger">{{ $pension->diasVencida() }} días</small>
                                            @endif
                                        </td>
                                        <td>
                                            @switch($pension->estado)
                                                @case('pendiente')
                                                    <span class="badge bg-warning">Pendiente</span>
                                                    @break
                                                @case('pagada')
                                                    <span class="badge bg-success">Pagada</span>
                                                    <br><small class="text-muted">{{ \Carbon\Carbon::parse($pension->fecha_pago)->format('d/m/Y') }}</small>
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
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('pensiones.show', $pension) }}" class="btn btn-sm btn-outline-primary" title="Ver detalle">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                
                                                @if(!$pension->isPagada() && !$pension->isAnulada())
                                                    <button type="button" class="btn btn-sm btn-outline-success" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#pagoModal{{ $pension->id }}" 
                                                            title="Procesar pago">
                                                        <i class="fas fa-money-bill"></i>
                                                    </button>
                                                @endif

                                                @if(auth()->user()->roles_id != 4)
                                                    @if(!$pension->isPagada() && !$pension->isAnulada())
                                                        <a href="{{ route('pensiones.edit', $pension) }}" class="btn btn-sm btn-outline-warning" title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    @endif

                                                    @if(!$pension->isAnulada())
                                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#anularModal{{ $pension->id }}" 
                                                                title="Anular">
                                                            <i class="fas fa-ban"></i>
                                                        </button>
                                                    @endif
                                                @endif
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Modal para procesar pago -->
                                    @if(!$pension->isPagada() && !$pension->isAnulada())
                                        @include('pensiones.modals.pago', ['pension' => $pension])
                                    @endif

                                    <!-- Modal para anular -->
                                    @if(auth()->user()->roles_id != 4 && !$pension->isAnulada())
                                        @include('pensiones.modals.anular', ['pension' => $pension])
                                    @endif

                                @empty
                                    <tr>
                                        <td colspan="{{ auth()->user()->roles_id != 4 ? '9' : '8' }}" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-search fa-3x mb-3 opacity-50"></i>
                                                <p>No se encontraron pensiones con los filtros aplicados.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    @if($pensiones->hasPages())
                        <div class="d-flex justify-content-center mt-4">
                            {{ $pensiones->appends(request()->query())->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para generar pensiones masivas -->
@if(auth()->user()->roles_id != 4)
    @include('pensiones.modals.generar-masivas')
@endif

@endsection

@push('styles')
<style>
    .avatar {
        flex-shrink: 0;
    }
    
    .table td {
        vertical-align: middle;
    }
    
    .btn-group .btn {
        border-radius: 0.25rem !important;
        margin-right: 2px;
    }
    
    .btn-group .btn:last-child {
        margin-right: 0;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-actualizar recargos por mora cada 5 minutos
    setInterval(function() {
        fetch('{{ route("pensiones.actualizar-vencidas") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            }
        }).then(response => response.json())
          .then(data => {
              if (data.actualizadas > 0) {
                  location.reload();
              }
          });
    }, 300000); // 5 minutos
});
</script>
@endpush