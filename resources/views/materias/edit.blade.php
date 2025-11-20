@extends('layouts.app')

@section('title', 'Editar Materia')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-edit"></i> Editar Materia: {{ $materia->nombre }}
                    </h3>
                </div>

                <form action="{{ route('materias.update', $materia->id) }}" method="POST">
                    @csrf
                    @method('PUT')
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
                                           value="{{ old('nombre', $materia->nombre) }}"
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
                                        @php $selectedCursos = old('cursos', $materia->cursos->pluck('id')->toArray()); @endphp
                                        @foreach($cursos as $curso)
                                            <option value="{{ $curso->id }}" 
                                                    {{ in_array($curso->id, $selectedCursos) ? 'selected' : '' }}>
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
                                                    {{ old('docente_id', $materia->docente_id) == $docente->id ? 'selected' : '' }}>
                                                {{ $docente->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('docente_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        Puedes cambiar el docente asignado o dejarlo sin asignar.
                                    </small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>
                                        <i class="fas fa-info-circle"></i> Información de Registro
                                    </label>
                                    <div class="form-control-plaintext">
                                        <small class="text-muted">
                                            <strong>Creado:</strong> {{ $materia->created_at->format('d/m/Y H:i') }}<br>
                                            <strong>Modificado:</strong> {{ $materia->updated_at->format('d/m/Y H:i') }}
                                        </small>
                                    </div>
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
                                      placeholder="Descripción detallada de la materia, objetivos, contenidos, etc.">{{ old('descripcion', $materia->descripcion) }}</textarea>
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
                                <a href="{{ route('materias.show', $materia->id) }}" class="btn btn-info">
                                    <i class="fas fa-eye"></i> Ver Detalles
                                </a>
                            </div>
                            <div class="col-md-6 text-right">
                                <button type="reset" class="btn btn-warning" onclick="resetForm()">
                                    <i class="fas fa-undo"></i> Restaurar
                                </button>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i> Guardar Cambios
                                </button>
                            </div>
                        </div>
                    </div>
                    <!-- Campo oculto con los cursos seleccionados (JSON) para uso en JS -->
                    <input type="hidden" id="selectedCursosData" value='@json(old('cursos', $materia->cursos->pluck('id')->toArray()))'>
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
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
}

.btn-group .btn {
    margin-right: 5px;
}
</style>
@endsection

@section('scripts')
<script>
function resetForm() {
    // Restaurar valores originales
    document.getElementById('nombre').value = '{{ $materia->nombre }}';
    // Restaurar select de nombre si existe
    var nombreSelect = document.getElementById('nombre_select');
    if (nombreSelect) {
        var found = false;
        for (var i = 0; i < nombreSelect.options.length; i++) {
            if (nombreSelect.options[i].value === '{{ $materia->nombre }}') { nombreSelect.selectedIndex = i; found = true; break; }
        }
        if (!found) {
            // crear opción y seleccionarla
            var opt = document.createElement('option'); opt.value = '{{ $materia->nombre }}'; opt.text = '{{ $materia->nombre }}';
            // insertar antes de 'Otros...' si existe
            var other = Array.from(nombreSelect.options).find(function(o){ return o.value === '__other__'; });
            if (other) nombreSelect.insertBefore(opt, other); else nombreSelect.appendChild(opt);
            nombreSelect.value = '{{ $materia->nombre }}';
        }
    }
    // restaurar selección múltiple de cursos desde el campo oculto JSON
    var selected = [];
    var selectedDataEl = document.getElementById('selectedCursosData');
    if (selectedDataEl) {
        try {
            selected = JSON.parse(selectedDataEl.value || '[]');
        } catch (e) {
            selected = [];
        }
    }
    var cursoSel = document.querySelector('select[name="cursos[]"]');
    if (cursoSel) {
        for (var i = 0; i < cursoSel.options.length; i++) {
            cursoSel.options[i].selected = selected.indexOf(parseInt(cursoSel.options[i].value)) !== -1;
        }
    }
    document.querySelector('select[name="docente_id"]').value = '{{ $materia->docente_id ?? "" }}';
    document.getElementById('descripcion').value = '{{ $materia->descripcion }}';
}
// Mostrar/ocultar campo nuevo curso cuando se seleccione 'Otros'
document.addEventListener('DOMContentLoaded', function(){
    var selectNombre = document.getElementById('nombre_select');
    var inputNombre = document.getElementById('nombre');

    if (selectNombre && inputNombre) {
        // seleccionar la opcion correspondiente si existe, sino crearla
        var current = '{{ $materia->nombre }}';
        var found = Array.from(selectNombre.options).some(function(o){ return o.value === current; });
        if (found) selectNombre.value = current;
        else {
            var opt = document.createElement('option'); opt.value = current; opt.text = current;
            var other = Array.from(selectNombre.options).find(function(o){ return o.value === '__other__'; });
            if (other) selectNombre.insertBefore(opt, other); else selectNombre.appendChild(opt);
            selectNombre.value = current;
        }

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

        inputNombre.addEventListener('blur', function(){
            var v = this.value.trim();
            if (!v) return;
            var exists = Array.from(selectNombre.options).some(function(o){ return o.value === v; });
            if (!exists) {
                var opt = document.createElement('option');
                opt.value = v; opt.text = v;
                var otherOpt = Array.from(selectNombre.options).find(function(o){ return o.value === '__other__'; });
                if (otherOpt) selectNombre.insertBefore(opt, otherOpt);
                else selectNombre.appendChild(opt);
            }
        });
    }
});
</script>
@endsection