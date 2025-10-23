@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pull-left">
                <h2> Mostrar Matrícula</h2>
            </div>
            <div class="pull-right">
                <a class="btn btn-primary" href="{{ route('matriculas.index') }}"> Volver</a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <strong>Estudiante:</strong>
                {{ $matricula->user->name }}
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <strong>Fecha de Matrícula:</strong>
                {{ $matricula->fecha_matricula }}
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-12">
            <div class="form-group">
                <strong>Estado:</strong>
                {{ $matricula->estado }}
            </div>
        </div>
    </div>
</div>
@endsection
