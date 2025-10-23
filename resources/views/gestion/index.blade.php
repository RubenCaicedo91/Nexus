@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-3">Gestión de Horarios</h1>
            <p class="text-muted mb-4">Acciones rápidas para gestionar cursos y horarios.</p>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="text-primary mb-3">
                        <i class="fas fa-plus fa-2x"></i>
                    </div>
                    <h5 class="card-title mb-3">Crear Curso</h5>
                    <a href="{{ route('gestion.crearCurso') }}" class="btn btn-primary w-100">
                        <i class="fas fa-plus-circle me-2"></i>Crear Curso
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="text-warning mb-3">
                        <i class="fas fa-edit fa-2x"></i>
                    </div>
                    <h5 class="card-title mb-3">Editar Curso</h5>
                    <a href="{{ route('gestion.editarCurso') }}" class="btn btn-outline-primary w-100">
                        <i class="fas fa-edit me-2"></i>Editar Curso
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sñ@@ñ h-100">
                <div class="card-body text-center">
                    <div class="text-info mb-3">
                        <i class="fas fa-clock fa-2x"></i>
                    </div>
                    <h5 class="card-title mb-3">Horarios</h5>
                    <a href="{{ route('gestion.horarios') }}" class="btn btn-info w-100">
                        <i class="fas fa-clock me-2"></i>Horarios
                    </a>
                </div>
            </div>
        </div>
        
    </div>
</div>
@endsection
