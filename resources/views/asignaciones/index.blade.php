@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-users me-2"></i>Asignaciones de Estudiantes
                    </h4>
                    <a href="{{ route('asignaciones.create') }}" class="btn btn-light">
                        <i class="fas fa-plus me-2"></i>Nueva Asignación
                    </a>
                </div>

                <div class="card-body">
                    {{-- Filtros --}}
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label for="curso_id" class="form-label">Curso</label>
                            <select name="curso_id" id="curso_id" class="form-select">
                                <option value="">Todos los cursos</option>
                                @foreach($cursos as $curso)
                                    <option value="{{ $curso->id }}" {{ request('curso_id') == $curso->id ? 'selected' : '' }}>
                                        {{ $curso->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        {{-- CAMBIO AQUÍ: El filtro de estudiante ahora es un desplegable --}}
                        <div class="col-md-3">
                            <label for="estudiante" class="form-label">Estudiante</label>
                            <select name="estudiante" id="estudiante" class="form-select">
                                <option value="">Todos los estudiantes</option>
                                @foreach($estudiantes as $estudiante)
                                    <option value="{{ $estudiante->name }}" {{ request('estudiante') == $estudiante->name ? 'selected' : '' }}>
                                        {{ $estudiante->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="estado" class="form-label">Estado</label>
                            <select name="estado" id="estado" class="form-select">
                                <option value="">Todos</option>
                                <option value="activa" {{ request('estado') == 'activa' ? 'selected' : '' }}>Activa</option>
                                <option value="inactiva" {{ request('estado') == 'inactiva' ? 'selected' : '' }}>Inactiva</option>
                                <option value="suspendida" {{ request('estado') == 'suspendida' ? 'selected' : '' }}>Suspendida</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="documentos_completos" class="form-label">Documentos</label>
                            <select name="documentos_completos" id="documentos_completos" class="form-select">
                                <option value="">Todos</option>
                                <option value="1" {{ request('documentos_completos') == '1' ? 'selected' : '' }}>Completos</option>
                                <option value="0" {{ request('documentos_completos') == '0' ? 'selected' : '' }}>Incompletos</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search"></i> Filtrar
                            </button>
                            <a href="{{ route('asignaciones.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </form>

                    {{-- Tabla de asignaciones --}}
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Estudiante</th>
                                    <th>Curso</th>
                                    <th>Fecha Matrícula</th>
                                    <th>Estado</th>
                                    <th>Documentos</th>
                                    <th>Pago</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($asignaciones as $asignacion)
                                    <tr>
                                        <td>{{ $asignacion->id }}</td>
                                        <td>
                                            <div class="fw-bold">{{ $asignacion->user->name }}</div>
                                            <small class="text-muted">{{ $asignacion->user->email }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">{{ $asignacion->curso->nombre ?? 'Sin asignar' }}</span>
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($asignacion->fecha_matricula)->format('d/m/Y') }}</td>
                                        <td>
                                            @switch($asignacion->estado)
                                                @case('activa')
                                                    <span class="badge bg-success">Activa</span>
                                                    @break
                                                @case('inactiva')
                                                    <span class="badge bg-secondary">Inactiva</span>
                                                    @break
                                                @case('suspendida')
                                                    <span class="badge bg-warning">Suspendida</span>
                                                    @break
                                                @default
                                                    <span class="badge bg-light text-dark">{{ $asignacion->estado }}</span>
                                            @endswitch
                                        </td>
                                        <td>
                                            @if($asignacion->documentos_completos)
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i>Completos
                                                </span>
                                            @else
                                                <span class="badge bg-danger">
                                                    <i class="fas fa-times me-1"></i>Incompletos
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($asignacion->monto_pago && $asignacion->fecha_pago)
                                                <div class="text-success fw-bold">${{ number_format($asignacion->monto_pago, 0, ',', '.') }}</div>
                                                <small class="text-muted">{{ \Carbon\Carbon::parse($asignacion->fecha_pago)->format('d/m/Y') }}</small>
                                            @else
                                                <span class="text-muted">Sin pago</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('asignaciones.show', $asignacion) }}" 
                                                   class="btn btn-sm btn-outline-primary" title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('asignaciones.edit', $asignacion) }}" 
                                                   class="btn btn-sm btn-outline-warning" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        data-bs-toggle="modal" data-bs-target="#deleteModal{{ $asignacion->id }}" title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>

                                            {{-- Modal de confirmación para eliminar --}}
                                            <div class="modal fade" id="deleteModal{{ $asignacion->id }}" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Confirmar eliminación</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            ¿Está seguro de que desea eliminar la asignación de <strong>{{ $asignacion->user->name }}</strong> 
                                                            al curso <strong>{{ $asignacion->curso->nombre ?? 'Sin asignar' }}</strong>?
                                                            <br><br>
                                                            <div class="alert alert-warning">
                                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                                Esta acción también eliminará todos los documentos asociados y no se puede deshacer.
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                            <form action="{{ route('asignaciones.destroy', $asignacion) }}" method="POST" class="d-inline">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-danger">
                                                                    <i class="fas fa-trash me-2"></i>Eliminar
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                                <h5>No hay asignaciones registradas</h5>
                                                <p>Comience creando una nueva asignación de estudiante.</p>
                                                <a href="{{ route('asignaciones.create') }}" class="btn btn-primary">
                                                    <i class="fas fa-plus me-2"></i>Crear Primera Asignación
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Paginación --}}
                    @if($asignaciones->hasPages())
                        <div class="d-flex justify-content-center mt-4">
                            {{ $asignaciones->withQueryString()->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit form when filters change
    // CAMBIO AQUÍ: Se añade 'estudiante' a la lista de filtros que envían el formulario automáticamente
    const filters = ['curso_id', 'estudiante', 'estado', 'documentos_completos'];
    filters.forEach(filterId => {
        const element = document.getElementById(filterId);
        if (element) {
            element.addEventListener('change', function() {
                this.form.submit();
            });
        }
    });
});
</script>
@endsection