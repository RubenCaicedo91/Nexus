@extends('layouts.app')

@section('content')
<div class="container py-4" data-estudiante-role="{{ $estudianteRoleId ?? '' }}">
    <div class="card shadow-sm rounded overflow-hidden">

        <!-- Encabezado oscuro -->
        <div class="bg-dark text-white p-3">
            <h2 class="fw-semibold mb-0">
                <i class="bi bi-file-earmark-text me-2 text-info"></i> Circulares Institucionales
            </h2>
            <p class="small mb-0 text-light">Listado actualizado de circulares emitidas por la institución.</p>
        </div>

        <!-- Separador -->
        <hr class="m-0">

        <!-- Contenido -->
        <div class="p-4 bg-light">
            <!-- Formulario para publicar nueva circular (similar a notificaciones) -->
            @if(!empty($canSend) && $canSend)
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-info text-white">
                    <i class="bi bi-file-earmark-plus me-1"></i> Publicar Circular Institucional
                </div>
                <div class="card-body">
                    <form action="{{ route('comunicacion.circulares.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="modo_circ" class="form-label fw-bold">Modo de envío</label>
                                <select name="modo" id="modo_circ" class="form-select" required>
                                    <option value="rol" selected>Por grupo</option>
                                    <option value="todos">Enviar a todos</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3" id="container-rol-circ">
                                <label for="rol_circ" class="form-label fw-bold">Grupo</label>
                                <select name="rol_id" id="rol_circ" class="form-select">
                                    <option value="">-- Seleccione grupo --</option>
                                    @foreach($roles as $rol)
                                        <option value="{{ $rol->id }}">{{ $rol->nombre }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">La circular se publicará para todos los usuarios del grupo, o seleccione usuarios concretos abajo.</div>
                            </div>

                            <div class="col-md-6 mb-3" id="container-curso-circ" style="display:none;">
                                <label for="curso_circ" class="form-label fw-bold">Curso (si aplica)</label>
                                <select name="curso_id" id="curso_circ" class="form-select">
                                    <option value="">-- Seleccione curso --</option>
                                    @foreach($cursos as $curso)
                                        <option value="{{ $curso->id }}">{{ $curso->nombre }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">Si selecciona un curso, podrá buscar los estudiantes matriculados para seleccionar destinatarios y sus acudientes.</div>
                            </div>
                        </div>

                        <div class="row" id="container-usuarios-grupo-circ" style="display:none;">
                            <div class="col-12 mb-3">
                                <label for="usuarios_circ_search" class="form-label fw-bold">Usuarios del grupo (opcional)</label>
                                <div class="input-group mb-2">
                                    <input id="usuarios_circ_search" class="form-control" placeholder="Buscar por nombre, apellido o documento..." autocomplete="off">
                                    <button id="usuarios_circ_clear" type="button" class="btn btn-outline-secondary">Limpiar</button>
                                </div>
                                <ul id="usuarios_circ_results" class="list-group" style="max-height:220px; overflow:auto; display:none;"></ul>
                                <div class="form-text">Busque y agregue usuarios del grupo. Si no selecciona ninguno, la circular se enviará a todo el grupo.</div>
                                <div id="selected-hidden-inputs-circ"><!-- hidden inputs para usuarios seleccionados --></div>
                            </div>

                            <div class="col-12 mb-2" id="selected-recipients-container-circ" style="display:none;">
                                <label class="form-label fw-bold">Destinatarios (seleccionados)</label>
                                <div id="selected-recipients-circ" class="d-flex flex-wrap gap-2">
                                    <!-- chips de usuarios seleccionados -->
                                </div>
                                <div class="form-text">Puede quitar un destinatario con la X si lo seleccionó por error.</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="titulo" class="form-label fw-bold">Título</label>
                            <input type="text" name="titulo" id="titulo" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="contenido" class="form-label fw-bold">Contenido</label>
                            <textarea name="contenido" id="contenido" rows="4" class="form-control" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="fecha_publicacion" class="form-label fw-bold">Fecha de publicación</label>
                            <input type="date" name="fecha_publicacion" id="fecha_publicacion" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="archivo" class="form-label">Archivo (opcional)</label>
                            <input type="file" name="archivo" id="archivo" class="form-control">
                        </div>
                        <button type="submit" class="btn btn-info">
                            <i class="bi bi-send-fill me-1"></i> Publicar Circular
                        </button>
                    </form>
                </div>
            </div>
            @else
                <div class="alert alert-secondary">No tiene permisos para publicar circulares.</div>
            @endif

            @if(!empty($canSend) && $canSend)
                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <div class="card shadow-sm">
                            <div class="card-header bg-light">Tus envíos</div>
                            <div class="card-body">
                                @if($myCirculares->isEmpty())
                                    <div class="small text-muted">Aún no ha publicado circulares.</div>
                                @else
                                    <ul class="list-group list-group-flush">
                                        @foreach($myCirculares as $m)
                                            <li class="list-group-item small d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong>{{ Str::limit($m->titulo, 60) }}</strong>
                                                    <div class="text-muted small">{{ optional($m->created_at)->format('d/m/Y H:i') }}</div>
                                                </div>
                                                <div>
                                                    @if($m->archivo)
                                                        <button type="button" class="btn btn-sm btn-outline-primary view-circ-btn" data-file-url="{{ route('comunicacion.circulares.archivo', $m->id) }}">Ver</button>
                                                    @endif
                                                    @if(!empty($canDelete) && $canDelete)
                                                        <form action="{{ route('comunicacion.circulares.eliminar', $m->id) }}" method="POST" style="display:inline-block; margin-left:6px;" onsubmit="return confirm('¿Confirma que desea eliminar esta circular? Esta acción eliminará también el archivo almacenado.')">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="card shadow-sm">
                            <div class="card-header bg-light">Envíos de otros remitentes autorizados</div>
                            <div class="card-body">
                                @if($othersCirculares->isEmpty())
                                    <div class="small text-muted">No hay envíos recientes de otros remitentes autorizados.</div>
                                @else
                                    <ul class="list-group list-group-flush">
                                        @foreach($othersCirculares as $o)
                                            <li class="list-group-item small d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong>{{ Str::limit($o->titulo, 60) }}</strong>
                                                    <div class="text-muted small">{{ optional($o->created_at)->format('d/m/Y H:i') }} — <em>{{ optional($o->creador)->name }}</em></div>
                                                </div>
                                                <div>
                                                    @if($o->archivo)
                                                        <button type="button" class="btn btn-sm btn-outline-primary view-circ-btn" data-file-url="{{ route('comunicacion.circulares.archivo', $o->id) }}">Ver</button>
                                                    @endif
                                                    @if(!empty($canDelete) && $canDelete)
                                                        <form action="{{ route('comunicacion.circulares.eliminar', $o->id) }}" method="POST" style="display:inline-block; margin-left:6px;" onsubmit="return confirm('¿Confirma que desea eliminar esta circular? Esta acción eliminará también el archivo almacenado.')">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if($circulares->isEmpty())
                <div class="alert alert-info text-center">
                    No hay circulares registradas en el sistema.
                </div>
            @else
                <table class="table table-hover table-striped align-middle">
                    <thead class="table-secondary">
                        <tr>
                            <th>Título</th>
                            <th>Contenido</th>
                            <th>Fecha</th>
                            <th>Archivo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($circulares as $circ)
                            <tr>
                                <td>{{ $circ->titulo }}</td>
                                <td>{{ Str::limit($circ->contenido, 80) }}</td>
                                <td>{{ \Carbon\Carbon::parse($circ->fecha_publicacion)->format('d/m/Y') }}</td>
                                <td>
                                        @if($circ->archivo)
                                            <button type="button" class="btn btn-sm btn-outline-primary view-circ-btn" data-file-url="{{ route('comunicacion.circulares.archivo', $circ->id) }}">Ver</button>
                                                                                    <a href="{{ route('comunicacion.circulares.archivo', $circ->id) }}" target="_blank" class="btn btn-sm btn-outline-info ms-1">
                                                <i class="bi bi-download me-1"></i> Descargar
                                            </a>
                                            @if(!empty($canDelete) && $canDelete)
                                                <form action="{{ route('comunicacion.circulares.eliminar', $circ->id) }}" method="POST" style="display:inline-block; margin-left:6px;" onsubmit="return confirm('¿Confirma que desea eliminar esta circular? Esta acción eliminará también el archivo almacenado.')">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                                </form>
                                            @endif
                                        @else
                                            <span class="text-muted">Sin archivo</span>
                                        @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
@endsection

<!-- Modal para visualizar documento de circular -->
<div class="modal fade" id="circViewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Visualizador de Circular</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body" style="min-height:500px;">
        <iframe id="circViewerIframe" src="" frameborder="0" style="width:100%; height:70vh;"></iframe>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

        @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function(){
                (function(){
                    const modo = document.getElementById('modo_circ');
                    const containerRol = document.getElementById('container-rol-circ');
                    const rolSelect = document.getElementById('rol_circ');
                    const containerCurso = document.getElementById('container-curso-circ');
                    const cursoSelect = document.getElementById('curso_circ');
                    const containerUsuariosGrupo = document.getElementById('container-usuarios-grupo-circ');
                    const usuariosGrupoSearch = document.getElementById('usuarios_circ_search');
                    const usuariosGrupoClear = document.getElementById('usuarios_circ_clear');
                    const usuariosGrupoResults = document.getElementById('usuarios_circ_results');
                    const hiddenInputsContainer = document.getElementById('selected-hidden-inputs-circ');

                    // leer id del rol Estudiante desde el atributo data del contenedor (evita directivas Blade en JS)
                    const containerRoot = document.querySelector('[data-estudiante-role]');
                    const estudianteRoleIdRaw = containerRoot ? containerRoot.dataset.estudianteRole : '';
                    const estudianteRoleId = estudianteRoleIdRaw ? Number(estudianteRoleIdRaw) : null;

                    function toggleFields() {
                        const val = modo.value;
                        if (val === 'rol') {
                            containerRol.style.display = '';
                            rolSelect.disabled = false;
                            // Mostrar selector de curso sólo si el rol seleccionado es Estudiante
                            if (estudianteRoleId && String(rolSelect.value) === String(estudianteRoleId)) {
                                containerCurso.style.display = '';
                            } else {
                                containerCurso.style.display = 'none';
                            }
                            if (rolSelect.value) containerUsuariosGrupo.style.display = '';
                        } else {
                            containerRol.style.display = 'none';
                            rolSelect.disabled = true;
                            containerUsuariosGrupo.style.display = 'none';
                            containerCurso.style.display = 'none';
                        }
                    }

                    modo.addEventListener('change', toggleFields);

                    rolSelect.addEventListener('change', function(){
                        const rolId = this.value;
                        usuariosGrupoSearch.value = '';
                        usuariosGrupoResults.innerHTML = '';
                        if (!rolId) {
                            containerUsuariosGrupo.style.display = 'none';
                            containerCurso.style.display = 'none';
                            return;
                        }
                        // Si el rol es Estudiante, mostrar selector de curso
                        if (estudianteRoleId && String(rolId) === String(estudianteRoleId)) {
                            containerCurso.style.display = '';
                        } else {
                            containerCurso.style.display = 'none';
                        }
                        containerUsuariosGrupo.style.display = '';
                        usuariosGrupoSearch.focus();
                    });

                    cursoSelect.addEventListener('change', function(){
                        usuariosGrupoSearch.value = '';
                        usuariosGrupoResults.innerHTML = '';
                        if (!this.value) {
                            usuariosGrupoResults.style.display = 'none';
                            return;
                        }
                        // Cargar lista inicial de estudiantes del curso (sin filtro)
                        doSearch();
                    });

                    function debounce(fn, delay){ let t; return function(...args){ clearTimeout(t); t = setTimeout(()=>fn.apply(this,args), delay); }; }
                    const selectedUsers = new Map();

                    function renderResults(items){
                        usuariosGrupoResults.innerHTML = '';
                        if (!items || items.length === 0) { usuariosGrupoResults.style.display = 'none'; return; }
                        usuariosGrupoResults.style.display = '';
                        items.forEach(u => {
                            const li = document.createElement('li');
                            li.className = 'list-group-item d-flex justify-content-between align-items-start small';
                            const left = document.createElement('div'); left.className='ms-2 me-auto'; left.textContent = u.name + (u.document_number ? ' — ' + u.document_number : '');
                            const btn = document.createElement('button'); btn.type='button'; btn.className='btn btn-sm btn-outline-primary'; btn.textContent = selectedUsers.has(String(u.id)) ? 'Seleccionado' : 'Agregar'; if (selectedUsers.has(String(u.id))) btn.disabled = true;
                            btn.addEventListener('click', function(e){ e.stopPropagation(); addUserToSelected(u); btn.disabled=true; btn.textContent='Seleccionado'; });
                            li.appendChild(left); li.appendChild(btn); usuariosGrupoResults.appendChild(li);
                        });
                    }

                    const doSearch = debounce(function(){
                        const q = usuariosGrupoSearch.value.trim(); const rolId = rolSelect.value; usuariosGrupoResults.innerHTML=''; if (!rolId) return;
                        // Si es rol Estudiante y hay curso seleccionado, buscar por curso
                        if (estudianteRoleId && String(rolId) === String(estudianteRoleId) && cursoSelect && cursoSelect.value) {
                            const cursoId = cursoSelect.value;
                            fetch(`/comunicacion/estudiantes-por-curso/${cursoId}?q=${encodeURIComponent(q)}`)
                                .then(r=>r.json()).then(json=>renderResults(json.data||[])).catch(()=>usuariosGrupoResults.style.display='none');
                            return;
                        }

                        if (q.length<1){ usuariosGrupoResults.style.display='none'; return; }
                        fetch(`/comunicacion/usuarios-por-grupo/${rolId}?q=${encodeURIComponent(q)}`)
                            .then(r=>r.json()).then(json=>renderResults(json.data||[])).catch(()=>usuariosGrupoResults.style.display='none');
                    }, 300);

                    usuariosGrupoSearch.addEventListener('input', doSearch);
                    usuariosGrupoClear.addEventListener('click', function(){ usuariosGrupoSearch.value=''; usuariosGrupoResults.innerHTML=''; usuariosGrupoResults.style.display='none'; usuariosGrupoSearch.focus(); });

                    function addUserToSelected(u){ if (selectedUsers.has(String(u.id))) return; const userObj = { id: u.id, name: u.name, rol_id: u.rol_id ? parseInt(u.rol_id,10) : (rolSelect.value ? parseInt(rolSelect.value,10) : null) }; selectedUsers.set(String(u.id), userObj); renderSelected(); }
                    function removeUserFromSelected(id){ selectedUsers.delete(String(id)); renderSelected(); }

                    function renderSelected(){
                        const container = document.getElementById('selected-recipients-circ');
                        const containerWrapper = document.getElementById('selected-recipients-container-circ');
                        container.innerHTML = ''; hiddenInputsContainer.innerHTML = '';
                        const arr = Array.from(selectedUsers.values()); if (arr.length===0){ containerWrapper.style.display='none'; return; }
                        function colorFromGrupoId(id){ if (!id) return null; const hue=(id*53)%360; return `hsl(${hue} 65% 45%)`; }
                        arr.forEach(u=>{
                            const bg = colorFromGrupoId(u.rol_id)||'#0dcaf0'; const chip = document.createElement('div'); chip.className='d-inline-flex align-items-center rounded-pill text-white'; chip.style.background=bg; chip.style.padding='0.25rem 0.6rem'; chip.style.fontSize='0.85rem'; chip.style.marginRight='0.35rem'; chip.style.marginBottom='0.35rem';
                            const span=document.createElement('span'); span.textContent=u.name; chip.appendChild(span);
                            const btn=document.createElement('button'); btn.type='button'; btn.className='ms-2'; btn.setAttribute('aria-label','Eliminar'); btn.style.border='none'; btn.style.background='transparent'; btn.style.color='rgba(255,255,255,0.9)'; btn.style.fontSize='1rem'; btn.style.lineHeight='1'; btn.style.padding='0 0 0 0.35rem'; btn.textContent='✕'; btn.addEventListener('click', function(){ removeUserFromSelected(u.id); });
                            chip.appendChild(btn); container.appendChild(chip);
                            const hi=document.createElement('input'); hi.type='hidden'; hi.name='usuarios[]'; hi.value=u.id; hiddenInputsContainer.appendChild(hi);
                        });
                        containerWrapper.style.display='';
                    }

                    // inicial
                    toggleFields();
                })();
            });
        </script>
        @endpush
        @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function(){
                document.querySelectorAll('.view-circ-btn').forEach(function(btn){
                    btn.addEventListener('click', function(){
                        const url = btn.dataset.fileUrl;
                        if (!url) return alert('Archivo no disponible');
                        const iframe = document.getElementById('circViewerIframe');
                        iframe.src = url;
                        try { var modalEl = document.getElementById('circViewModal'); var bsModal = new bootstrap.Modal(modalEl); bsModal.show(); } catch(e){ window.open(url, '_blank'); }
                    });
                });
            });
        </script>
        @endpush
