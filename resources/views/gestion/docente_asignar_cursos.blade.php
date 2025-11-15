@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="h4">Asignar cursos a: {{ $docente->name }}</h1>
    <p class="text-muted">Marca los cursos que este docente debe impartir.</p>

    <form action="{{ route('docentes.update', $docente->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="card mb-3">
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Cursos <small class="text-muted">(Selecciona uno o varios)</small></label>
                    <div class="row">
                        @foreach($cursos as $curso)
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="cursos[]" value="{{ $curso->id }}" id="curso_{{ $curso->id }}" @if(in_array($curso->id, $cursosAsignados)) checked @endif>
                                    <label class="form-check-label" for="curso_{{ $curso->id }}">{{ $curso->nombre }}</label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @error('cursos')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
                <button class="btn btn-primary">Guardar asignaciones</button>
                <a href="{{ route('docentes.index') }}" class="btn btn-link">Cancelar</a>

                {{-- Botón para deseleccionar todos los checkboxes (acción client-side) --}}
                <button type="button" id="btnQuitarTodos" class="btn btn-outline-danger ms-2">Quitar todos</button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    const btn = document.getElementById('btnQuitarTodos');
    if (!btn) return;
    btn.addEventListener('click', function(){
        if (!confirm('¿Seguro que quieres deseleccionar todos los cursos asignados a este docente?')) return;
        document.querySelectorAll('input[name="cursos[]"]:checked').forEach(function(cb){ cb.checked = false; });
    });
});
</script>
@endpush
