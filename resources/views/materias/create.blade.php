@extends('layouts.app')

@section('title', 'Crear Materia')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-plus"></i> Crear Nueva Materia
                    </h3>
                </div>

                <form action="{{ route('materias.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        @if($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <h6><i class="fas fa-exclamation-circle"></i> Por favor, corrige los siguientes errores:</h6>
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nombre" class="required">
                                        <i class="fas fa-book"></i> Nombre de la Materia
                                    </label>
                                    <input type="text" 
                                           class="form-control @error('nombre') is-invalid @enderror" 
                                           id="nombre" 
                                           name="nombre" 
                                           value="{{ old('nombre') }}" 
                                           placeholder="Ej: Matemáticas, Español, Ciencias..."
                                           required>
                                    @error('nombre')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="curso_id" class="required">
                                        <i class="fas fa-graduation-cap"></i> Curso
                                    </label>
                                    <select class="form-control @error('curso_id') is-invalid @enderror" 
                                            id="curso_id" 
                                            name="curso_id" 
                                            required>
                                        <option value="">Seleccionar curso...</option>
                                        @foreach($cursos as $curso)
                                            <option value="{{ $curso->id }}" 
                                                    {{ old('curso_id') == $curso->id ? 'selected' : '' }}>
                                                {{ $curso->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('curso_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="docente_id">
                                        <i class="fas fa-chalkboard-teacher"></i> Docente (Opcional)
                                    </label>
                                    <select class="form-control @error('docente_id') is-invalid @enderror" 
                                            id="docente_id" 
                                            name="docente_id">
                                        <option value="">Sin docente asignado</option>
                                        @foreach($docentes as $docente)
                                            <option value="{{ $docente->id }}" 
                                                    {{ old('docente_id') == $docente->id ? 'selected' : '' }}>
                                                {{ $docente->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('docente_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        Puedes asignar un docente más tarde si no está disponible ahora.
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="descripcion">
                                <i class="fas fa-align-left"></i> Descripción
                            </label>
                            <textarea class="form-control @error('descripcion') is-invalid @enderror" 
                                      id="descripcion" 
                                      name="descripcion" 
                                      rows="4" 
                                      placeholder="Descripción detallada de la materia, objetivos, contenidos, etc.">{{ old('descripcion') }}</textarea>
                            @error('descripcion')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Máximo 1000 caracteres. Describe brevemente la materia y sus objetivos.
                            </small>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <div class="col-md-6">
                                <a href="{{ route('materias.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Volver al Listado
                                </a>
                            </div>
                            <div class="col-md-6 text-right">
                                <button type="reset" class="btn btn-warning">
                                    <i class="fas fa-undo"></i> Limpiar
                                </button>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i> Crear Materia
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
.required::after {
    content: " *";
    color: red;
}

.form-group label {
    font-weight: 600;
    color: #495057;
}

.card-header {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
}

.btn-group .btn {
    margin-right: 5px;
}
</style>
@endsection