{{-- index.blade.php --}}
@extends('layouts.app')
@section('content')
<h2>Registrar Sanción</h2>

@if($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('gestion-disciplinaria.store') }}" method="POST">
    @csrf
    <div class="mb-3">
        <label for="usuario_id" class="form-label">ID Usuario</label>
        <input type="number" name="usuario_id" class="form-control" required>
    </div>
    <div class="mb-3">
        <label for="descripcion" class="form-label">Descripción</label>
        <input type="text" name="descripcion" class="form-control" required>
    </div>
    <div class="mb-3">
        <label for="tipo" class="form-label">Tipo</label>
        <input type="text" name="tipo" class="form-control" required>
    </div>
    <div class="mb-3">
        <label for="fecha" class="form-label">Fecha</label>
        <input type="date" name="fecha" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-success">Registrar</button>
    <a href="{{ route('gestion-disciplinaria.index') }}" class="btn btn-secondary">Cancelar</a>

    <a href="{{ route('gestion-disciplinaria.index') }}" class="btn btn-secondary">
    Volver
</a>
</form>

@endsection