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

        <div class="mb-3">
            <label for="hora" class="form-label">Hora</label>
            <input type="time" name="hora" class="form-control" value="{{ old('hora', $horario->hora) }}" required>
        </div>

        <button type="submit" class="btn btn-primary">Actualizar</button>
        <a href="{{ route('gestion.horarios') }}" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
@endsection
