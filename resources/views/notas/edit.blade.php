@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4>Editar Nota</h4>
            <a href="{{ route('notas.index') }}" class="btn btn-sm btn-secondary">Volver</a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('notas.update', $nota) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">Estudiante</label>
                    <div>{{ $nota->matricula->user->name ?? 'N/A' }}</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Materia</label>
                    <div>{{ $nota->materia->nombre ?? 'N/A' }}</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Periodo</label>
                    <div>{{ $nota->periodo }}</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Valor</label>
                    <input type="number" step="0.01" name="valor" class="form-control" value="{{ old('valor', $nota->valor) }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Observaciones</label>
                    <textarea name="observaciones" class="form-control">{{ old('observaciones', $nota->observaciones) }}</textarea>
                </div>

                <div class="d-flex justify-content-end">
                    <button class="btn btn-success">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
