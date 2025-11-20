@extends('layouts.app')

@section('title', 'Editar asistencia')

@section('content')
    <div class="mb-3">
        <h3>Editar asistencia</h3>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('asistencias.update', $asistencia->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label>Fecha</label>
            <input type="date" name="fecha" class="form-control" value="{{ old('fecha', $asistencia->fecha->format('Y-m-d')) }}" required>
        </div>

        <div class="mb-3">
            <label>Curso</label>
            <select name="curso_id" class="form-control" required>
                <option value="">-- Seleccione --</option>
                @foreach($cursos as $curso)
                    <option value="{{ $curso->id }}" {{ (old('curso_id', $asistencia->curso_id) == $curso->id) ? 'selected' : '' }}>{{ $curso->nombre }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Estudiante</label>
            <select name="estudiante_id" class="form-control" required>
                <option value="">-- Seleccione --</option>
                @foreach($estudiantes as $e)
                    <option value="{{ $e->id }}" {{ (old('estudiante_id', $asistencia->estudiante_id) == $e->id) ? 'selected' : '' }}>{{ $e->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" name="presente" value="1" class="form-check-input" id="presente" {{ old('presente', $asistencia->presente) ? 'checked' : '' }}>
            <label class="form-check-label" for="presente">Presente</label>
        </div>

        <div class="mb-3">
            <label>Observación</label>
            <textarea name="observacion" class="form-control">{{ old('observacion', $asistencia->observacion) }}</textarea>
        </div>

        <button class="btn btn-primary">Guardar</button>
        <a href="{{ route('asistencias.index') }}" class="btn btn-secondary">Cancelar</a>
    </form>
@endsection
