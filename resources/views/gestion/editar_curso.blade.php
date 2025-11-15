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
    
    {{-- Sección: Docentes y materias asignadas en este curso --}}
    <div class="card mt-4">
        <div class="card-body">
            <h5 class="card-title">👩‍🏫 Docentes y materias asignadas</h5>
            <p class="text-muted">Aquí ves los docentes relacionados con el curso y las materias que tienen asignadas en este curso.</p>

            @if(isset($docentes) && $docentes->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Docente</th>
                                <th>Materias asignadas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($docentes as $docente)
                                <tr>
                                    <td>{{ $docente->name ?? $docente->first_name . ' ' . $docente->first_last }}</td>
                                    <td>
                                        @php
                                            $lista = $materias->where('docente_id', $docente->id)->pluck('nombre')->all();
                                        @endphp
                                        @if(!empty($lista))
                                            <ul class="mb-0">
                                                @foreach($lista as $m)
                                                    <li>{{ $m }}</li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <span class="text-muted">(Sin materias asignadas)</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert alert-info">No hay docentes vinculados a este curso.</div>
            @endif

            {{-- Mostrar materias sin docente asignado --}}
            @php
                $sinDocente = $materias->whereNull('docente_id')->pluck('nombre')->all();
            @endphp
            @if(!empty($sinDocente))
                <div class="mt-3">
                    <h6>Materias sin docente asignado</h6>
                    <ul>
                        @foreach($sinDocente as $m)
                            <li>{{ $m }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
