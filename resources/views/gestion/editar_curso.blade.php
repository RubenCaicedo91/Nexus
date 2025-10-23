@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="h4">Editar Curso</h1>
    <p class="text-muted">Página de ejemplo para editar cursos.</p>

    <div class="card">
        <div class="card-body">
            <form>
                <div class="mb-3">
                    <label class="form-label">Seleccione curso</label>
                    <select class="form-select">
                        <option>Matemáticas</option>
                        <option>Lengua</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Descripción</label>
                    <textarea class="form-control"></textarea>
                </div>
                <button class="btn btn-primary">Guardar cambios</button>
            </form>
        </div>
    </div>
</div>
@endsection
