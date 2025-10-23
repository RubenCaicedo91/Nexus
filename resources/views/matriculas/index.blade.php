@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">Gestión de Matrículas</h1>
                <p class="text-muted mb-0">Acciones rápidas para gestionar matrículas.</p>
            </div>
            <a class="btn btn-success" href="{{ route('matriculas.create') }}">
                <i class="fas fa-plus me-2"></i>Crear Nueva Matrícula
            </a>
        </div>
    </div>
    <div class="my-3"></div>
    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p class="mb-0">{{ $message }}</p>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>No</th>
                        <th>Estudiante</th>
                        <th>Fecha de Matrícula</th>
                        <th>Estado</th>
                        <th width="280px">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @php($i = 0)
                    @foreach ($matriculas as $matricula)
                    <tr>
                        <td>{{ ++$i }}</td>
                        <td>{{ $matricula->user->name }}</td>
                        <td>{{ $matricula->fecha_matricula }}</td>
                        <td>{{ $matricula->estado }}</td>
                        <td>
                            <form action="{{ route('matriculas.destroy',$matricula->id) }}" method="POST" class="d-inline">
                                <a class="btn btn-info btn-sm" href="{{ route('matriculas.show',$matricula->id) }}">
                                    <i class="fas fa-eye"></i> Mostrar
                                </a>
                                <a class="btn btn-primary btn-sm" href="{{ route('matriculas.edit',$matricula->id) }}">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">
                                    <i class="fas fa-trash"></i> Eliminar
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
