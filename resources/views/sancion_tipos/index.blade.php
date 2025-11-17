@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Tipos de Sanción</h2>
    <a href="{{ route('gestion-disciplinaria.tipos.create') }}" class="btn btn-primary">Crear tipo</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<table class="table table-striped">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Severidad</th>
            <th>Activo</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        @foreach($tipos as $t)
            <tr>
                <td>{{ $t->id }}</td>
                <td>{{ $t->nombre }}</td>
                <td>{{ $t->severidad }}</td>
                <td>{{ $t->activo ? 'Sí' : 'No' }}</td>
                <td>
                    <a href="{{ route('gestion-disciplinaria.tipos.edit', $t->id) }}" class="btn btn-sm btn-secondary">Editar</a>
                    <form action="{{ route('gestion-disciplinaria.tipos.destroy', $t->id) }}" method="POST" style="display:inline-block" onsubmit="return confirm('Eliminar tipo?');">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-danger">Eliminar</button>
                    </form>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

@endsection
