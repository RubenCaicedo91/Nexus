@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Editar Horario</h2>

    <form action="{{ route('horarios.actualizar', $horario->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="curso" class="form-label">Curso</label>
            <input type="text" name="curso" class="form-control" value="{{ old('curso', $horario->curso) }}" required>
        </div>

        <div class="mb-3">
            <label for="dia" class="form-label">Día</label>
            <input type="text" name="dia" class="form-control" value="{{ old('dia', $horario->dia) }}" required>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="hora_inicio" class="form-label">Hora inicio</label>
                <input type="time" name="hora_inicio" id="hora_inicio" class="form-control" value="{{ old('hora_inicio', $horario->hora_inicio ?? $horario->hora) }}" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="hora_fin" class="form-label">Hora fin</label>
                <input type="time" name="hora_fin" id="hora_fin" class="form-control" value="{{ old('hora_fin', $horario->hora_fin) }}">
            </div>
        </div>

        <div class="mb-3">
            <label for="materia_id" class="form-label">Materia</label>
            <select name="materia_id" id="materia_id" class="form-select">
                <option value="">(Sin materia)</option>
                @foreach($materias as $m)
                    <option value="{{ $m->id }}" @if(old('materia_id', $horario->materia_id) == $m->id) selected @endif>{{ $m->nombre }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Actualizar</button>
        <a href="{{ route('gestion.horarios') }}" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
@endsection
