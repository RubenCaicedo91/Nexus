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
                    <label class="form-label">Cursos</label>
                    <select name="cursos[]" class="form-select" multiple size="8">
                        @foreach($cursos as $curso)
                            <option value="{{ $curso->id }}" @if(in_array($curso->id, $cursosAsignados)) selected @endif>{{ $curso->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn btn-primary">Guardar asignaciones</button>
                <a href="{{ route('docentes.index') }}" class="btn btn-link">Cancelar</a>
            </div>
        </div>
    </form>
</div>
@endsection
