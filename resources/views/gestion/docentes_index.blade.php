@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="h4">Docentes</h1>
    <p class="text-muted">Selecciona un docente para asignarle cursos.</p>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Cursos asignados</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($docentes as $d)
                        <tr>
                            <td>{{ $d->name }}</td>
                            <td>{{ $d->email }}</td>
                            <td>
                                @if(method_exists($d, 'cursosAsignados') && $d->cursosAsignados->isNotEmpty())
                                    @foreach($d->cursosAsignados as $c)
                                        <span class="badge bg-secondary me-1">{{ $c->nombre }}</span>
                                    @endforeach
                                @else
                                    <span class="text-muted">(Sin cursos)</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('docentes.edit', $d->id) }}" class="btn btn-sm btn-primary">Asignar Cursos</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">No se encontraron docentes.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
