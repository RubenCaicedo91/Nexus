@extends('layouts.app')
@section('content')
<h2>Crear Tipo de Sanción</h2>

@if($errors->any())
    <div class="alert alert-danger"><ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif

<form action="{{ route('gestion-disciplinaria.tipos.store') }}" method="POST">
    @csrf
    <div class="mb-3">
        <label class="form-label">Nombre</label>
        <input name="nombre" class="form-control" value="{{ old('nombre') }}" required>
    </div>
    <div class="mb-3">
        <label class="form-label">Descripción</label>
        <textarea name="descripcion" class="form-control">{{ old('descripcion') }}</textarea>
    </div>
    <div class="mb-3">
        <label class="form-label">Severidad</label>
        <input name="severidad" class="form-control" value="{{ old('severidad') }}">
    </div>
    <div class="mb-3">
        <label class="form-label">Categoría</label>
        <select name="categoria" class="form-control">
            <option value="">Normal</option>
            <option value="suspension" {{ old('categoria')=='suspension' ? 'selected' : '' }}>Suspensión</option>
            <option value="monetary" {{ old('categoria')=='monetary' ? 'selected' : '' }}>Monetaria</option>
            <option value="other" {{ old('categoria')=='other' ? 'selected' : '' }}>Otro</option>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Duración por defecto (días)</label>
        <input name="duracion_defecto_dias" class="form-control" value="{{ old('duracion_defecto_dias') }}">
    </div>
    <div class="mb-3 form-check">
        <input type="checkbox" name="activo" class="form-check-input" id="activo" checked>
        <label class="form-check-label" for="activo">Activo</label>
    </div>
    <button class="btn btn-success">Crear</button>
    <a href="{{ route('gestion-disciplinaria.tipos.index') }}" class="btn btn-secondary">Cancelar</a>
</form>

@endsection
