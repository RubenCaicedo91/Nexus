@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="h4">Horarios</h1>
    <p class="text-muted">Página de ejemplo para gestionar horarios.</p>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Formulario para crear horario --}}
    @unless(isset($isDocente) && $isDocente)
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Crear nuevo horario</h5>
            <form action="{{ route('horarios.guardar') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="curso" class="form-label">Curso</label>
                    <select name="curso_id" id="curso" class="form-select" required>
                        <option value="">Selecciona un curso</option>
                        @foreach($cursos as $c)
                            <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label for="materia_id" class="form-label">Materia</label>
                    <select name="materia_id" id="materia_id" class="form-select">
                        <option value="">(Selecciona un curso primero)</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="dia" class="form-label">Día</label>
                    <select name="dia" class="form-select" required>
                        <option value="">Selecciona un día</option>
                        <option>Lunes</option>
                        <option>Martes</option>
                        <option>Miércoles</option>
                        <option>Jueves</option>
                        <option>Viernes</option>
                    </select>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="hora_inicio" class="form-label">Hora inicio</label>
                        <input type="time" name="hora_inicio" id="hora_inicio" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="hora_fin" class="form-label">Hora fin</label>
                        <input type="time" name="hora_fin" id="hora_fin" class="form-control" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Guardar horario</button>
            </form>
        </div>
    </div>
    @endunless

    {{-- Tabla de horarios existentes --}}
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Horarios registrados</h5>
            {{-- Filtro por curso (muestra los horarios del curso seleccionado) --}}
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="filter_curso" class="form-label">Filtrar por curso</label>
                    <select id="filter_curso" class="form-select">
                        <option value="">-- Todos los cursos --</option>
                        @foreach($cursos as $c)
                            <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="btn-group w-100" role="group">
                        <button id="filter_search" class="btn btn-primary">Buscar</button>
                        <button id="filter_clear" class="btn btn-secondary">Limpiar filtro</button>
                    </div>
                </div>
                <div class="col-md-3">
                    <label for="filter_docente" class="form-label">Filtrar por docente</label>
                    <select id="filter_docente" class="form-select">
                        <option value="">-- Todos los docentes --</option>
                        @if(isset($docentes))
                            @foreach($docentes as $doc)
                                <option value="{{ $doc->id }}">{{ $doc->name }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
            </div>
            {{-- Embed all cursos as JSON to restore if needed --}}
            <script id="all-cursos-json" type="application/json">@json($cursos)</script>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Curso</th>
                        <th>Docente</th>
                        <th>Materia</th>
                        <th>Día</th>
                        <th>Hora inicio</th>
                        <th>Hora fin</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($horarios as $horario)
                        @php
                            $cursoIdAttr = $horario->curso_id ?? \App\Models\Curso::where('nombre', $horario->curso)->value('id');
                        @endphp
                        <tr data-curso-id="{{ $cursoIdAttr ?? '' }}" data-docente="{{ $horario->docente_id ?? '' }}">
                            <td>{{ $horario->curso }}</td>
                            <td>{{ $horario->docente_nombre ?? '—' }}</td>
                            <td>{{ $horario->materia_nombre ?? '—' }}</td>
                            <td>{{ $horario->dia }}</td>
                            <td>{{ $horario->hora_inicio ?? ($horario->hora) }}</td>
                            <td>{{ $horario->hora_fin ?? '' }}</td>
                            <td>
                                @unless(isset($isDocente) && $isDocente)
                                    <a href="{{ route('horarios.editar', $horario->id) }}" class="btn btn-sm btn-warning">✏️ Editar</a>

                                    <form action="{{ route('horarios.eliminar', $horario->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Estás segura de eliminar este horario?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">🗑️ Eliminar</button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">No hay horarios registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Select2 CSS & JS (CDN) para mejorar dropdown con scroll -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<style>
/* Limitar la altura del dropdown de Select2 a aproximadamente 5 elementos y activar scroll */
.select2-container--default .select2-results__options { max-height: 10rem; overflow-y: auto; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function(){
    // Inicializar Select2 en el select de cursos
    if (window.jQuery && window.jQuery().select2) {
        $('#curso').select2({
            width: '100%',
            dropdownAutoWidth: true,
            placeholder: 'Selecciona un curso',
            allowClear: true,
            minimumResultsForSearch: 10 // mostrar buscador si hay muchos cursos
        });

        $('#materia_id').select2({
            width: '100%',
            placeholder: '(Selecciona una materia)'
        });

        // Inicializar Select2 para los filtros también
        $('#filter_curso').select2({
            width: '100%',
            placeholder: '-- Todos los cursos --',
            allowClear: true,
            minimumResultsForSearch: 10
        });

        $('#filter_docente').select2({
            width: '100%',
            placeholder: '-- Todos los docentes --',
            allowClear: true,
            minimumResultsForSearch: 10
        });
    }

    // Cargar materias vía AJAX al cambiar el curso
    $('#curso').on('change', function(){
        const cursoId = $(this).val();
        const $materia = $('#materia_id');
        $materia.empty().append(new Option('(Cargando...)', ''));
        if (!cursoId) {
            $materia.empty().append(new Option('(Selecciona un curso primero)', ''));
            return;
        }
        fetch('/gestion-academica/cursos/' + encodeURIComponent(cursoId) + '/materias-json')
            .then(r => r.json())
            .then(data => {
                $materia.empty().append(new Option('(Sin materia seleccionada)', ''));
                data.forEach(m => {
                    const opt = new Option(m.nombre, m.id);
                    $materia.append(opt);
                });
                $materia.trigger('change');
            }).catch(err => {
                console.error('Error cargando materias:', err);
                $materia.empty().append(new Option('(Error al cargar materias)', ''));
            });
    });

    // Filtrar tabla de horarios por curso y docente.
    // Comportamiento: el filtro empieza en blanco; al limpiar se dejan ambos en blanco;
    // si se cambian ambos, el último que se modificó tiene prioridad (último aplicado gana).
    function normalize(s){ return (s||'').toString().trim().toLowerCase(); }
    const $filterCurso = $('#filter_curso');
    const $filterDocente = $('#filter_docente');
    const $tableRows = $('table tbody tr');

    // Último filtro que el usuario cambió: 'curso' | 'docente' | null
    let lastChanged = null;

    // Al ingresar a la página asegurarnos que no haya filtros activos
    // (evitar que el navegador recuerde selecciones). No disparamos
    // eventos ni fetches aquí para que la carga sea silenciosa.
    try {
        $filterCurso.val('');
        $filterDocente.val('');
        // si usamos Select2, pedir que refresque la UI
        try { $filterCurso.trigger('change.select2'); } catch(e) {}
        try { $filterDocente.trigger('change.select2'); } catch(e) {}
    } catch(e) {
        // ignorar si algo falla en selección inicial
    }

    function applyFilters(){
        const valCurso = ($filterCurso.val() || '').toString().trim();
        const valDoc = ($filterDocente.val() || '').toString().trim();

        // Si no hay último cambio, mostrar todo
        if (!lastChanged) {
            $tableRows.show();
            return;
        }

        $tableRows.each(function(){
            const cursoId = ($(this).attr('data-curso-id') || '').toString().trim();
            const docente = ($(this).attr('data-docente') || '').toString().trim();

            let show = true;
            if (lastChanged === 'curso') {
                if (valCurso && cursoId !== valCurso) show = false;
            } else if (lastChanged === 'docente') {
                if (valDoc && docente !== valDoc) show = false;
            }

            if (show) $(this).show(); else $(this).hide();
        });
    }

    $filterCurso.on('change', function(){
        lastChanged = 'curso';
        const cursoId = $(this).val();
        // Al cambiar curso, limpiar el filtro de docente (pero no disparar su handler que
        // podría restaurar el select de cursos y borrar la selección del usuario)
        $filterDocente.val('');
        try { $filterDocente.trigger('change.select2'); } catch(e) {}
        // Aplicar filtro inmediatamente para que el usuario vea resultados sin esperar al fetch
        applyFilters();

        if (!cursoId) {
            // Restaurar lista completa de docentes desde todos (no embebido); solo limpiamos la selección
            applyFilters();
            return;
        }

        // Repoblar docentes en segundo plano (no bloqueante)
        fetch(`/gestion-academica/cursos/${encodeURIComponent(cursoId)}/docentes-json`)
            .then(r => r.ok ? r.json() : Promise.reject(r.statusText))
            .then(data => {
                const $sel = $filterDocente.empty().append(new Option('-- Todos los docentes --', ''));
                data.forEach(d => $sel.append(new Option(d.name, d.id)));
                // mantener la selección de curso (no la limpiamos) y no cambiar lastChanged
            }).catch(err => {
                console.error('Error cargando docentes del curso:', err);
            });
    });

    $filterDocente.on('change', function(){
        // Cuando el usuario selecciona un docente, marcamos como último cambio docente
        lastChanged = 'docente';
        const docenteId = $(this).val();
        if (!docenteId) {
            // Si este evento fue disparado por la limpieza del filtro de docente
            // desde el handler de 'curso' (es decir, el usuario seleccionó curso),
            // no restauramos ni limpiamos el select de curso aquí porque eso
            // borra la selección que el usuario acaba de hacer.
            if (lastChanged === 'curso') {
                applyFilters();
                return;
            }

            // Restaurar lista completa desde el JSON embebido
            try {
                const all = JSON.parse(document.getElementById('all-cursos-json').textContent || '[]');
                const $sel = $filterCurso.empty().append(new Option('-- Todos los cursos --', ''));
                all.forEach(c => $sel.append(new Option(c.nombre, c.id)));
                $filterCurso.val('');
                applyFilters();
            } catch (e) {
                console.error('Error restaurando cursos:', e);
            }
            return;
        }

        fetch(`/gestion-academica/docentes/${encodeURIComponent(docenteId)}/cursos-json`)
            .then(r => r.ok ? r.json() : Promise.reject(r.statusText))
            .then(data => {
                const $sel = $filterCurso.empty().append(new Option('-- Todos los cursos --', ''));
                data.forEach(c => $sel.append(new Option(c.nombre, c.id)));
                // No disparar evento change del curso para no alterar lastChanged
                    $filterCurso.val('');
                    applyFilters();
            }).catch(err => {
                console.error('Error cargando cursos del docente:', err);
            });
    });

    $('#filter_clear').on('click', function(e){
        e.preventDefault();
        // Si no hay ningún filtro activo (ningún select con valor y lastChanged == null), no hacemos nada
        const isCursoSelected = ($filterCurso.val() || '') !== '';
        const isDocenteSelected = ($filterDocente.val() || '') !== '';
        if (!isCursoSelected && !isDocenteSelected && !lastChanged) {
            return; // no-op
        }

        // Limpiar selects y restaurar lista completa de cursos
        $filterCurso.val('');
        $filterDocente.val('');
        // Trigger change in case a UI plugin (Select2) is attached
        try { $filterCurso.trigger('change'); } catch(e) {}
        try { $filterDocente.trigger('change'); } catch(e) {}
        try {
            const all = JSON.parse(document.getElementById('all-cursos-json').textContent || '[]');
            const $sel = $filterCurso.empty().append(new Option('-- Todos los cursos --', ''));
            all.forEach(c => $sel.append(new Option(c.nombre, c.id)));
        } catch (e) {
            console.error('Error restaurando cursos:', e);
        }
        lastChanged = null;
        applyFilters();
    });

    // Botón Buscar: aplica el filtrado según la lógica de prioridad.
    $('#filter_search').on('click', function(e){
        e.preventDefault();
        // Si no hay último cambio registrado, inferir uno por presencia de valores
        if (!lastChanged) {
            const isCursoSelected = ($filterCurso.val() || '') !== '';
            const isDocenteSelected = ($filterDocente.val() || '') !== '';
            if (isCursoSelected && !isDocenteSelected) lastChanged = 'curso';
            else if (isDocenteSelected && !isCursoSelected) lastChanged = 'docente';
            else if (isCursoSelected && isDocenteSelected) {
                // Si ambos están presentes y no hay lastChanged, priorizamos el último que el usuario tocó
                // Si no hay registro, por defecto priorizamos 'curso'
                lastChanged = 'curso';
            }
        }
        applyFilters();
    });
});
</script>
@endpush