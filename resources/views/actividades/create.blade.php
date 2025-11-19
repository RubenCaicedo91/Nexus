
@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                Crear actividad para: <strong>{{ $nota->matricula->user->name ?? 'N/A' }} - {{ $nota->materia->nombre ?? 'N/A' }}</strong>
            </div>
            <div>
                <a href="{{ route('notas.actividades.index', $nota) }}" class="btn btn-sm btn-secondary">Volver</a>
            </div>
        </div>
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('notas.actividades.store', $nota) }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="nombre" class="form-control" value="{{ old('nombre') }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Valor (0-5)</label>
                    <input type="number" step="0.01" min="0" max="5" name="valor" class="form-control" value="{{ old('valor') }}" required>
                </div>
                <button class="btn btn-primary">Crear</button>
            </form>
        </div>
    </div>
</div>
@endsection
