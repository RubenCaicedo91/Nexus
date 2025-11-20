@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm rounded overflow-hidden">

        <!-- Encabezado oscuro -->
        <div class="bg-dark text-white p-3 d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold mb-0">
                <i class="bi bi-journal-text me-2 text-warning"></i> Listado de Notas
            </h2>
        </div>

        <!-- Separador -->
        <hr class="m-0">

        <!-- Contenido -->
        <div class="p-4 bg-light">
            <!-- Filtros -->
            <form method="GET" class="row g-3 mb-4">
                @if(isset($acudienteStudents) && $acudienteStudents->count() > 0)
                <div class="col-md-3">
                    <label class="form-label fw-bold">Estudiante</label>
                    <select name="estudiante_id" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Todos --</option>
                        @foreach($acudienteStudents as $a)
                            <option value="{{ $a->id }}" {{ (isset($selectedEstudianteId) && $selectedEstudianteId == $a->id) ? 'selected' : '' }}>{{ $a->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="col-md-3">
                    <label class="form-label fw-bold">Curso</label>
                    <select name="curso_id" class="form-select">
                        <option value="">-- Seleccionar --</option>
                        @php
                            $selCurso = request('curso_id') ?? (isset($lastSearch) ? $lastSearch['curso_id'] : null);
                        @endphp
                        @foreach($cursos as $c)
                            <option value="{{ $c->id }}" {{ $selCurso == $c->id ? 'selected' : '' }}>{{ $c->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Materia</label>
                    <select name="materia_id" class="form-select">
                        <option value="">-- Seleccionar --</option>
                        @php
                            $selMateria = request('materia_id') ?? (isset($lastSearch) ? $lastSearch['materia_id'] : null);
                        @endphp
                        @foreach($materias as $m)
                            <option value="{{ $m->id }}" {{ $selMateria == $m->id ? 'selected' : '' }}>{{ $m->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-secondary w-100">
                        <i class="bi bi-funnel-fill me-1"></i> Filtrar
                    </button>
                </div>
            </form>

            @if(isset($acudienteStudents) && isset($estudianteCursoMap))
                <!-- Campo oculto con el mapa estudiante->curso (JSON) -->
                <input type="hidden" id="estudianteCursoMapData" value='@json($estudianteCursoMap)'>
                <script>
                    (function(){
                        var map = {};
                        var mapEl = document.getElementById('estudianteCursoMapData');
                        if (mapEl) {
                            try {
                                map = JSON.parse(mapEl.value || '{}');
                            } catch (e) {
                                map = {};
                            }
                        }

                        var estudianteSel = document.querySelector('select[name="estudiante_id"]');
                        if (!estudianteSel) return;

                        estudianteSel.addEventListener('change', function(e){
                            var val = this.value;
                            // Si hay un curso asociado en el mapa, colocarlo en el select de curso
                            if (val && Object.prototype.hasOwnProperty.call(map, val) && map[val]) {
                                var cursoSel = document.querySelector('select[name="curso_id"]');
                                if (cursoSel) {
                                    cursoSel.value = map[val];
                                }
                            }
                            // Enviar formulario para repoblar materias y resultados
                            // (se enviará estudiante_id y curso_id si existe)
                            this.form.submit();
                        });
                    })();
                </script>
            @endif

            @php
                $canCreateNotes = Auth::check() && method_exists(Auth::user(), 'hasPermission') && Auth::user()->hasPermission('registrar_notas');
                $canViewNotes = Auth::check() && method_exists(Auth::user(), 'hasAnyPermission') && Auth::user()->hasAnyPermission(['ver_notas','registrar_notas','consultar_reporte_academicos']);
                $isStudent = false;
                if (Auth::check() && optional(Auth::user()->role)->nombre) {
                    $isStudent = stripos(optional(Auth::user()->role)->nombre, 'estudiante') !== false;
                }
            @endphp

            @if($canViewNotes && ! $canCreateNotes)
                <div class="alert alert-warning">No tienes permiso para <strong>crear</strong> notas en este módulo, pero podrás consultar y, si corresponde, quitar el estado <em>definitiva</em>.</div>
            @endif

            <!-- Tabla -->
            @if(isset($showResults) && $showResults)
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle">
                    <thead class="table-secondary">
                        <tr>
                            <th>Estudiante</th>
                            <th>Materia</th>
                            <th>Calificación</th>
                            <th>Aprobada</th>
                            <th>Notas</th>
                            @if(! $isStudent)
                                <th class="text-end">Acciones</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($notas as $nota)
                            <tr>
                                <td>{{ $nota->matricula->user->name ?? 'N/A' }}</td>
                                <td>{{ $nota->materia->nombre ?? 'N/A' }}</td>
                                @php
                                    // Calcular calificación efectiva en escala 0-5 para la fila.
                                    // Prioridad: promedio calculado por el controlador ($nota->calificacion),
                                    // luego valores individuales si no existe el promedio.
                                    $calif_ef = null;

                                    // Si el controlador ya calculó el promedio (suma de calificaciones / cantidad), usarlo.
                                    if (isset($nota->calificacion) && $nota->calificacion !== null) {
                                        $calif_ef = $nota->calificacion;
                                    }
                                    // Si la fila misma tiene valor (caso de Eloquent Nota en lista simple)
                                    elseif (isset($nota->valor) && $nota->valor !== null) {
                                        $v = floatval($nota->valor);
                                        if ($v <= 5.0) {
                                            $calif_ef = round($v, 2);
                                        } else {
                                            $calif_ef = round(($v / 100.0) * 5.0, 2);
                                        }
                                    }
                                    // Si hay una nota representativa con actividades, usar el promedio de actividades
                                    elseif (isset($nota->nota) && is_object($nota->nota) && isset($nota->nota->actividades) && $nota->nota->actividades->count() > 0) {
                                        $calif_ef = round($nota->nota->actividades->avg('valor'), 2);
                                    }
                                    // Si existe una nota representativa con valor numérico, normalizarlo
                                    elseif (isset($nota->nota) && is_object($nota->nota) && isset($nota->nota->valor) && $nota->nota->valor !== null) {
                                        $v = floatval($nota->nota->valor);
                                        if ($v <= 5.0) {
                                            $calif_ef = round($v, 2);
                                        } else {
                                            $calif_ef = round(($v / 100.0) * 5.0, 2);
                                        }
                                    }

                                    $aprobada_display_row = ($calif_ef !== null && $calif_ef >= 3.0) ? true : false;
                                @endphp
                                <td>
                                    @if($calif_ef !== null)
                                        {{ number_format($calif_ef, 2) }}
                                    @else
                                        --
                                    @endif
                                </td>
                                <td>
                                    @if($aprobada_display_row)
                                        <span class="badge bg-success">Sí</span>
                                    @else
                                        <span class="badge bg-warning text-dark">No</span>
                                    @endif
                                </td>

                                @php
                                    // Determinar id de la matrícula para el enlace "Notas"
                                    $matriculaIdBtn = null;
                                    if (is_object($nota) && isset($nota->matricula) && isset($nota->matricula->id)) {
                                        $matriculaIdBtn = $nota->matricula->id;
                                    } elseif (isset($nota->matricula_id)) {
                                        $matriculaIdBtn = $nota->matricula_id;
                                    } elseif (is_object($nota) && isset($nota->nota) && is_object($nota->nota) && isset($nota->nota->matricula_id)) {
                                        $matriculaIdBtn = $nota->nota->matricula_id;
                                    }
                                @endphp

                                <td class="text-center">
                                    @if($matriculaIdBtn)
                                                @php
                                                    $userIdBtn = optional($nota->matricula->user)->id ?? null;
                                                    $countNotas = isset($notaCounts) && $userIdBtn && isset($notaCounts[$userIdBtn]) ? $notaCounts[$userIdBtn] : 0;
                                                @endphp
                                                <a href="{{ route('notas.matricula.ver', array_filter(['matricula' => $matriculaIdBtn, 'back' => request()->fullUrl(), 'curso_id' => request('curso_id'), 'materia_id' => request('materia_id')])) }}" class="btn btn-sm btn-outline-info">
                                                    <i class="bi bi-file-text me-1"></i> Notas
                                                    <span class="badge bg-secondary ms-2">{{ $countNotas }}</span>
                                                </a>
                                    @else
                                        -
                                    @endif
                                </td>

                                @if(! $isStudent)
                                <td class="text-end">
                                    @php
                                        // tratar distintos tipos de $nota (stdClass o Eloquent)
                                        $matriculaId = null;
                                        $materiaId = request('materia_id') ?? null;
                                        if (is_object($nota) && isset($nota->matricula) && isset($nota->matricula->id)) {
                                            $matriculaId = $nota->matricula->id;
                                            // si no viene materia en request, intentar obtenerla del item
                                            if (! $materiaId && isset($nota->materia) && isset($nota->materia->id)) {
                                                $materiaId = $nota->materia->id;
                                            }
                                        } elseif (isset($nota->matricula_id)) {
                                            $matriculaId = $nota->matricula_id;
                                        }
                                    @endphp

                                    @php
                                        $userIdForCreate = is_object($nota) && isset($nota->matricula->user) ? optional($nota->matricula->user)->id : null;
                                        $existingCount = isset($notaCounts) && $userIdForCreate && isset($notaCounts[$userIdForCreate]) ? $notaCounts[$userIdForCreate] : 0;
                                        $createLabel = $existingCount > 0 ? 'Agregar otra nota' : 'Crear nota';
                                    @endphp

                                    @if(! $canCreateNotes)
                                        <button type="button" class="btn btn-sm btn-secondary" disabled title="No tienes permiso para crear notas.">
                                            <i class="bi bi-plus-circle me-1"></i> {{ $createLabel }}
                                        </button>
                                    @else
                                        @if($matriculaId)
                                            <a href="{{ route('notas.create', ['matricula_id' => $matriculaId, 'materia_id' => $materiaId, 'back' => request()->fullUrl()]) }}" class="btn btn-sm btn-outline-success">
                                                <i class="bi bi-plus-circle me-1"></i> {{ $createLabel }}
                                            </a>
                                        @else
                                            <a href="{{ route('notas.create', ['curso_id' => request('curso_id'), 'materia_id' => request('materia_id'), 'back' => request()->fullUrl()]) }}" class="btn btn-sm btn-outline-success">
                                                <i class="bi bi-plus-circle me-1"></i> {{ $createLabel }}
                                            </a>
                                        @endif
                                    @endif
                                </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div class="mt-3">
                {{ $notas->withQueryString()->links() }}
            </div>
            @else
                <div class="alert alert-info">Utilice los filtros para listar estudiantes (Curso + Materia).</div>
            @endif
        </div>
    </div>
</div>
@endsection
