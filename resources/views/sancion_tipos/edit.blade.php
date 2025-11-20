@extends('layouts.app')
@section('content')
<h2>Editar Tipo de Sanción</h2>

@if($errors->any())
    <div class="alert alert-danger"><ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<form action="{{ route('gestion-disciplinaria.tipos.update', $tipo->id) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="mb-3">
        <label class="form-label">Nombre</label>
        <input name="nombre" class="form-control" value="{{ old('nombre', $tipo->nombre) }}" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Descripción</label>
        <textarea name="descripcion" class="form-control">{{ old('descripcion', $tipo->descripcion) }}</textarea>
    </div>
    <div class="mb-3">
        <label class="form-label">Severidad</label>
        <input name="severidad" class="form-control" value="{{ old('severidad', $tipo->severidad) }}">
    </div>
    <div class="mb-3">
        <label class="form-label">Categoría</label>
        <select name="categoria" class="form-control">
            <option value="" {{ $tipo->categoria === null ? 'selected' : '' }}>Normal</option>
            <option value="suspension" {{ $tipo->categoria=='suspension' ? 'selected' : '' }}>Suspensión</option>
            <option value="monetary" {{ $tipo->categoria=='monetary' ? 'selected' : '' }}>Monetaria</option>
            <option value="other" {{ $tipo->categoria=='other' ? 'selected' : '' }}>Otro</option>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Duración por defecto (días)</label>
        <input name="duracion_defecto_dias" class="form-control" value="{{ old('duracion_defecto_dias', $tipo->duracion_defecto_dias) }}">
    </div>
    <div class="mb-3 form-check">
        <input type="checkbox" name="activo" class="form-check-input" id="activo" {{ $tipo->activo ? 'checked' : '' }}>
        <label class="form-check-label" for="activo">Activo</label>
    </div>
    <button class="btn btn-success">Guardar</button>
    <a href="{{ route('gestion-disciplinaria.tipos.index') }}" class="btn btn-secondary">Cancelar</a>
</form>

@endsection
