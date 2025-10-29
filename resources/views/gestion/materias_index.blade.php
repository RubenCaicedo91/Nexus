
@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="h4">📗 Materias del curso: {{ $curso->nombre }}</h1>
    <p class="text-muted">Aquí puedes crear materias y asignar docentes a cada materia.</p>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">➕ Crear nueva materia</h5>
            <form action="{{ route('materias.store', $curso->id) }}" method="POST">
                @csrf
                <div class="row g-2">
                    <div class="col-md-4">
                        <input type="text" name="nombre" class="form-control" placeholder="Nombre de la materia" required>
                    </div>
                    <div class="col-md-6">
                        <input type="text" name="descripcion" class="form-control" placeholder="Descripción (opcional)">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100" type="submit">Crear</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title">📚 Materias</h5>
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Docente asignado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($materias as $m)
                        <tr>
                            <td>{{ $m->nombre }}</td>
                            <td>{{ $m->descripcion }}</td>
                            <td>{{ optional($m->docente)->name ?? '— Sin asignar —' }}</td>
                            <td>
                                <a href="{{ route('materias.editar', $m->id) }}" class="btn btn-sm btn-secondary">Asignar / Editar</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">No hay materias creadas para este curso.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <a href="{{ route('cursos.panel') }}" class="btn btn-link">← Volver a cursos</a>
        </div>
    </div>
</div>
@endsection
