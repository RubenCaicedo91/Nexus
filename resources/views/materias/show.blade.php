@extends('layouts.app')

@section('title', 'Detalles de Materia')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-book"></i> Detalles de la Materia
                    </h3>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h4 class="text-primary">{{ $materia->nombre }}</h4>
                            <p class="text-muted mb-3">
                                <i class="fas fa-hashtag"></i> ID: {{ $materia->id }}
                            </p>
                        </div>
                        <div class="col-md-6 text-right">
                            <div class="btn-group" role="group">
                                <a href="{{ route('materias.edit', $materia->id) }}" 
                                   class="btn btn-warning">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                                <button type="button" 
                                        class="btn btn-danger btn-eliminar" 
                                        data-materia-id="{{ $materia->id }}"
                                        data-materia-nombre="{{ $materia->nombre }}">
                                    <i class="fas fa-trash"></i> Eliminar
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-info">
                                    <i class="fas fa-graduation-cap"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Curso Asignado</span>
                                    <span class="info-box-number">
                                        {{ $materia->curso ? $materia->curso->nombre : 'Sin asignar' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="info-box">
                                <span class="info-box-icon {{ $materia->docente ? 'bg-success' : 'bg-warning' }}">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Docente Asignado</span>
                                    <span class="info-box-number">
                                        {{ $materia->docente ? $materia->docente->name : 'Sin docente' }}
                                    </span>
                                    @if($materia->docente)
                                        <span class="info-box-desc">
                                            <i class="fas fa-envelope"></i> {{ $materia->docente->email }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($materia->descripcion)
                        <div class="mt-4">
                            <h5><i class="fas fa-align-left"></i> Descripción</h5>
                            <div class="card">
                                <div class="card-body">
                                    <p class="mb-0">{{ $materia->descripcion }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="row mt-4">
                        <div class="col-md-6">
                            <h6><i class="fas fa-calendar-plus"></i> Fecha de Creación</h6>
                            <p class="text-muted">
                                {{ $materia->created_at->format('d/m/Y H:i:s') }}
                                <small>({{ $materia->created_at->diffForHumans() }})</small>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-calendar-edit"></i> Última Modificación</h6>
                            <p class="text-muted">
                                {{ $materia->updated_at->format('d/m/Y H:i:s') }}
                                <small>({{ $materia->updated_at->diffForHumans() }})</small>
                            </p>
                        </div>
                    </div>

                    <!-- Información adicional del curso -->
                    @if($materia->curso)
                        <div class="mt-4">
                            <h5><i class="fas fa-info-circle"></i> Información del Curso</h5>
                            <div class="card">
                                <div class="card-body">
                                    <h6>{{ $materia->curso->nombre }}</h6>
                                    @if($materia->curso->descripcion)
                                        <p class="text-muted mb-0">{{ $materia->curso->descripcion }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="card-footer">
                    <div class="row">
                        <div class="col-md-6">
                            <a href="{{ route('materias.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Volver al Listado
                            </a>
                        </div>
                        <div class="col-md-6 text-right">
                            <a href="{{ route('materias.edit', $materia->id) }}" class="btn btn-primary">
                                <i class="fas fa-edit"></i> Editar Materia
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmación para eliminar -->
<div class="modal fade" id="modalEliminar" tabindex="-1" aria-labelledby="modalEliminarLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalEliminarLabel">
                    <i class="fas fa-exclamation-triangle"></i> Confirmar Eliminación
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas eliminar la materia <strong id="nombreMateria"></strong>?</p>
                <p class="text-danger">
                    <i class="fas fa-warning"></i> Esta acción no se puede deshacer.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="formEliminar" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
.info-box {
    display: flex;
    background: #fff;
    border: 1px solid #d2d6de;
    border-radius: 0.25rem;
    margin-bottom: 15px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
}

.info-box-icon {
    width: 70px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.info-box-content {
    padding: 15px;
    flex: 1;
}

.info-box-text {
    text-transform: uppercase;
    font-weight: bold;
    font-size: 0.75rem;
    color: #6c757d;
}

.info-box-number {
    display: block;
    font-weight: bold;
    font-size: 1.1rem;
    color: #333;
}

.info-box-desc {
    display: block;
    font-size: 0.8rem;
    color: #6c757d;
    margin-top: 5px;
}

.card-header {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
    color: white;
}
</style>
@endsection

@section('scripts')
<script>
// Funcionalidad híbrida que funciona con jQuery y JavaScript vanilla
$(document).ready(function() {
    console.log('DOM cargado - iniciando event listeners para materias (show)');
    
    // Event delegation con jQuery (más compatible)
    $(document).on('click', '.btn-eliminar', function(e) {
        e.preventDefault();
        console.log('Botón eliminar clickeado con jQuery');
        
        const id = $(this).data('materia-id');
        const nombre = $(this).data('materia-nombre');
        
        console.log('ID:', id, 'Nombre:', nombre);
        
        $('#nombreMateria').text(nombre);
        $('#formEliminar').attr('action', '{{ url("materias") }}/' + id);
        
        // Usar Bootstrap 5 modal
        const modalEl = document.getElementById('modalEliminar');
        if (modalEl && typeof bootstrap !== 'undefined') {
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
            console.log('Modal Bootstrap 5 mostrado');
        } else if (typeof $ !== 'undefined' && $.fn.modal) {
            // Fallback a Bootstrap 4/jQuery
            $('#modalEliminar').modal('show');
            console.log('Modal jQuery/Bootstrap 4 mostrado');
        } else {
            alert('¿Confirmas eliminar la materia "' + nombre + '"?');
        }
    });
});

// Función de respaldo para llamadas directas
function confirmarEliminacion(id, nombre) {
    document.getElementById('nombreMateria').textContent = nombre;
    document.getElementById('formEliminar').action = '{{ url("materias") }}/' + id;
    
    const modalEl = document.getElementById('modalEliminar');
    if (modalEl && typeof bootstrap !== 'undefined') {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    } else if (typeof $ !== 'undefined') {
        $('#modalEliminar').modal('show');
    }
}
</script>
@endsection