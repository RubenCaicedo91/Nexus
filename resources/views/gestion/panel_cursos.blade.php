@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="h4">📘 Gestión de Cursos</h1>
    <p class="text-muted">Desde aquí puedes crear, editar, eliminar y visualizar los cursos registrados.</p>

    {{-- Mensaje de éxito --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Formulario para crear curso --}}
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">➕ Crear nuevo curso</h5>
            <form action="{{ route('guardarCurso') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Nivel</label>
                        <select name="nivel" class="form-select" required>
                            <option value="">Selecciona un nivel</option>
                            <option>Primero</option>
                            <option>Segundo</option>
                            <option>Tercero</option>
                            <option>Cuarto</option>
                            <option>Quinto</option>
                            <option>Sexto</option>
                            <option>Séptimo</option>
                            <option>Octavo</option>
                            <option>Noveno</option>
                            <option>Décimo</option>
                            <option>Once</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Grupo</label>
                        <input type="text" name="grupo" class="form-control" placeholder="Ej: 01-1, A, B" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Descripción</label>
                        <input type="text" name="descripcion" class="form-control" placeholder="Opcional">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Guardar curso</button>
            </form>
        </div>
    </div>

    {{-- Tabla de cursos registrados --}}
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">📚 Cursos registrados</h5>
            @if(!empty($errorMessage))
                <div class="alert alert-warning">{{ $errorMessage }}</div>
            @endif
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cursos as $curso)
                        <tr>
                            <td>{{ $curso->nombre }}</td>
                            <td>{{ $curso->descripcion }}</td>
                            <td>
                                <a href="{{ route('editarCurso', $curso->id) }}" class="btn btn-sm btn-warning">✏️ Editar</a>

                                <a href="{{ route('cursos.materias', $curso->id) }}" class="btn btn-sm btn-info">👩‍🏫 Asignar docentes</a>

                                <form action="{{ route('eliminarCurso', $curso->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Estás segura de eliminar este curso?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">🗑️ Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted">No hay cursos registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
