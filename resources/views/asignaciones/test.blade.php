    @extends('layouts.app')

@section('content')
<div class="container">
    <h2>Prueba de Lista de Estudiantes</h2>
    
    <div class="alert alert-info">
        <strong>Debug:</strong> {{ $debug_info ?? 'Sin debug info' }}
    </div>
    
    <h3>Estudiantes encontrados:</h3>
    @if(isset($estudiantes) && $estudiantes->count() > 0)
        <ul class="list-group">
            @foreach($estudiantes as $estudiante)
                <li class="list-group-item">
                    <strong>ID:</strong> {{ $estudiante->id }} | 
                    <strong>Nombre:</strong> {{ $estudiante->name }} | 
                    <strong>Email:</strong> {{ $estudiante->email }}
                </li>
            @endforeach
        </ul>
    @else
        <div class="alert alert-warning">No se encontraron estudiantes</div>
    @endif
    
    <h3>Select HTML básico:</h3>
    <select class="form-select" style="width: 100%; max-width: 500px;">
        <option value="">-- Seleccionar estudiante --</option>
        @if(isset($estudiantes))
            @foreach($estudiantes as $estudiante)
                <option value="{{ $estudiante->id }}">
                    {{ $estudiante->name }} - {{ $estudiante->email }}
                </option>
            @endforeach
        @endif
    </select>
    
    <h3>Cursos encontrados:</h3>
    @if(isset($cursos) && $cursos->count() > 0)
        <ul class="list-group">
            @foreach($cursos as $curso)
                <li class="list-group-item">
                    <strong>ID:</strong> {{ $curso->id }} | 
                    <strong>Nombre:</strong> {{ $curso->nombre }}
                </li>
            @endforeach
        </ul>
    @else
        <div class="alert alert-warning">No se encontraron cursos</div>
    @endif
</div>
@endsection