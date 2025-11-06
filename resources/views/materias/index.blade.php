@extends('layouts.app')

@section('title', 'Gestión de Materias')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-book"></i> Gestión de Materias
                    </h3>
                    <a href="{{ route('materias.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nueva Materia
                    </a>
                </div>

                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('error') || $errors->has('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle"></i> 
                            {{ session('error') ?? $errors->first('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if($materias->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead class="bg-primary text-white">
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Descripción</th>
                                        <th>Curso</th>
                                        <th>Docente</th>
                                        <th>Fecha Creación</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($materias as $materia)
                                        <tr>
                                            <td>{{ $materia->id }}</td>
                                            <td>
                                                <strong>{{ $materia->nombre }}</strong>
                                            </td>
                                            <td>
                                                {{ $materia->descripcion ? Str::limit($materia->descripcion, 50) : 'Sin descripción' }}
                                            </td>
                                            <td>
                                                <span class="badge badge-info">
                                                    {{ $materia->curso ? $materia->curso->nombre : 'Sin asignar' }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($materia->docente)
                                                    <span class="badge badge-success">
                                                        {{ $materia->docente->name }}
                                                    </span>
                                                @else
                                                    <span class="badge badge-warning">Sin docente</span>
                                                @endif
                                            </td>
                                            <td>{{ $materia->created_at->format('d/m/Y') }}</td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('materias.show', $materia->id) }}" 
                                                       class="btn btn-info btn-sm" title="Ver detalles">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('materias.edit', $materia->id) }}" 
                                                       class="btn btn-warning btn-sm" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" 
                                                            class="btn btn-danger btn-sm btn-eliminar" 
                                                            title="Eliminar"
                                                            data-materia-id="{{ $materia->id }}"
                                                            data-materia-nombre="{{ $materia->nombre }}">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginación -->
                        <div class="d-flex justify-content-center mt-3">
                            {{ $materias->links() }}
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-book fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No hay materias registradas</h4>
                            <p class="text-muted">Comienza creando tu primera materia.</p>
                            <a href="{{ route('materias.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Crear Primera Materia
                            </a>
                        </div>
                    @endif
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

@section('scripts')
<script>
// Funcionalidad híbrida que funciona con jQuery y JavaScript vanilla
$(document).ready(function() {
    console.log('DOM cargado - iniciando event listeners para materias');
    
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