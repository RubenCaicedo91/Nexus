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
    
    {{-- Tarjeta: Gestionar Materias (nuevo menú independiente) --}}
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="text-success mb-3">
                    <i class="fas fa-book-open fa-2x"></i>
                </div>
                <h5 class="card-title mb-3">Gestionar Materias</h5>
                @if(auth()->check() && (
                    auth()->user()->hasPermission('asignar_docentes') ||
                    (optional(auth()->user()->role)->nombre && (
                        stripos(optional(auth()->user()->role)->nombre, 'admin') !== false ||
                        stripos(optional(auth()->user()->role)->nombre, 'administrador') !== false
                    )) || auth()->user()->roles_id == 1
                ))
                    <button class="btn btn-outline-success w-100" id="openMateriasModalBtn">
                        <i class="fas fa-book-open me-2"></i>Gestionar Materias
                    </button>
                @else
                    <button class="btn btn-outline-secondary w-100" disabled>Materias (sin permiso)</button>
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

        // Elements
        const openBtn = document.getElementById('openMateriasModalBtn');
        if (!openBtn) return;

        // Create the modal HTML dynamically into the page (so we keep file edits small)
        const materiasModalHtml = `
        <div class="modal fade" id="materiasModal" tabindex="-1" aria-labelledby="materiasModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Gestionar Materias</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label">Curso</label>
                                <select id="materias_curso_select" class="form-select">
                                    <option value="">-- Selecciona curso --</option>
                                    @foreach($cursos as $curso)
                                        <option value="{{ $curso->id }}">{{ $curso->nombre }}</option>
                                    @endforeach
                                </select>
                                <div class="mt-3 d-grid">
                                    <button id="cargarMateriasBtn" class="btn btn-primary">Cargar materias</button>
                                    <button id="openCrearMateriaBtn" class="btn btn-success mt-2">Crear nueva materia</button>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div id="materiasList" class="list-group">
                                    <div class="text-muted">Selecciona un curso y haz clic en "Cargar materias"</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
        `;

        // Append modal HTML to body
        document.body.insertAdjacentHTML('beforeend', materiasModalHtml);

        const materiasModalEl = document.getElementById('materiasModal');
        const materiasModal = new bootstrap.Modal(materiasModalEl);

        openBtn.addEventListener('click', function(){
                materiasModal.show();
        });

        // cargar materias
        document.addEventListener('click', function(e){
                if (e.target && e.target.id === 'cargarMateriasBtn'){
                        const select = document.getElementById('materias_curso_select');
                        const cursoId = select.value;
                        if (!cursoId) {
                                alert('Selecciona un curso');
                                return;
                        }
                        fetch('/gestion-academica/cursos/' + cursoId + '/materias-json', {
                                headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        })
                        .then(r => r.json())
                        .then(data => renderMateriasList(data, cursoId))
                        .catch(err => {
                                console.error(err);
                                alert('Error al cargar materias');
                        });
                }

                // abrir crear materia modal
                if (e.target && e.target.id === 'openCrearMateriaBtn'){
                        // build create modal HTML
                        const cursoId = document.getElementById('materias_curso_select').value || '';
                        const crearHtml = `
                        <div class="modal fade" id="materiaCreateModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header"><h5 class="modal-title">Crear Materia</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                    </div>
                                    <form id="materiaCreateForm" action="{{ route('materias.crear') }}" method="POST">
                                        <div class="modal-body">
                                            <input type="hidden" name="_token" value="${csrfToken}">
                                            <div class="mb-3">
                                                <label class="form-label">Curso</label>
                                                <select name="curso_id" class="form-select" required>
                                                    @foreach($cursos as $curso)
                                                        <option value="{{ $curso->id }}" ${cursoId == '{{ $curso->id }}' ? 'selected' : ''}>{{ $curso->nombre }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Nombre</label>
                                                <input name="nombre" type="text" class="form-control" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Descripción</label>
                                                <textarea name="descripcion" class="form-control"></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Docente (opcional)</label>
                                                <select name="docente_id" class="form-select">
                                                    <option value="">-- Ninguno --</option>
                                                    @foreach($docentes as $d)
                                                        <option value="{{ $d->id }}">{{ $d->name }} ({{ $d->email }})</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                            <button type="submit" class="btn btn-primary">Crear</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        `;

                        // remove existing create modal if present
                        const existing = document.getElementById('materiaCreateModal');
                        if (existing) existing.remove();
                        document.body.insertAdjacentHTML('beforeend', crearHtml);
                        const cm = new bootstrap.Modal(document.getElementById('materiaCreateModal'));
                        cm.show();
                }
        });

        // Render list of materias into the panel
        function renderMateriasList(materias, cursoId){
                const container = document.getElementById('materiasList');
                container.innerHTML = '';
                if (!materias || materias.length === 0){
                        container.innerHTML = '<div class="text-muted">No hay materias para este curso.</div>';
                        return;
                }
                materias.forEach(m => {
                        const div = document.createElement('div');
                        div.className = 'list-group-item d-flex justify-content-between align-items-center';
                        div.innerHTML = `
                                <div>
                                    <strong>${escapeHtml(m.nombre)}</strong>
                                    <div class="small text-muted">ID: ${m.id} ${m.docente_id ? (' - Docente ID: ' + m.docente_id) : ''}</div>
                                </div>
                                <div>
                                    <button class="btn btn-sm btn-outline-primary me-2" data-id="${m.id}" data-action="edit-materia">Editar</button>
                                </div>
                        `;
                        container.appendChild(div);
                });
        }

                // Delegación para click en botón Editar (usa endpoint JSON y genera modal con el formulario)
                document.addEventListener('click', function(e){
                                const btn = e.target.closest('button[data-action="edit-materia"]');
                                if (!btn) return;
                                const materiaId = btn.getAttribute('data-id');
                                if (!materiaId) return;

                                fetch('/gestion-academica/materias/' + materiaId + '/json', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                                        .then(r => r.json())
                                        .then(data => {
                                                // build edit modal with pre-filled form
                                                const editHtml = `
                                                <div class="modal fade" id="materiaEditModal" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Editar Materia - ${escapeHtml(data.nombre)}</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                                            </div>
                                                            <form class="materia-edit-form" action="/gestion-academica/materias/${data.id}" method="POST">
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="_token" value="${csrfToken}">
                                                                    <input type="hidden" name="_method" value="PUT">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Nombre</label>
                                                                        <input type="text" name="nombre" class="form-control" value="${escapeHtml(data.nombre)}" required>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Descripción</label>
                                                                        <input type="text" name="descripcion" class="form-control" value="${escapeHtml(data.descripcion || '')}">
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Docente asignado</label>
                                                                        <select name="docente_id" class="form-select">
                                                                            <option value="">-- Ninguno --</option>
                                                                            ${docentesOptions.map(d => `<option value="${d.id}" ${data.docente_id == d.id ? 'selected' : ''}>${escapeHtml(d.name + ' (' + d.email + ')')}</option>`).join('')}
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                                    <button type="submit" class="btn btn-primary">Guardar</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                                `;

                                                const existing = document.getElementById('materiaEditModal');
                                                if (existing) existing.remove();
                                                document.body.insertAdjacentHTML('beforeend', editHtml);
                                                const modal = new bootstrap.Modal(document.getElementById('materiaEditModal'));
                                                modal.show();
                                        })
                                        .catch(err => { console.error(err); alert('Error cargando datos de la materia'); });
                });

        // Delegación para manejar envíos (create/edit) por AJAX
        document.addEventListener('submit', function(e){
                const form = e.target;
                // Crear materia (modal)
                if (form && form.id === 'materiaCreateForm'){
                        e.preventDefault();
                        const action = form.action;
                        const formData = new FormData(form);
                        fetch(action, {
                                method: 'POST',
                                headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
                                body: formData
                        })
                        .then(res => {
                                if (res.redirected) window.location.href = res.url; // fallback
                                return res.text();
                        })
                        .then(() => {
                                // close modal and refresh list if curso selected
                                const cmEl = document.getElementById('materiaCreateModal');
                                if (cmEl) {
                                        const cm = bootstrap.Modal.getInstance(cmEl);
                                        if (cm) cm.hide();
                                        cmEl.remove();
                                }
                                const select = document.getElementById('materias_curso_select');
                                if (select && select.value) {
                                        document.getElementById('cargarMateriasBtn').click();
                                }
                        })
                        .catch(err => { console.error(err); alert('Error creando materia'); });
                }

                // Editar materia (form loaded inside modal should have class .materia-edit-form)
                if (form && form.classList && form.classList.contains('materia-edit-form')){
                        e.preventDefault();
                        const action = form.action;
                        const formData = new FormData(form);
                        // Laravel expects PUT; send as POST with _method or use PUT
                        fetch(action, {
                                method: 'POST',
                                headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
                                body: formData
                        })
                        .then(res => {
                                if (res.redirected) window.location.href = res.url; // fallback
                                return res.text();
                        })
                        .then(() => {
                                // Close edit modal and refresh list
                                const emEl = document.getElementById('materiaEditModal');
                                if (emEl) {
                                        const em = bootstrap.Modal.getInstance(emEl);
                                        if (em) em.hide();
                                        emEl.remove();
                                }
                                const select = document.getElementById('materias_curso_select');
                                if (select && select.value) {
                                        document.getElementById('cargarMateriasBtn').click();
                                }
                        })
                        .catch(err => { console.error(err); alert('Error actualizando materia'); });
                }
        });

        // small helper to escape HTML
        function escapeHtml(str){
                if (!str) return '';
                return String(str).replace(/[&<>"']/g, function (s) {
                        return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[s];
                });
        }

});
</script>
@endsection
