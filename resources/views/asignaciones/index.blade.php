@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-users me-2"></i>Asignaciones de Estudiantes
                    </h4>
                    <a href="{{ route('asignaciones.create') }}" class="btn btn-light">
                        <i class="fas fa-plus me-2"></i>Nueva Asignación
                    </a>
                </div>

                <div class="card-body">
                    {{-- Filtros --}}
                    <form method="GET" class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label for="curso_id" class="form-label">Curso</label>
                            <select name="curso_id" id="curso_id" class="form-select" data-url-template="{{ route('asignaciones.curso.estudiantes', ['cursoId' => 'CURSO_ID']) }}" data-search-url="{{ route('asignaciones.json.estudiantes') }}">
                                <option value="">Todos los cursos</option>
                                @foreach($cursos as $curso)
                                    <option value="{{ $curso->id }}" {{ request('curso_id') == $curso->id ? 'selected' : '' }}>
                                        {{ $curso->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        {{-- CAMBIO AQUÍ: El filtro de estudiante ahora es un desplegable --}}
                        <div class="col-md-3">
                            <label for="estudiante" class="form-label">Estudiante</label>
                            <input type="text" name="estudiante" id="estudiante" class="form-control" list="estudiantes_list" value="{{ request('estudiante') }}" placeholder="Escriba nombre o apellido...">
                            <input type="hidden" name="estudiante_id" id="estudiante_id" value="{{ request('estudiante_id') }}">
                            <datalist id="estudiantes_list">
                                @foreach($estudiantes as $estudiante)
                                    <option value="{{ $estudiante->name }}">{{ $estudiante->name }}</option>
                                @endforeach
                            </datalist>
                        </div>
                        <div class="col-md-2">
                            <label for="estado" class="form-label">Estado</label>
                            <select name="estado" id="estado" class="form-select">
                                <option value="">Todos</option>
                                <option value="activo" {{ request('estado') == 'activo' ? 'selected' : '' }}>Activo</option>
                                <option value="inactivo" {{ request('estado') == 'inactivo' ? 'selected' : '' }}>Inactivo</option>
                                <option value="suspendido" {{ request('estado') == 'suspendido' ? 'selected' : '' }}>Suspendido</option>
                               
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="documentos_completos" class="form-label">Documentos</label>
                            <select name="documentos_completos" id="documentos_completos" class="form-select">
                                <option value="">Todos</option>
                                <option value="1" {{ request('documentos_completos') == '1' ? 'selected' : '' }}>Completos</option>
                                <option value="0" {{ request('documentos_completos') == '0' ? 'selected' : '' }}>Incompletos</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search"></i> Filtrar
                            </button>
                            <a href="{{ route('asignaciones.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </form>

                    {{-- Tabla de asignaciones --}}
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Estudiante</th>
                                    <th>Curso</th>
                                    <th>Fecha Matrícula</th>
                                    <th>Estado</th>
                                    <th>Documentos</th>
                                    <th>Pago</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($asignaciones as $asignacion)
                                    <tr>
                                        <td>{{ $asignacion->id }}</td>
                                        <td>
                                            <div class="fw-bold">{{ $asignacion->user->name }}</div>
                                            <small class="text-muted">{{ $asignacion->user->email }}</small>
                                        </td>
                                        <td>
                                            @if(!empty($asignacion->curso))
                                                <span class="badge bg-info">{{ $asignacion->curso->nombre }}</span>
                                            @else
                                                <span class="badge bg-info">Sin asignar</span>
                                                @if(!empty($asignacion->curso_nombre))
                                                    <span class="badge bg-secondary ms-2">Matriculado: {{ $asignacion->curso_nombre }}</span>
                                                @endif
                                            @endif
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($asignacion->fecha_matricula)->format('d/m/Y') }}</td>
                                        <td>
                                            @switch($asignacion->estado)
                                                @case('activo')
                                                    <span class="badge bg-success">Activo</span>
                                                    @break
                                                @case('inactivo')
                                                    <span class="badge bg-secondary">Inactivo</span>
                                                    @break
                                                @case('falta de documentacion')
                                                    <span class="badge bg-warning text-dark">Falta documentación</span>
                                                    @break
                                                @case('completado')
                                                    <span class="badge bg-warning">Completado</span>
                                                    @break
                                                @case('suspendido')
                                                    <span class="badge bg-warning">Suspendido</span>
                                                    @break
                                                @default
                                                    <span class="badge bg-light text-dark">{{ $asignacion->estado }}</span>
                                            @endswitch
                                        </td>
                                        <td>
                                            @if(method_exists($asignacion, 'tieneDocumentosCompletos') ? $asignacion->tieneDocumentosCompletos() : ($asignacion->documentos_completos ?? false))
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i>Completos
                                                </span>
                                            @else
                                                <span class="badge bg-danger">
                                                    <i class="fas fa-times me-1"></i>Incompletos
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($asignacion->monto_pago && $asignacion->fecha_pago)
                                                <div class="text-success fw-bold">${{ number_format($asignacion->monto_pago, 0, ',', '.') }}</div>
                                                <small class="text-muted">{{ \Carbon\Carbon::parse($asignacion->fecha_pago)->format('d/m/Y') }}</small>
                                            @else
                                                <span class="text-muted">Sin pago</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('asignaciones.show', $asignacion) }}" 
                                                   class="btn btn-sm btn-outline-primary" title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('asignaciones.edit', $asignacion) }}" 
                                                   class="btn btn-sm btn-outline-warning" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        data-bs-toggle="modal" data-bs-target="#deleteModal{{ $asignacion->id }}" title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>

                                            {{-- Modal de confirmación para eliminar --}}
                                            <div class="modal fade" id="deleteModal{{ $asignacion->id }}" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Confirmar eliminación</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            ¿Está seguro de que desea eliminar la asignación de <strong>{{ $asignacion->user->name }}</strong> 
                                                            al curso <strong>{{ $asignacion->curso->nombre ?? 'Sin asignar' }}</strong>?
                                                            <br><br>
                                                            <div class="alert alert-warning">
                                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                                Esta acción también eliminará todos los documentos asociados y no se puede deshacer.
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                            <form action="{{ route('asignaciones.destroy', $asignacion) }}" method="POST" class="d-inline">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-danger">
                                                                    <i class="fas fa-trash me-2"></i>Eliminar
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                                <h5>No hay asignaciones registradas</h5>
                                                <p>Comience creando una nueva asignación de estudiante.</p>
                                                <a href="{{ route('asignaciones.create') }}" class="btn btn-primary">
                                                    <i class="fas fa-plus me-2"></i>Crear Primera Asignación
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Paginación --}}
                    @if($asignaciones->hasPages())
                        <div class="d-flex justify-content-center mt-4">
                            {{ $asignaciones->withQueryString()->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Cuando cambia el curso, obtener estudiantes por AJAX y actualizar el select
        const cursoSelect = document.getElementById('curso_id');
        const estudianteInput = document.getElementById('estudiante');
        const estudiantesDatalist = document.getElementById('estudiantes_list');
        // mapa nombre -> id para resolver selección del datalist
        const estudiantesMap = {};

        function buildUrl(template, id) {
            return template.replace('CURSO_ID', id);
        }

        async function fetchStudents(cursoId, selectedName = '') {
            // Si no hay curso, dejamos las opciones iniciales (ya renderizadas en el datalist)
            const template = cursoSelect.getAttribute('data-url-template');
            if (!cursoId) {
                // Si existe selectedName, aseguramos que el input lo mantenga
                if (selectedName) estudianteInput.value = selectedName;
                return;
            }

            const url = buildUrl(template, cursoId);

            try {
                const res = await fetch(url);
                if (!res.ok) throw new Error('Error al obtener estudiantes');
                const data = await res.json();

                // data = [{id, name}, ...] — rellenar datalist
                estudiantesDatalist.innerHTML = '';
                // limpiar mapa previo para evitar entradas antiguas
                for (const k in estudiantesMap) delete estudiantesMap[k];
                data.forEach(s => {
                    const opt = document.createElement('option');
                    opt.value = s.name;
                    estudiantesDatalist.appendChild(opt);
                    estudiantesMap[s.name] = s.id;
                });

                // Mantener valor seleccionado si aplica
                if (selectedName) {
                    estudianteInput.value = selectedName;
                    const hid = document.getElementById('estudiante_id');
                    if (hid && estudiantesMap[selectedName]) hid.value = estudiantesMap[selectedName];
                }
            } catch (err) {
                console.error(err);
            }
        }

        // Auto-submit para filtros (excepto curso_id, que actualiza el select)
        const autoSubmitFilters = ['estudiante', 'estado', 'documentos_completos'];
        autoSubmitFilters.forEach(filterId => {
            const element = document.getElementById(filterId);
            if (element) {
                element.addEventListener('change', function() {
                    this.form.submit();
                });
            }
        });

        // Autocompletado al escribir en el campo estudiante (debounce)
        const searchUrl = cursoSelect.getAttribute('data-search-url');
        function debounce(fn, wait = 250) {
            let t;
            return function(...args) {
                clearTimeout(t);
                t = setTimeout(() => fn.apply(this, args), wait);
            };
        }

        async function searchStudentsByText(q) {
            if (!q || q.length < 2) {
                return; // evitar búsquedas muy cortas
            }
            try {
                const url = new URL(searchUrl, window.location.origin);
                url.searchParams.set('q', q);
                const cursoId = cursoSelect.value;
                if (cursoId) url.searchParams.set('curso_id', cursoId);

                const res = await fetch(url);
                if (!res.ok) throw new Error('Error al buscar estudiantes');
                const data = await res.json();

                estudiantesDatalist.innerHTML = '';
                // limpiar mapa previo
                for (const k in estudiantesMap) delete estudiantesMap[k];
                data.forEach(s => {
                    const opt = document.createElement('option');
                    opt.value = s.name;
                    estudiantesDatalist.appendChild(opt);
                    estudiantesMap[s.name] = s.id;
                });
                console.debug('searchStudentsByText result', data);
            } catch (err) {
                console.error(err);
            }
        }

        if (estudianteInput) {
            estudianteInput.addEventListener('input', debounce(function(e) {
                const q = this.value.trim();
                if (!q) return;
                searchStudentsByText(q);
            }, 300));

            // al perder foco, si el texto coincide exactamente con una opción, rellenar id
            estudianteInput.addEventListener('blur', function() {
                const name = this.value.trim();
                const hid = document.getElementById('estudiante_id');
                if (hid) {
                    hid.value = estudiantesMap[name] ? estudiantesMap[name] : '';
                }
            });

            // al cambiar (selección), asignar id y enviar el formulario
            estudianteInput.addEventListener('change', function() {
                const name = this.value.trim();
                const hid = document.getElementById('estudiante_id');
                if (hid) hid.value = estudiantesMap[name] ? estudiantesMap[name] : '';
                this.form.submit();
            });
        }

        // Cuando cambia curso: actualizar lista de estudiantes (no enviado automático)
        if (cursoSelect) {
            cursoSelect.addEventListener('change', function() {
                const id = this.value;
                // cargar estudiantes del curso y luego limpiar estudiante_id y enviar el formulario
                fetchStudents(id).then(() => {
                    const hid = document.getElementById('estudiante_id');
                    const estInput = document.getElementById('estudiante');
                    if (hid) hid.value = '';
                    if (estInput) estInput.value = '';
                    // enviar formulario automáticamente para aplicar el filtro por curso
                    const form = cursoSelect.closest('form');
                    if (form) form.submit();
                });
            });

            // Si hay un curso seleccionado al cargar la página, obtener estudiantes y mantener el seleccionado si existe
            const initialCurso = cursoSelect.value;
            const selectedName = estudianteInput ? estudianteInput.value : '';
            const selectedId = document.getElementById('estudiante_id') ? document.getElementById('estudiante_id').value : '';
            if (initialCurso) {
                fetchStudents(initialCurso, selectedName).then(() => {
                    if (selectedId) {
                        const hid = document.getElementById('estudiante_id');
                        if (hid) hid.value = selectedId;
                    }
                });
            }
        }
    });
    </script>
@endsection