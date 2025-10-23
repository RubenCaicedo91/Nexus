@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2>Editar Matrícula</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('matriculas.index') }}"> Volver</a>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Whoops!</strong> Hubo algunos problemas con tu entrada.<br><br>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('matriculas.update', $matricula->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-xs-12 col-sm-12 col-md-12">
                <div class="form-group">
                    <strong>Estudiante:</strong>
                    <select name="user_id" class="form-control">
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}" @if($student->id == $matricula->user_id) selected @endif>{{ $student->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-xs-12 col-sm-12 col-md-12">
                <div class="form-group">
                    <strong>Fecha de Matrícula:</strong>
                    <input type="date" name="fecha_matricula" value="{{ $matricula->fecha_matricula }}" class="form-control" placeholder="Fecha de Matrícula">
                </div>
            </div>
            <div class="col-xs-12 col-sm-12 col-md-12">
                <div class="form-group">
                    <strong>Estado:</strong>
                    <select name="estado" class="form-control">
                        <option value="activo" @if($matricula->estado == 'activo') selected @endif>Activo</option>
                        <option value="inactivo" @if($matricula->estado == 'inactivo') selected @endif>Inactivo</option>
                        <option value="completado" @if($matricula->estado == 'completado') selected @endif>Completado</option>
                    </select>
                </div>
            </div>
            <div class="col-xs-12 col-sm-12 col-md-12 text-center">
                <button type="submit" class="btn btn-primary">Enviar</button>
            </div>
        </div>
    </form>
</div>
@endsection
