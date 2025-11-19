@extends('layouts.app')

@section('content')
<div class="container py-4" data-estudiante-role="{{ $estudianteRoleId ?? '' }}">
    <div class="card shadow-sm rounded overflow-hidden">

        <!-- Encabezado oscuro -->
        <div class="bg-dark text-white p-3">
            <h2 class="fw-semibold mb-0">
                <i class="bi bi-bell-fill me-2 text-warning"></i> Notificaciones
            </h2>
            <p class="small mb-0 text-light">Consulta las notificaciones internas de la institución.</p>
        </div>

        <!-- Separador -->
        <hr class="m-0">

        <!-- Contenido -->
        <div class="p-4 bg-light">
            <!-- Formulario de envío de notificaciones (similar a mensajes) -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <i class="bi bi-bell-fill me-1"></i> Enviar Notificación
                </div>
                <div class="card-body">
                    <form action="{{ route('comunicacion.notificaciones.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="modo_notif" class="form-label fw-bold">Modo de envío</label>
                                <select name="modo" id="modo_notif" class="form-select" required>
                                    <option value="rol" selected>Por grupo</option>
                                    <option value="todos">Enviar a todos</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3" id="container-rol-notif">
                                <label for="rol_notif" class="form-label fw-bold">Grupo</label>
                                <select name="rol_id" id="rol_notif" class="form-select">
                                    <option value="">-- Seleccione grupo --</option>
                                    @foreach($roles as $rol)
                                        <option value="{{ $rol->id }}">{{ $rol->nombre }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">Se enviará la notificación a todos los usuarios de este grupo. O seleccione usuarios concretos abajo.</div>
                            </div>

                            <div class="col-md-6 mb-3" id="container-curso-notif" style="display:none;">
                                <label for="curso_notif" class="form-label fw-bold">Curso (si aplica)</label>
                                <select name="curso_id" id="curso_notif" class="form-select">
                                    <option value="">-- Seleccione curso --</option>
                                    @foreach($cursos as $curso)
                                        <option value="{{ $curso->id }}">{{ $curso->nombre }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">Si selecciona un curso, la notificación se enviará a los estudiantes matriculados y a sus acudientes (si existen). Sólo se mostrarán los estudiantes en la interfaz.</div>
                            </div>
                        </div>

                        <div class="row" id="container-usuarios-grupo-notif" style="display:none;">
                            <div class="col-12 mb-3">
                                <label for="usuarios_notif_search" class="form-label fw-bold">Usuarios del grupo (opcional)</label>
                                <div class="input-group mb-2">
                                    <input id="usuarios_notif_search" class="form-control" placeholder="Buscar por nombre, apellido o documento..." autocomplete="off">
                                    <button id="usuarios_notif_clear" type="button" class="btn btn-outline-secondary">Limpiar</button>
                                </div>
                                <ul id="usuarios_notif_results" class="list-group" style="max-height:220px; overflow:auto; display:none;"></ul>
                                <div class="form-text">Busque y agregue usuarios del grupo. Si no selecciona ninguno, la notificación se enviará a todo el grupo.</div>
                                <div id="selected-hidden-inputs-notif"><!-- hidden inputs para usuarios seleccionados --></div>
                            </div>

                            <div class="col-12 mb-2" id="selected-recipients-container-notif" style="display:none;">
                                <label class="form-label fw-bold">Destinatarios (seleccionados)</label>
                                <div id="selected-recipients-notif" class="d-flex flex-wrap gap-2">
                                    <!-- chips de usuarios seleccionados -->
                                </div>
                                <div class="form-text">Puede quitar un destinatario con la X si lo seleccionó por error.</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="titulo_notif" class="form-label fw-bold">Título</label>
                            <input type="text" name="titulo" id="titulo_notif" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="mensaje_notif" class="form-label fw-bold">Mensaje</label>
                            <textarea name="mensaje" id="mensaje_notif" rows="3" class="form-control" required></textarea>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" value="1" id="solo_acudiente_responde" name="solo_acudiente_responde">
                            <label class="form-check-label" for="solo_acudiente_responde">
                                Sólo el acudiente puede responder
                            </label>
                        </div>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-send-fill me-1"></i> Enviar Notificación
                        </button>
                    </form>
                </div>
            </div>

            <div class="row">
                @if(!empty($sentGroups) && $sentGroups->count())
                    <div class="col-12 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                                <div><i class="bi bi-send-fill me-2"></i> Notificaciones enviadas (grupos)</div>
                                <small class="text-light">Acciones sobre grupos enviados</small>
                            </div>
                            <div class="card-body">
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($sentGroups as $g)
                                        <div class="card p-2" style="min-width:220px;">
                                            <div class="fw-bold small">{{ Str::limit($g->titulo, 50) }}</div>
                                            <div class="small text-muted">{{ Str::limit($g->mensaje, 80) }}</div>
                                            <div class="small text-muted mt-1">{{ optional($g->created_at)->toDateTimeString() }}</div>
                                            <div class="mt-2 text-end">
                                                <a href="{{ route('comunicacion.notificaciones.grupo.respuestas', $g->group_key) }}" class="btn btn-sm btn-outline-primary">Ver respuestas</a>
                                                <form action="{{ route('comunicacion.notificaciones.grupo.eliminar', $g->group_key) }}" method="POST" style="display:inline-block; margin-left:6px;" onsubmit="return confirm('¿Eliminar este grupo de notificaciones para los destinatarios?')">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                                </form>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                @forelse($notificaciones as $notif)
                    <div class="col-md-4 mb-3">
                        <div class="card notif-card shadow-sm h-100 {{ $notif->leida ? 'border-secondary' : 'border-warning' }}" data-notif-id="{{ $notif->id }}">
                            <div class="card-header d-flex align-items-center {{ $notif->leida ? 'bg-secondary text-white' : 'bg-warning text-dark' }}">
                                <i class="bi bi-bell-fill me-2"></i>
                                <span class="fw-bold">{{ $notif->titulo }}</span>
                            </div>
                            <div class="card-body">
                                <p class="card-text">{{ Str::limit($notif->mensaje, 100) }}</p>
                                <span class="badge bg-light text-dark">
                                    📅 {{ $notif->fecha }}
                                </span>
                            </div>
                            <div class="card-footer text-end">
                                @if(!$notif->leida)
                                    <form action="{{ route('comunicacion.notificaciones.leer', $notif->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-success">
                                            <i class="bi bi-check-circle me-1"></i> Marcar como leída
                                        </button>
                                    </form>
                                @else
                                        <span class="text-muted"><i class="bi bi-eye-fill"></i> Leída</span>
                                @endif
                                {{-- Botón responder: sólo si el usuario es destinatario y tiene permiso según la notificación --}}
                                @php
                                    $canReply = false;
                                    if (auth()->check() && (int)auth()->id() === (int)$notif->usuario_id) {
                                        $roleName = optional(auth()->user()->role)->nombre ?? '';
                                        $isAcudiente = stripos($roleName, 'acudiente') !== false;

                                        // Si la notificación es un pago de matrícula, el acudiente no puede responder
                                        if ($notif->tipo === 'pago_matricula' && $isAcudiente) {
                                            $canReply = false;
                                        } else {
                                            if (empty($notif->solo_acudiente_responde)) {
                                                $canReply = true;
                                            } else {
                                                if ($isAcudiente) $canReply = true;
                                            }
                                        }
                                    }
                                @endphp
                                <button type="button" class="btn btn-sm btn-outline-primary mt-2 view-notif-btn" data-notif-id="{{ $notif->id }}">Ver</button>
                                @if($canReply)
                                    <button type="button" class="btn btn-sm btn-primary mt-2 open-reply-btn" data-notif-id="{{ $notif->id }}">Responder</button>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="alert alert-info text-center">
                            <i class="bi bi-info-circle"></i> No hay notificaciones disponibles
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function(){
        (function(){
            const modo = document.getElementById('modo_notif');
            const containerRol = document.getElementById('container-rol-notif');
            const rolSelect = document.getElementById('rol_notif');
            const containerCurso = document.getElementById('container-curso-notif');
            const cursoSelect = document.getElementById('curso_notif');
            const containerUsuariosGrupo = document.getElementById('container-usuarios-grupo-notif');
            const usuariosGrupoSearch = document.getElementById('usuarios_notif_search');
            const usuariosGrupoClear = document.getElementById('usuarios_notif_clear');
            const usuariosGrupoResults = document.getElementById('usuarios_notif_results');
            const hiddenInputsContainer = document.getElementById('selected-hidden-inputs-notif');

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
                    // si se deselecciona curso, limpiar lista
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

                // Comportamiento por defecto: buscar usuarios por rol
                if (q.length<1){ usuariosGrupoResults.style.display='none'; return; }
                fetch(`/comunicacion/usuarios-por-grupo/${rolId}?q=${encodeURIComponent(q)}`)
                    .then(r=>r.json()).then(json=>renderResults(json.data||[])).catch(()=>usuariosGrupoResults.style.display='none');
            }, 300);

            usuariosGrupoSearch.addEventListener('input', doSearch);
            usuariosGrupoClear.addEventListener('click', function(){ usuariosGrupoSearch.value=''; usuariosGrupoResults.innerHTML=''; usuariosGrupoResults.style.display='none'; usuariosGrupoSearch.focus(); });

            function addUserToSelected(u){ if (selectedUsers.has(String(u.id))) return; const userObj = { id: u.id, name: u.name, rol_id: u.rol_id ? parseInt(u.rol_id,10) : (rolSelect.value ? parseInt(rolSelect.value,10) : null) }; selectedUsers.set(String(u.id), userObj); renderSelected(); }
            function removeUserFromSelected(id){ selectedUsers.delete(String(id)); renderSelected(); }

            function renderSelected(){
                const container = document.getElementById('selected-recipients-notif');
                const containerWrapper = document.getElementById('selected-recipients-container-notif');
                container.innerHTML = ''; hiddenInputsContainer.innerHTML = '';
                const arr = Array.from(selectedUsers.values()); if (arr.length===0){ containerWrapper.style.display='none'; return; }
                function colorFromGrupoId(id){ if (!id) return null; const hue=(id*53)%360; return `hsl(${hue}, 65%, 45%)`; }
                arr.forEach(u=>{
                    const bg = colorFromGrupoId(u.rol_id)||'#fd7e14'; const chip = document.createElement('div'); chip.className='d-inline-flex align-items-center rounded-pill text-white'; chip.style.background=bg; chip.style.padding='0.25rem 0.6rem'; chip.style.fontSize='0.85rem'; chip.style.marginRight='0.35rem'; chip.style.marginBottom='0.35rem';
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


<!-- Modal flotante para detalle de notificación -->
<div class="modal fade" id="notifModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="notifModalTitle"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body" id="notifModalBody"></div>
            <div class="modal-footer" id="notifModalFooter">
                <div style="flex:1 1 auto; text-align:left;">
                        <small class="text-muted" id="notifModalFecha"></small>
                </div>
                <div id="notifReplyArea" style="display:none; width:100%;">
                        <form id="notifReplyForm" class="row gx-2">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                <div class="col-12 mb-2">
                                        <input type="text" name="asunto" id="notifReplyAsunto" class="form-control form-control-sm" placeholder="Asunto" required>
                                </div>
                                <div class="col-12 mb-2">
                                        <textarea name="contenido" id="notifReplyContenido" class="form-control form-control-sm" rows="3" placeholder="Escribe tu respuesta..." required></textarea>
                                </div>
                                <div class="col-auto">
                                        <button type="button" id="notifReplySubmit" class="btn btn-primary btn-sm">Enviar</button>
                                </div>
                                <div class="col-auto">
                                        <button type="button" id="notifReplyCancel" class="btn btn-secondary btn-sm">Cancelar</button>
                                </div>
                        </form>
                </div>
                <button type="button" id="notifReplyBtn" class="btn btn-primary" style="display:none; margin-right:0.5rem;">Responder</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    function showNotification(id, card){
        if (!id) return Promise.reject(new Error('id missing'));
        return fetch(`/comunicacion/notificaciones/${id}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            credentials: 'same-origin'
        })
            .then(async r => {
                if (!r.ok) {
                    // intentar leer mensaje JSON devuelto por el servidor
                    let text = await r.text();
                    try {
                        const obj = JSON.parse(text);
                        const msg = obj.message || obj.error || obj.msg || text;
                        throw new Error(msg || ('HTTP ' + r.status));
                    } catch (e) {
                        throw new Error(text || ('HTTP ' + r.status));
                    }
                }
                return r.json();
            })
                .then(json => {
                const title = json.titulo || '';
                const body = json.mensaje || '';
                const fecha = json.fecha || '';
                const canReply = json.canReply || false;
                const replyUrl = json.replyUrl || null;

                document.getElementById('notifModalTitle').textContent = title;
                document.getElementById('notifModalBody').textContent = body;
                document.getElementById('notifModalFecha').textContent = fecha;

                    const replyBtn = document.getElementById('notifReplyBtn');
                    if (canReply) {
                        replyBtn.style.display = '';
                        // store replyUrl for fallback or reference (not used for navigation)
                        replyBtn.dataset.replyUrl = replyUrl || '';
                    } else {
                        replyBtn.style.display = 'none';
                        replyBtn.dataset.replyUrl = '';
                    }

                    // guardar id de notificación en el modal para usar al enviar respuesta
                    try {
                        var modalEl = document.getElementById('notifModal');
                        modalEl.setAttribute('data-current-notif', json.id);
                    } catch (e) {}

                // mostrar modal usando Bootstrap (si está disponible)
                try {
                    var modalEl = document.getElementById('notifModal');
                    var bsModal = new bootstrap.Modal(modalEl);
                    bsModal.show();
                } catch (err) {
                    alert(title + '\n\n' + body);
                }

                if (card && card.classList) {
                    card.classList.remove('border-warning');
                    card.classList.add('border-secondary');
                }
            })
            .catch(err => {
                console.error(err);
                alert(err.message || 'No se pudo cargar la notificación');
                throw err;
            });
    }

    // Conectar handlers: tarjeta completa y botón 'Ver'
    document.querySelectorAll('.notif-card').forEach(function(card){
        card.addEventListener('click', function(e){
            // evitar colisiones con botones dentro de la tarjeta
            if (e.target && (e.target.tagName === 'BUTTON' || e.target.tagName === 'A' || e.target.closest('form'))) return;
            const id = card.dataset.notifId;
            showNotification(id, card);
        });
    });

    document.querySelectorAll('.view-notif-btn').forEach(function(btn){
        btn.addEventListener('click', function(e){
            e.stopPropagation();
            e.preventDefault();
            const id = btn.dataset.notifId;
            const card = btn.closest('.notif-card');
            showNotification(id, card);
        });
    });

    document.querySelectorAll('.open-reply-btn').forEach(function(btn){
        btn.addEventListener('click', function(e){
            e.stopPropagation();
            e.preventDefault();
            const id = btn.dataset.notifId;
            const card = btn.closest('.notif-card');
            // cargar notificación y al finalizar abrir el área de respuesta
            showNotification(id, card).then(() => {
                const replyBtn = document.getElementById('notifReplyBtn');
                if (replyBtn) replyBtn.click();
            }).catch(()=>{});
        });
    });

    // Handlers for inline reply in modal
    function resetReplyArea(){
        document.getElementById('notifReplyArea').style.display = 'none';
        document.getElementById('notifReplyBtn').style.display = 'none';
        document.getElementById('notifReplyAsunto').value = '';
        document.getElementById('notifReplyContenido').value = '';
    }

    document.getElementById('notifReplyCancel').addEventListener('click', function(){
        resetReplyArea();
    });

    // Mostrar área inline al pulsar el botón 'Responder' (no navegar)
    document.getElementById('notifReplyBtn').addEventListener('click', function(e){
        e.preventDefault();
        const area = document.getElementById('notifReplyArea');
        const replyBtn = document.getElementById('notifReplyBtn');
        if (!area || !replyBtn) return;
        area.style.display = '';
        replyBtn.style.display = 'none';
        // prefill asunto
        const titulo = document.getElementById('notifModalTitle').textContent || '';
        const asu = titulo ? ('Re: ' + titulo) : '';
        document.getElementById('notifReplyAsunto').value = asu;
        document.getElementById('notifReplyContenido').focus();
    });

    document.getElementById('notifReplySubmit').addEventListener('click', function(){
        // obtener id del notificación abierta
        const modalEl = document.getElementById('notifModal');
        const id = modalEl.getAttribute('data-current-notif');
        if (!id) { alert('ID de notificación no disponible'); return; }

        const asunto = document.getElementById('notifReplyAsunto').value.trim();
        const contenido = document.getElementById('notifReplyContenido').value.trim();
        if (!asunto || !contenido) { alert('Complete asunto y mensaje'); return; }

        const token = document.querySelector('#notifReplyForm input[name="_token"]').value;

        fetch(`/comunicacion/notificaciones/${id}/responder`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({ asunto: asunto, contenido: contenido })
        }).then(async res => {
            if (!res.ok) {
                let text = await res.text();
                try { const obj = JSON.parse(text); throw new Error(obj.message || obj.error || text); } catch(e){ throw new Error(text || ('HTTP ' + res.status)); }
            }
            return res.json();
        }).then(json => {
            // mostrar éxito, cerrar modal
            try { var modalEl = document.getElementById('notifModal'); var bsModal = bootstrap.Modal.getInstance(modalEl); if (bsModal) bsModal.hide(); } catch(e){}
            alert(json.message || 'Respuesta enviada');
            resetReplyArea();
        }).catch(err => {
            console.error(err);
            alert('Error al enviar la respuesta: ' + (err.message || err));
        });
    });
});
</script>
@endpush
