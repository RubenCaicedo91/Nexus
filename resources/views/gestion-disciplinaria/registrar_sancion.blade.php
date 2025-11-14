{{-- resources/views/gestion-disciplinaria/index.blade.php --}}
@extends('layouts.app')

@section('content')
<h2>Registrar Sanción</h2>

{{-- Mostrar errores de validación --}}
@if($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('gestion-disciplinaria.store') }}" method="POST">
    @csrf

    {{-- Campo de búsqueda de estudiante --}}
    <div class="mb-3">
        <label for="buscador_estudiante_registrar" class="form-label">Estudiante</label>
        <input id="buscador_estudiante_registrar"
               list="lista_estudiantes_registrar"
               class="form-control"
               placeholder="Escribe un nombre..."
               autocomplete="off"
               required>

        <datalist id="lista_estudiantes_registrar">
            @if(isset($students) && count($students))
                @foreach($students as $stu)
                    <option value="{{ $stu->name }} (ID: {{ $stu->id }})"></option>
                @endforeach
            @endif
        </datalist>

        <input type="hidden" name="usuario_id" id="usuario_id_hidden">

        <small class="form-text text-muted">
            Selecciona un estudiante de la lista. Si no se selecciona, el formulario no se enviará.
        </small>
    </div>

    {{-- Campos del formulario --}}
    <div class="mb-3">
        <label for="descripcion" class="form-label">Descripción</label>
        <input type="text" name="descripcion" class="form-control" required>
    </div>

    <div class="mb-3">
        <label for="tipo" class="form-label">Tipo</label>
        <input type="text" name="tipo" class="form-control" required>
    </div>

    <div class="mb-3">
        <label for="fecha" class="form-label">Fecha</label>
        <input type="date" name="fecha" class="form-control" required>
    </div>

    <button type="submit" class="btn btn-success">Registrar</button>
    <a href="{{ route('gestion-disciplinaria.index') }}" class="btn btn-secondary">Cancelar</a>
</form>

{{-- Script JS --}}
<script>
    (function(){
        // Si el backend no pasa $studentArray, se usa []
        const studentList = JSON.parse(atob('{{ base64_encode(json_encode($studentArray ?? [])) }}'));

        const input = document.getElementById('buscador_estudiante_registrar');
        const hidden = document.getElementById('usuario_id_hidden');

        function findStudentIdByInput(text) {
            if (!text) return '';
            const q = text.trim().toLowerCase();

            // Buscar coincidencias exactas o parciales
            for (const s of studentList) {
                const display = `${s.name} (ID: ${s.id})`.toLowerCase();
                if (display === q || display.includes(q)) return s.id;
                if ((s.name || '').toLowerCase() === q) return s.id;
                if (s.document_number && ('' + s.document_number).toLowerCase() === q) return s.id;
            }
            return '';
        }

        if (input) {
            input.addEventListener('input', function(e){
                const v = (e.target.value || '').trim();
                hidden.value = findStudentIdByInput(v);
            });

            // Validar antes de enviar
            const form = input.closest('form');
            if (form) {
                form.addEventListener('submit', function(ev){
                    if (!hidden.value) {
                        ev.preventDefault();
                        alert('Por favor selecciona un estudiante válido de la lista.');
                        input.focus();
                    }
                });
            }
        }
    })();
</script>
@endsection
