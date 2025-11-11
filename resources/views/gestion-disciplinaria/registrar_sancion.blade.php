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
    // Usamos base64 para evitar que el editor/lingüeta JS interprete mal sintaxis Blade/JSON
    const studentList = JSON.parse(atob('{{ base64_encode(json_encode($studentArray ?? [])) }}'));

        const input = document.getElementById('buscador_estudiante_registrar');
        const hidden = document.getElementById('usuario_id_hidden');

        function findStudentIdByInput(text) {
            if (!text) return '';
            const q = text.trim().toLowerCase();
            // 1) exact match on display
            for (const s of studentList) {
                if ((s.display || '').toLowerCase() === q) return s.id;
            }
            // 2) exact match on name
            for (const s of studentList) {
                if ((s.name || '').toLowerCase() === q) return s.id;
            }
            // 3) startsWith on name
            for (const s of studentList) {
                if ((s.name || '').toLowerCase().startsWith(q)) return s.id;
            }
            // 4) includes on name
            for (const s of studentList) {
                if ((s.name || '').toLowerCase().includes(q)) return s.id;
            }
            return '';
        }

        if (input) {
            input.addEventListener('input', function(e){
                const v = (e.target.value || '').trim();
                if (v === '') { hidden.value = ''; return; }
                const id = findStudentIdByInput(v);
                hidden.value = id || '';
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