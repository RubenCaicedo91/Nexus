@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm rounded overflow-hidden">

        <!-- Encabezado oscuro -->
        <div class="bg-dark text-white p-3 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-semibold mb-0">
                    <i class="bi bi-shield-lock-fill me-2 text-primary"></i> Roles institucionales
                </h2>
                <p class="small mb-0 text-light">
                    Administrar permisos y funciones del personal y estudiantes
                </p>
            </div>
            <div>
                @if(auth()->check() && auth()->user()->hasPermission('editar_roles'))
                    <a href="{{ route('roles.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i> Crear Rol
                    </a>
                @endif
            </div>
        </div>

        <!-- Separador -->
        <hr class="m-0">

        <!-- Contenido -->
        <div class="p-4 bg-light">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            @endif

            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-darkblue">
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
                                        @if(auth()->check() && auth()->user()->hasPermission('editar_roles'))
                                            <a href="{{ route('roles.edit', $rol->id) }}" class="btn btn-sm btn-warning">Editar</a>
                                            <form action="{{ route('roles.destroy', $rol->id) }}" method="POST" style="display:inline-block">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar rol?')">Eliminar</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center p-4 text-muted">
                                        <i class="bi bi-info-circle me-1"></i> No hay roles creados aún.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
