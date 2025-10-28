@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="h4">Crear Curso</h1>
    <p class="text-muted">Define el nivel y grupo del curso, junto con su descripción.</p>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <form action="{{ route('cursos.guardar') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Nivel</label>
                    <select name="nivel" class="form-select" required>
                        <option value="">Selecciona un nivel</option>
                        <option>Primero</option>
                        <option>Segundo</option>
                        <option>Tercero</option>
                        <option>Cuarto</option>
                        <option>Quinto</option>
                        <option>Sexto</option>
                        <option>Séptimo</option>
                        <option>Octavo</option>
                        <option>Noveno</option>
                        <option>Décimo</option>
                        <option>Once</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Grupo</label>
                    <input type="text" name="grupo" class="form-control" placeholder="Ej: 01-1, A, B" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Descripción</label>
                    <textarea name="descripcion" class="form-control" rows="3" placeholder="Opcional"></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Crear curso</button>
            </form>
        </div>
    </div>
</div>
@endsection
