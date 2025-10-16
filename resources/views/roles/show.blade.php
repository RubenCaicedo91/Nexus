@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Rol: {{ $rol->nombre }}</h1>

    <p><strong>Descripción:</strong> {{ $rol->descripcion }}</p>
    <p><strong>Permisos:</strong> {{ implode(', ', $rol->permisos ?? []) }}</p>

    <a href="{{ route('roles.edit', $rol->id) }}" class="btn btn-warning">Editar</a>
    <a href="{{ route('roles.index') }}" class="btn btn-secondary">Volver</a>
</div>
@endsection
