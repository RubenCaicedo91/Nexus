@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm rounded overflow-hidden">

        <!-- Encabezado oscuro -->
        <div class="bg-dark text-white p-3 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-semibold mb-0">
                    <i class="bi bi-people-fill me-2 text-primary"></i> Usuarios
                </h2>
                <p class="small mb-0 text-light">
                    Lista de usuarios del sistema. Aquí puedes crear, editar, asignar roles y eliminar usuarios.
                </p>
            </div>
            <a href="{{ route('usuarios.create') }}" class="btn btn-primary">
                ➕ Crear usuario
            </a>
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
                <div class="card-body">
                    <form method="GET" action="{{ route('usuarios.index') }}" class="row g-2 mb-3">
                        <div class="col-md-4">
                            <input type="text" name="name" value="{{ request('name') }}" class="form-control" placeholder="Buscar por nombre">
                        </div>
                        <div class="col-md-4">
                            <input type="text" name="email" value="{{ request('email') }}" class="form-control" placeholder="Buscar por email">
                        </div>
                        <div class="col-md-3">
                            <select name="role" class="form-select">
                                <option value="">— Todos los roles —</option>
                                @foreach($roles as $r)
                                    <option value="{{ $r->id }}" {{ (string) request('role') === (string) $r->id ? 'selected' : '' }}>{{ $r->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-1 d-grid">
                            <button class="btn btn-primary">Filtrar</button>
                        </div>
                        <div class="col-12 mt-2">
                            <a href="{{ route('usuarios.index') }}" class="btn btn-link">Limpiar filtros</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 align-middle">
                            <thead class="table-secondary">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Rol</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $user)
                                    <tr>
                                        <td>{{ $user->name }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>{{ optional($user->role)->nombre ?? ($user->roles_id ?? '—') }}</td>
                                        <td>
                                            <a href="{{ route('usuarios.edit', $user->id) }}" class="btn btn-sm btn-warning">
                                                ✏️ Editar
                                            </a>
                                            <form action="{{ route('usuarios.destroy', $user->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar usuario?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-danger">
                                                    🗑️ Eliminar
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">
                                            <i class="bi bi-info-circle me-1"></i> No hay usuarios registrados.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 px-3">
                        {{ $users->appends(request()->except('page'))->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
