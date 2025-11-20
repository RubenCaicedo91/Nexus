@extends('layouts.app')

@section('title', 'Crear Materia')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-plus"></i> Crear Nueva Materia
                    </h3>
                </div>

                <form action="{{ route('materias.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        @if($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <h6><i class="fas fa-exclamation-circle"></i> Por favor, corrige los siguientes errores:</h6>
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nombre_select" class="required">
                                        <i class="fas fa-book"></i> Nombre de la Materia
                                    </label>
                                    <select id="nombre_select" class="form-control mb-2">
                                        <option value="">-- Seleccionar una materia existente (opcional) --</option>
                                        @foreach($materias as $m)
                                            <option value="{{ $m->nombre }}">{{ $m->nombre }}</option>
                                        @endforeach
                                        <option value="__other__">Otros...</option>
                                    </select>

                                    <input type="text"
                                           class="form-control @error('nombre') is-invalid @enderror"
                                           id="nombre"
                                           name="nombre"
                                           value="{{ old('nombre') }}"
                                           placeholder="Ej: Matemáticas, Español, Ciencias..."
                                           required>
                                    @error('nombre')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Puedes seleccionar una materia existente para autocompletar el nombre, o elegir "Otros..." y escribir uno nuevo.</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="cursos" class="required">
                                        <i class="fas fa-graduation-cap"></i> Cursos
                                    </label>
                                    <select class="form-control @error('cursos') is-invalid @enderror" 
                                            id="cursos" 
                                            name="cursos[]" 
                                            multiple
                                            required>
                                        @foreach($cursos as $curso)
                                            <option value="{{ $curso->id }}" 
                                                    {{ (is_array(old('cursos')) && in_array($curso->id, old('cursos'))) ? 'selected' : '' }}>
                                                {{ $curso->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('cursos')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Mantén presionada la tecla Ctrl (o Cmd) para seleccionar múltiples cursos.</small>
                                </div>
                                
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="docente_id">
                                        <i class="fas fa-chalkboard-teacher"></i> Docente (Opcional)
                                    </label>
                                    <select class="form-control @error('docente_id') is-invalid @enderror" 
                                            id="docente_id" 
                                            name="docente_id">
                                        <option value="">Sin docente asignado</option>
                                        @foreach($docentes as $docente)
                                            <option value="{{ $docente->id }}" 
                                                    {{ old('docente_id') == $docente->id ? 'selected' : '' }}>
                                                {{ $docente->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('docente_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        Puedes asignar un docente más tarde si no está disponible ahora.
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="descripcion">
                                <i class="fas fa-align-left"></i> Descripción
                            </label>
                            <textarea class="form-control @error('descripcion') is-invalid @enderror" 
                                      id="descripcion" 
                                      name="descripcion" 
                                      rows="4" 
                                      placeholder="Descripción detallada de la materia, objetivos, contenidos, etc.">{{ old('descripcion') }}</textarea>
                            @error('descripcion')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Máximo 1000 caracteres. Describe brevemente la materia y sus objetivos.
                            </small>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <div class="col-md-6">
                                <a href="{{ route('materias.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Volver al Listado
                                </a>
                            </div>
                            <div class="col-md-6 text-right">
                                <button type="reset" class="btn btn-warning">
                                    <i class="fas fa-undo"></i> Limpiar
                                </button>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i> Crear Materia
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
.required::after {
    content: " *";
    color: red;
}

.form-group label {
    font-weight: 600;
    color: #495057;
}

.card-header {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
}

.btn-group .btn {
    margin-right: 5px;
}
</style>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function(){
    var selectNombre = document.getElementById('nombre_select');
    var inputNombre = document.getElementById('nombre');

    if (selectNombre && inputNombre) {
        selectNombre.addEventListener('change', function(){
            var val = this.value;
            if (val === '__other__') {
                inputNombre.value = '';
                inputNombre.focus();
            } else if (val === '') {
                // no hacer nada
            } else {
                inputNombre.value = val;
            }
        });

        // Al perder foco en el input, si el valor no está en la lista y no está vacío, añadirlo al select
        inputNombre.addEventListener('blur', function(){
            var v = this.value.trim();
            if (!v) return;
            var exists = Array.from(selectNombre.options).some(function(o){ return o.value === v; });
            if (!exists) {
                var opt = document.createElement('option');
                opt.value = v;
                opt.text = v;
                // insertar antes de la opción 'Otros...' si existe
                var otherOpt = Array.from(selectNombre.options).find(function(o){ return o.value === '__other__'; });
                if (otherOpt) selectNombre.insertBefore(opt, otherOpt);
                else selectNombre.appendChild(opt);
            }
        });
    }
});
</script>
@endsection