{{-- index.blade.php --}}
@extends('layouts.app')
@section('content')
<h2>Registrar Sanción</h2>

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
    <div class="mb-3">
        <label for="buscador_estudiante_registrar" class="form-label">Estudiante</label>
        {{-- Campo de texto con sugerencias; el id real se guarda en el input hidden usuario_id --}}
        <input id="buscador_estudiante_registrar" list="lista_estudiantes_registrar" class="form-control" placeholder="Escribe un nombre..." autocomplete="off" required>
        <datalist id="lista_estudiantes_registrar">
            @if(isset($students) && count($students))
                @foreach($students as $stu)
                    @php $disp = $stu->name . ' (ID: ' . $stu->id . ')'; @endphp
                    <option value="{{ $disp }}"></option>
                @endforeach
            @endif
        </datalist>
        <input type="hidden" name="usuario_id" id="usuario_id_hidden">
        <small class="form-text text-muted">Selecciona un estudiante de la lista. Si no se selecciona, el formulario no enviará el id.</small>
    </div>


<script>
    (function(){
        // Mapa nombre -> id para asignar el usuario_id al seleccionar
        @php
            $map = [];
            if (isset($students) && count($students)) {
                foreach ($students as $s) {
                    $map[$s->name . ' (ID: ' . $s->id . ')'] = $s->id;
                }
            }
        @endphp
        const studentMap = @json($map);

        const input = document.getElementById('buscador_estudiante_registrar');
        const hidden = document.getElementById('usuario_id_hidden');
        // Cuando el usuario escribe o selecciona, si el texto coincide exactamente con una clave, asignamos el id
        if (input) {
            input.addEventListener('input', function(e){
                const v = (e.target.value || '').trim();
                if (v === '') { hidden.value = ''; return; }
                if (Object.prototype.hasOwnProperty.call(studentMap, v)) {
                    hidden.value = studentMap[v];
                } else {
                    // intentar coincidencia por inicio (primera opción), útil si el datalist muestra nombres exactos
                    hidden.value = '';
                }
            });

            // Al enviar el formulario verificar que hidden tenga valor
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

    <a href="{{ route('gestion-disciplinaria.index') }}" class="btn btn-secondary">
    Volver
</a>
</form>

@endsection