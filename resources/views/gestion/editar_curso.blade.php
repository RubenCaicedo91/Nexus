@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="h4">Editar Curso</h1>

    <div class="card">
        <div class="card-body">
            {{-- Mostrar errores de validación --}}
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('actualizarCurso', $curso->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">Nombre del curso</label>
                    <input type="text" name="nombre" class="form-control" value="{{ old('nombre', $curso->nombre) }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Descripción</label>
                    <textarea name="descripcion" class="form-control">{{ old('descripcion', $curso->descripcion) }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary">Guardar cambios</button>
                <a href="{{ route('cursos.panel') }}" class="btn btn-secondary ms-2">Cancelar</a>
            </form>
        </div>
    </div>
</div>
@endsection
