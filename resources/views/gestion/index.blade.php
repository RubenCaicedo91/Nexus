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
                @php
                    $u = auth()->user();
                    $roleNombre = optional($u->role)->nombre ?? '';
                    $roleNorm = strtr(mb_strtolower($roleNombre), ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u']);
                    $canManageAcademica = false;
                    if ($u && method_exists($u, 'hasPermission') && $u->hasPermission('gestionar_academica')) {
                        $canManageAcademica = true;
                    }
                    if ($u && ($u->roles_id == 1 || stripos($roleNombre, 'admin') !== false || stripos($roleNombre, 'administrador') !== false || stripos($roleNombre, 'rector') !== false)) {
                        $canManageAcademica = true;
                    }
                    // Permitir Coordinador Académico específicamente
                    if (mb_stripos($roleNorm, 'coordinador academ') !== false || mb_stripos($roleNorm, 'cordinador academ') !== false) {
                        $canManageAcademica = true;
                    }

                    // Permisos/roles específicos para acceder al módulo de Asistencias
                    $canAccessAsistencias = false;
                    if ($canManageAcademica) {
                        $canAccessAsistencias = true;
                    }
                    if ($u && method_exists($u, 'hasPermission') && ($u->hasPermission('ver_asistencias') || $u->hasPermission('registrar_asistencia'))) {
                        $canAccessAsistencias = true;
                    }
                    // Permitir al rol Docente
                    if ($u && optional($u->role)->nombre && stripos(optional($u->role)->nombre, 'docente') !== false) {
                        $canAccessAsistencias = true;
                    }
                    // Permitir a Docentes ver el panel de Horarios (solo vista, no gestión completa)
                    $canViewHorarios = $canManageAcademica;
                    if ($u && optional($u->role)->nombre && stripos(optional($u->role)->nombre, 'docente') !== false) {
                        $canViewHorarios = true;
                    }
                @endphp
                @if($canManageAcademica)
                    <a href="{{ route('cursos.panel') }}" class="btn btn-outline-primary w-100">
                        <i class="fas fa-edit me-2"></i>Ver y Crear Cursos
                    </a>
                @else
                    <button class="btn btn-outline-secondary w-100" disabled title="No tienes permiso para gestionar cursos">Ver y Crear Cursos (sin permiso)</button>
                @endif
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
                @if($canViewHorarios)
                    <a href="{{ route('gestion.horarios') }}" class="btn btn-info w-100">
                        <i class="fas fa-clock me-2"></i>Ver Horarios
                    </a>
                @else
                    <button class="btn btn-outline-secondary w-100" disabled title="No tienes permiso para gestionar horarios">Gestionar Horarios (sin permiso)</button>
                @endif
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
    
    {{-- Tarjeta: Gestionar Materias (nuevo menú independiente) --}}
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="text-success mb-3">
                    <i class="fas fa-book-open fa-2x"></i>
                </div>
                <h5 class="card-title mb-3">Gestionar Materias</h5>
                @if((
                    auth()->user()->hasPermission('asignar_docentes') ||
                    (optional(auth()->user()->role)->nombre && (
                        stripos(optional(auth()->user()->role)->nombre, 'admin') !== false ||
                        stripos(optional(auth()->user()->role)->nombre, 'administrador') !== false
                    )) || auth()->user()->roles_id == 1
                ))
                    <a href="{{ route('materias.index') }}" class="btn btn-outline-success w-100">
                        <i class="fas fa-book-open me-2"></i>Gestionar Materias
                    </a>
                @else
                    <button class="btn btn-outline-secondary w-100" disabled>Materias (sin permiso)</button>
                @endif
            </div>
        </div>
    </div>

    {{-- Tarjeta: Asignaciones de Estudiantes --}}
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="text-purple mb-3" style="color: #6f42c1;">
                    <i class="fas fa-users fa-2x"></i>
                </div>
                <h5 class="card-title mb-3">Asignaciones de Estudiantes</h5>
                @if((
                    auth()->user()->hasPermission('asignar_docentes') ||
                    (optional(auth()->user()->role)->nombre && (
                        stripos(optional(auth()->user()->role)->nombre, 'admin') !== false ||
                        stripos(optional(auth()->user()->role)->nombre, 'administrador') !== false
                    )) || auth()->user()->roles_id == 1
                ))
                    <a href="{{ route('asignaciones.index') }}" class="btn w-100" style="color: #6f42c1; border-color: #6f42c1;">
                        <i class="fas fa-user-check me-2"></i>Gestionar Asignaciones
                    </a>
                @else
                    <button class="btn btn-outline-secondary w-100" disabled>Asignaciones (sin permiso)</button>
                @endif
            </div>
        </div>
    </div>
    
    {{-- Tarjeta: Asistencias --}}
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="text-teal mb-3" style="color: #0d6efd;">
                    <i class="fas fa-calendar-check fa-2x"></i>
                </div>
                <h5 class="card-title mb-3">Asistencias</h5>
                @if($canAccessAsistencias)
                    <a href="{{ route('asistencias.index') }}" class="btn btn-outline-primary w-100">
                        <i class="fas fa-calendar-check me-2"></i>Ir a Asistencias
                    </a>
                @else
                    <button class="btn btn-outline-secondary w-100" disabled>Asistencias (sin permiso)</button>
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

@section('scripts')
<!-- Datos de docentes como JSON embebido fuera del bloque JS para evitar mixing Blade/JS -->
<script id="docentes-data" type="application/json">@json($docentes)</script>
<script>
document.addEventListener('DOMContentLoaded', function(){
        // CSRF token from meta
        const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';
    // Docentes data (leída desde el tag JSON embebido)
    const docentesEl = document.getElementById('docentes-data');
    const docentesOptions = docentesEl ? JSON.parse(docentesEl.textContent || '[]') : [];



});
</script>
@endsection
