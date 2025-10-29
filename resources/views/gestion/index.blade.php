@extends('layouts.app')

@section('content')
<div class="container-fluid py-5 d-flex justify-content-center">
  <div class="card shadow-lg border-0 text-center text-white"
       style="background: linear-gradient(135deg, #210e64ff, #220b4bff);
              border-radius: 20px;
              width: 95%;
              max-width: 1300px;
              padding: 25px 80px;">
    <div class="card-body p-0">
      <h1 class="fw-bold mb-2" style="font-size: 2.2rem;">🎓 Gestión Académica</h1>
      <p class="text-light mb-0" style="font-size: 1.1rem;">
        Accede rápidamente a los módulos de cursos, horarios y matrículas.
      </p>
    </div>
  </div>
</div>

<div class="row px-4">
    {{-- Tarjeta: Cursos --}}
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="text-primary mb-3">
                    <i class="fas fa-book fa-2x"></i>
                </div>
                <h5 class="card-title mb-3">Gestionar Cursos</h5>
                <a href="{{ route('cursos.panel') }}" class="btn btn-outline-primary w-100">
                    <i class="fas fa-edit me-2"></i>Ver y Crear Cursos
                </a>
            </div>
        </div>
    </div>

    {{-- Tarjeta: Horarios --}}
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="text-info mb-3">
                    <i class="fas fa-clock fa-2x"></i>
                </div>
                <h5 class="card-title mb-3">Horarios</h5>
                <a href="{{ route('gestion.horarios') }}" class="btn btn-info w-100">
                    <i class="fas fa-clock me-2"></i>Gestionar Horarios
                </a>
            </div>
        </div>
    </div>

    {{-- Tarjeta: Asignar Docentes (botón al mismo nivel) --}}
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="text-warning mb-3">
                    <i class="fas fa-chalkboard-teacher fa-2x"></i>
                </div>
                <h5 class="card-title mb-3">Asignar Docentes</h5>
                @if(auth()->check() && (
                    auth()->user()->hasPermission('asignar_docentes') ||
                    (optional(auth()->user()->role)->nombre && (
                        stripos(optional(auth()->user()->role)->nombre, 'admin') !== false ||
                        stripos(optional(auth()->user()->role)->nombre, 'administrador') !== false
                    )) || auth()->user()->roles_id == 1
                ))
                        <a href="{{ route('docentes.index') }}" class="btn btn-outline-warning w-100">
                        <i class="fas fa-chalkboard-teacher me-2"></i>Ir a Asignar Docentes
                    </a>
                @else
                    <button class="btn btn-outline-secondary w-100" disabled>Asignar Docentes (sin permiso)</button>
                @endif
            </div>
        </div>
    </div>
        </div> <!-- .row -->

        <!-- Modal: Asignar Docentes -->
        <div class="modal fade" id="asignarDocentesModal" tabindex="-1" aria-labelledby="asignarDocentesModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="asignarDocentesModalLabel">Asignar cursos a docente</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <form action="{{ route('docentes.asignar') }}" method="POST">
                        @csrf
                        <div class="modal-body">
                                <div class="mb-3">
                                        <label class="form-label">Docente</label>
                                        <select name="docente_id" class="form-select" required>
                                                <option value="">-- Selecciona un docente --</option>
                                                @foreach($docentes as $d)
                                                        <option value="{{ $d->id }}">{{ $d->name }} ({{ $d->email }})</option>
                                                @endforeach
                                        </select>
                                </div>
                                <div class="mb-3">
                                        <label class="form-label">Cursos (mantén Ctrl/Cmd para seleccionar varios)</label>
                                        <select name="cursos[]" class="form-select" multiple size="8">
                                                @foreach($cursos as $curso)
                                                        <option value="{{ $curso->id }}">{{ $curso->nombre }}</option>
                                                @endforeach
                                        </select>
                                </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar asignaciones</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

@endsection
