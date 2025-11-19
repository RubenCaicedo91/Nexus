@extends('layouts.app')

@section('title', 'Registro de asistencia por curso')

@section('content')
    <div class="mb-3">
        <h3>Registro de asistencia - Curso: {{ $curso->nombre }}</h3>
    </div>
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form id="asistencias-form" action="{{ route('asistencias.curso.registrar', ['cursoId' => $curso->id]) }}" method="POST">
        @csrf
        <div class="row mb-3">
            <div class="col-md-3">
                <label class="form-label">Fecha</label>
                <input type="date" name="fecha" class="form-control" value="{{ old('fecha', $fecha ?? date('Y-m-d')) }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Materia (opcional)</label>
                <select name="materia_id" class="form-control">
                    <option value="">-- Todas --</option>
                    @foreach($materias as $mat)
                        <option value="{{ $mat->id }}" {{ (isset($materiaId) && $materiaId == $mat->id) ? 'selected' : '' }}>{{ $mat->nombre }}</option>
                    @endforeach
                </select>
            </div>
                {{-- definitiva option removed --}}
            <div class="col-md-3 text-end">
                <label class="form-label d-block">Acciones rápidas</label>
                <div class="btn-group" role="group">
                    <button type="button" id="marcar-todos-presentes" class="btn btn-sm btn-success">Marcar todos presentes</button>
                    <button type="button" id="marcar-todos-ausentes" class="btn btn-sm btn-warning">Marcar todos ausentes</button>
                    <button type="button" id="limpiar-todos" class="btn btn-sm btn-secondary">Limpiar</button>
                </div>
            </div>
        </div>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Estudiante</th>
                    <th class="text-center">Asistió</th>
                    <th class="text-center">No asistió</th>
                    <th class="text-center">Excusa</th>
                    <th>Observación</th>
                </tr>
            </thead>
            <tbody>
                @foreach($matriculas as $m)
                    @php $existing = $existingAsistencias[$m->id] ?? null; @endphp
                    <tr>
                        <td>{{ optional($m->user)->name }}</td>
                        <td class="text-center">
                            <input type="radio" name="statuses[{{ $m->id }}]" value="present" {{ ($existing ? ($existing->presente ? 'checked' : '') : (old('statuses.'.$m->id, 'absent') == 'present' ? 'checked' : '')) }}>
                        </td>
                        <td class="text-center">
                            <input type="radio" name="statuses[{{ $m->id }}]" value="absent" {{ ($existing ? (!$existing->presente ? 'checked' : '') : (old('statuses.'.$m->id, 'absent') == 'absent' ? 'checked' : '')) }}>
                        </td>
                        <td class="text-center">
                            <input type="radio" name="statuses[{{ $m->id }}]" value="excuse" {{ ($existing ? ($existing->presente === null && $existing->observacion ? 'checked' : '') : (old('statuses.'.$m->id) == 'excuse' ? 'checked' : '')) }}>
                        </td>
                        <td>
                            <input type="text" name="observations[{{ $m->id }}]" class="form-control" value="{{ $existing ? $existing->observacion : old('observations.'.$m->id) }}" placeholder="Opcional">
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Guardar asistencias</button>
            <a href="{{ route('asistencias.index') }}" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    const form = document.getElementById('asistencias-form');

    function setAll(status){
        document.querySelectorAll('input[type="radio"][value="present"]').forEach(r => r.checked = (status==='present'));
        document.querySelectorAll('input[type="radio"][value="absent"]').forEach(r => r.checked = (status==='absent'));
        document.querySelectorAll('input[type="radio"][value="excuse"]').forEach(r => r.checked = (status==='excuse'));
    }

    document.getElementById('marcar-todos-presentes').addEventListener('click', function(){ setAll('present'); });
    document.getElementById('marcar-todos-ausentes').addEventListener('click', function(){ setAll('absent'); });
    document.getElementById('limpiar-todos').addEventListener('click', function(){
        document.querySelectorAll('input[type="radio"]').forEach(r => r.checked = false);
        document.querySelectorAll('input[type="text"]').forEach(i => i.value = '');
    });

    // Removed AJAX save button handler; the form submit (Guardar asistencias) handles saving.
});
</script>
@endsection
@endsection
