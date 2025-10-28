@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="h4">Usuarios</h1>
    <p class="text-muted">Lista de usuarios del sistema. Aquí puedes crear, editar, asignar roles y eliminar usuarios.</p>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('usuarios.create') }}" class="btn btn-primary">➕ Crear usuario</a>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-hover table-bordered">
                <thead class="table-light">
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
                                <a href="{{ route('usuarios.edit', $user->id) }}" class="btn btn-sm btn-warning">✏️ Editar</a>
                                <form action="{{ route('usuarios.destroy', $user->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar usuario?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger">🗑️ Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">No hay usuarios registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="mt-3">{{ $users->links() }}</div>
        </div>
    </div>
</div>
@endsection
