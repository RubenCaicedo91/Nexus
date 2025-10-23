@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h2 class="mb-0">Mostrar Matrícula</h2>
            <a class="btn btn-primary" href="{{ route('matriculas.index') }}">
                <i class="fas fa-arrow-left me-2"></i>Volver
            </a>
        </div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="mb-3">
                <strong>Estudiante:</strong>
                <div>{{ $matricula->user->name }}</div>
            </div>
            <div class="mb-3">
                <strong>Fecha de Matrícula:</strong>
                <div>{{ $matricula->fecha_matricula }}</div>
            </div>
            <div class="mb-3">
                <strong>Estado:</strong>
                <div>{{ $matricula->estado }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
