@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3">Editar Rol</h1>
        <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">Volver a la lista</a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('roles.update', $rol->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label fw-bold">Nombre del rol</label>
                    <input type="text" name="nombre" class="form-control" value="{{ old('nombre', $rol->nombre) }}" required>
                    <div class="form-text">Ej: rector, docente, coordinador_academico</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Descripción</label>
                    <textarea name="descripcion" class="form-control" rows="4">{{ old('descripcion', $rol->descripcion) }}</textarea>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card p-3">
                    <h6 class="fw-bold">Permisos asignados</h6>
                    <div class="mb-2 text-muted">Marca los permisos que correspondan a este rol.</div>

                    @php
                        $allPermissions = [
                            'ver_estudiantes', 'registrar_notas', 'editar_notas', 'ver_matriculas',
                            'registrar_asistencia', 'ver_asistencias', 'gestionar_usuarios', 'asignar_roles'
                        ];
                        $assigned = $rol->permisos ?? [];
                    @endphp

                    @foreach($allPermissions as $perm)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="permisos[]" value="{{ $perm }}" id="perm_{{ $perm }}"
                                {{ in_array($perm, old('permisos', $assigned)) ? 'checked' : '' }}>
                            <label class="form-check-label" for="perm_{{ $perm }}">{{ ucwords(str_replace('_', ' ', $perm)) }}</label>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="mt-3">
            <button class="btn btn-primary">Guardar cambios</button>
            <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary ms-2">Cancelar</a>
        </div>
    </form>
</div>
@endsection
