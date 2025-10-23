@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="h3">Gestión Académica</h1>
    <p class="text-muted">Acciones rápidas para gestionar cursos y horarios</p>

    <div class="row mt-4">
        <div class="col-md-4 mb-3">
            <a href="{{ route('gestion.crearCurso') }}" class="btn btn-primary w-100 py-3">
                <i class="fas fa-plus me-2"></i>Crear Curso
            </a>
        </div>
        <div class="col-md-4 mb-3">
            <a href="{{ route('gestion.editarCurso') }}" class="btn btn-outline-primary w-100 py-3">
                <i class="fas fa-edit me-2"></i>Editar Curso
            </a>
        </div>
        <div class="col-md-4 mb-3">
            <a href="{{ route('gestion.horarios') }}" class="btn btn-info w-100 py-3">
                <i class="fas fa-clock me-2"></i>Horarios
            </a>
        </div>
    </div>
</div>
@endsection
