@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>Gestión de Matrículas</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-success" href="{{ route('matriculas.create') }}"> Crear Nueva Matrícula</a>
            </div>
        </div>
    </div>

    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <p>{{ $message }}</p>
        </div>
    @endif

    @php($i = 0)
    <table class="table table-bordered">
        <tr>
            <th>No</th>
            <th>Estudiante</th>
            <th>Fecha de Matrícula</th>
            <th>Estado</th>
            <th width="280px">Acción</th>
        </tr>
        @foreach ($matriculas as $matricula)
        <tr>
            <td>{{ ++$i }}</td>
            <td>{{ $matricula->user->name }}</td>
            <td>{{ $matricula->fecha_matricula }}</td>
            <td>{{ $matricula->estado }}</td>
            <td>
                <form action="{{ route('matriculas.destroy',$matricula->id) }}" method="POST">
                    <a class="btn btn-info" href="{{ route('matriculas.show',$matricula->id) }}">Mostrar</a>
                    <a class="btn btn-primary" href="{{ route('matriculas.edit',$matricula->id) }}">Editar</a>
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </form>
            </td>
        </tr>
        @endforeach
    </table>
</div>
@endsection
