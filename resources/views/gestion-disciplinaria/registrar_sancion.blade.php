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
        <label for="usuario_id" class="form-label">ID Usuario</label>
        <input type="number" name="usuario_id" class="form-control" required>
    </div>
    <div class="mb-3">
        <label for="descripcion" class="form-label">Descripción</label>
        <input type="text" name="descripcion" class="form-control" required>
    </div>

    <div class="mb-3">
        <label for="tipo_id" class="form-label">Tipo</label>
        <select name="tipo_id" id="tipo_id" class="form-control" required>
            <option value="">-- Selecciona un tipo --</option>
                @if(isset($tipos) && count($tipos))
                    @foreach($tipos as $t)
                        <option value="{{ $t->id }}" {{ old('tipo_id') == $t->id ? 'selected' : '' }}>{{ $t->nombre }}</option>
                    @endforeach
                @endif
        </select>
    </div>
    <div id="suspension_fields" class="mb-3" style="display:none">
        <label class="form-label">Fecha inicio sanción</label>
        <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control" value="{{ old('fecha_inicio') }}">
        <div id="fecha_fin_wrap">
            <label class="form-label mt-2">Fecha fin sanción</label>
            <input type="date" name="fecha_fin" id="fecha_fin" class="form-control" value="{{ old('fecha_fin') }}">
        </div>
    </div>
    <div id="meeting_fields" class="mb-3" style="display:none">
        <label class="form-label">Fecha y hora de reunión (padre/tutor)</label>
        <input type="datetime-local" name="reunion_at" id="reunion_at" class="form-control" value="{{ old('reunion_at') }}">
    </div>
    <div id="monetary_fields" class="mb-3" style="display:none">
        <label class="form-label">Monto a pagar</label>
        <input type="number" step="0.01" min="0" name="monto" id="monto" class="form-control" value="{{ old('monto') }}">
        <small class="form-text text-muted">Este monto debe ser pagado por el responsable.</small>
        <input type="hidden" name="pago_observacion" id="pago_observacion" value="{{ old('pago_observacion', '') }}">
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