@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="h4">Crear Curso</h1>
    <p class="text-muted">Formulario de ejemplo para crear un curso.</p>

    <div class="card">
        <div class="card-body">
            <form>
                <div class="mb-3">
                    <label class="form-label">Nombre del curso</label>
                    <input class="form-control" />
                </div>
                <div class="mb-3">
                    <label class="form-label">Descripción</label>
                    <textarea class="form-control"></textarea>
                </div>
                <button class="btn btn-primary">Crear</button>
            </form>
        </div>
    </div>
</div>
@endsection
