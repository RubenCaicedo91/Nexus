@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-0">Roles institucionales</h1>
            <small class="text-muted">Administrar permisos y funciones del personal y estudiantes</small>
        </div>
        <div>
            <a href="{{ route('roles.create') }}" class="btn btn-primary">Crear Rol</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:60px">ID</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Permisos</th>
                        <th style="width:220px">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($roles as $rol)
                    <tr>
                        <td>{{ $rol->id }}</td>
                        <td>{{ ucfirst($rol->nombre) }}</td>
                        <td>{{ $rol->descripcion }}</td>
                        <td>{{ implode(', ', $rol->permisos ?? []) }}</td>
                        <td>
                            <a href="{{ route('roles.show', $rol->id) }}" class="btn btn-sm btn-info">Ver</a>
                            <a href="{{ route('roles.edit', $rol->id) }}" class="btn btn-sm btn-warning">Editar</a>
                            <form action="{{ route('roles.destroy', $rol->id) }}" method="POST" style="display:inline-block">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar rol?')">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center p-4">No hay roles creados aún.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
