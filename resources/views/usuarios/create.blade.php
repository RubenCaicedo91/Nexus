@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="h4">Crear Usuario</h1>

    <div class="card">
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('usuarios.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Contraseña</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirmar contraseña</label>
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Rol</label>
                    <select name="roles_id" class="form-select">
                        <option value="">-- Ninguno --</option>
                        @foreach($roles as $rol)
                            <option value="{{ $rol->id }}">{{ $rol->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Crear usuario</button>
                <a href="{{ route('usuarios.index') }}" class="btn btn-secondary ms-2">Cancelar</a>
            </form>
        </div>
    </div>
</div>
@endsection
