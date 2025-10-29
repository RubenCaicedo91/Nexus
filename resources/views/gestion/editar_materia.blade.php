
@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="h4">✏️ Editar materia: {{ $materia->nombre }}</h1>
    <p class="text-muted">Curso: {{ $curso->nombre }}</p>

    <form action="{{ route('materias.actualizar', $materia->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="card mb-3">
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="nombre" class="form-control" value="{{ old('nombre', $materia->nombre) }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Descripción</label>
                    <input type="text" name="descripcion" class="form-control" value="{{ old('descripcion', $materia->descripcion) }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Docente asignado</label>
                    <select name="docente_id" class="form-select">
                        <option value="">-- Ninguno --</option>
                        @foreach($docentes as $d)
                            <option value="{{ $d->id }}" @if($materia->docente_id == $d->id) selected @endif>{{ $d->name }} ({{ $d->email }})</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn btn-primary">Guardar</button>
                <a href="{{ route('cursos.materias', $curso->id) }}" class="btn btn-link">Cancelar</a>
            </div>
        </div>
    </form>
</div>
@endsection
