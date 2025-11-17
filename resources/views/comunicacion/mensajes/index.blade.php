@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm rounded overflow-hidden">

        <!-- Encabezado oscuro -->
        <div class="bg-dark text-white p-3">
            <h2 class="fw-semibold mb-0">
                <i class="bi bi-envelope-fill me-2 text-info"></i> Mensajes Internos
            </h2>
            <p class="small mb-0 text-light">Envía y consulta mensajes dentro de la institución.</p>
        </div>

        <!-- Separador -->
        <hr class="m-0">

        <!-- Contenido -->
        <div class="p-4 bg-light">
            
            <!-- Formulario de envío -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <div><i class="bi bi-send-check-fill me-1"></i> Enviar Mensaje</div>
                </div>
                <div class="card-body">
                    <form action="{{ route('comunicacion.mensajes.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="modo" class="form-label fw-bold">Modo de envío</label>
                                <select name="modo" id="modo" class="form-select" required>
                                    <option value="rol" selected>Por grupo</option>
                                    <option value="todos">Enviar a todos</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3" id="container-rol">
                                <label for="rol_id" class="form-label fw-bold">Grupo</label>
                                <select name="rol_id" id="rol_id" class="form-select">
                                    <option value="">-- Seleccione grupo --</option>
                                    @foreach($roles as $rol)
                                        <option value="{{ $rol->id }}">{{ $rol->nombre }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">Se enviará el mensaje a todos los usuarios de este grupo. O seleccione usuarios concretos abajo.</div>
                            </div>
                        </div>
                        <div class="row" id="container-usuarios-grupo" style="display:none;">
                            <div class="col-12 mb-3">
                                <label for="usuarios_grupo_search" class="form-label fw-bold">Usuarios del grupo (opcional)</label>
                                <div class="input-group mb-2">
                                    <input id="usuarios_grupo_search" class="form-control" placeholder="Buscar por nombre, apellido o documento..." autocomplete="off">
                                    <button id="usuarios_grupo_clear" type="button" class="btn btn-outline-secondary">Limpiar</button>
                                </div>
                                <ul id="usuarios_grupo_results" class="list-group" style="max-height:220px; overflow:auto; display:none;"></ul>
                                <div class="form-text">Busque y seleccione usuarios del grupo. Si no selecciona ninguno, el mensaje se enviará a todo el grupo.</div>
                                <div id="selected-hidden-inputs"><!-- hidden inputs para usuarios seleccionados --></div>
                            </div>

                            <div class="col-12 mb-2" id="selected-recipients-container" style="display:none;">
                                <label class="form-label fw-bold">Destinatarios (seleccionados)</label>
                                <div id="selected-recipients" class="d-flex flex-wrap gap-2">
                                    <!-- chips de usuarios seleccionados -->
                                </div>
                                <div class="form-text">Puede quitar un destinatario con la X si lo seleccionó por error.</div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="asunto" class="form-label fw-bold">Asunto</label>
                            <input type="text" name="asunto" id="asunto" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="contenido" class="form-label fw-bold">Contenido</label>
                            <textarea name="contenido" id="contenido" rows="4" class="form-control" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send-fill me-1"></i> Enviar
                        </button>
                    </form>
                </div>
            </div>

            <!-- Bandeja de entrada -->
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white d-flex align-items-center">
                    <div class="btn-group btn-group-sm" role="group" aria-label="Bandejas">
                        <button id="btn-inbox" type="button" class="btn btn-sm btn-outline-light active">Bandeja de Entrada</button>
                        <button id="btn-sent" type="button" class="btn btn-sm btn-outline-light">Buzón de Salida</button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="panel-inbox">
                        <table class="table table-hover table-striped align-middle">
                        <thead class="table-secondary">
                            <tr>
                                <th>Remitente</th>
                                <th>Asunto</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($mensajes as $msg)
                                <tr data-id="{{ $msg->id }}">
                                    <td>{{ optional($msg->remitente)->name ?? $msg->remitente_id }}</td>
                                    <td>{{ $msg->asunto }}</td>
                                    <td>{{ $msg->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        @if($msg->leido)
                                            <span class="badge bg-success">Leído</span>
                                        @else
                                            <span class="badge bg-warning text-dark">No leído</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button data-id="{{ $msg->id }}" class="btn btn-sm btn-outline-primary open-message-btn">Ver</button>
                                        <button data-id="{{ $msg->id }}" class="btn btn-sm btn-danger delete-message-btn ms-2">Eliminar</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted">No hay mensajes</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>

                    <div id="panel-sent" style="display:none;">
                        <table class="table table-hover table-striped align-middle">
                            <thead class="table-secondary">
                                <tr>
                                    <th>Destinatario</th>
                                    <th>Asunto</th>
                                    <th>Fecha</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($mensajesEnviados as $msg)
                                    <tr data-id="{{ $msg->id }}">
                                        <td>{{ optional($msg->destinatario)->name ?? $msg->destinatario_id }}</td>
                                        <td>{{ $msg->asunto }}</td>
                                        <td>{{ $msg->created_at->format('d/m/Y H:i') }}</td>
                                        <td>
                                            <button data-id="{{ $msg->id }}" class="btn btn-sm btn-outline-primary open-message-btn">Ver</button>
                                            <button data-id="{{ $msg->id }}" class="btn btn-sm btn-danger delete-message-btn ms-2">Eliminar</button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted">No hay mensajes enviados</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function(){
        // Tabs client-side para alternar entre Bandeja/Enviados sin navegación
        const btnInbox = document.getElementById('btn-inbox');
        const btnSent = document.getElementById('btn-sent');
        const panelInbox = document.getElementById('panel-inbox');
        const panelSent = document.getElementById('panel-sent');

        function activateInbox(){
            if (btnInbox) btnInbox.classList.add('active');
            if (btnSent) btnSent.classList.remove('active');
            if (panelInbox) panelInbox.style.display = '';
            if (panelSent) panelSent.style.display = 'none';
        }
        function activateSent(){
            if (btnInbox) btnInbox.classList.remove('active');
            if (btnSent) btnSent.classList.add('active');
            if (panelInbox) panelInbox.style.display = 'none';
            if (panelSent) panelSent.style.display = '';
        }

        if (btnInbox) btnInbox.addEventListener('click', function(e){ e.preventDefault(); activateInbox(); });
        if (btnSent) btnSent.addEventListener('click', function(e){ e.preventDefault(); activateSent(); });

        // inicial: mostrar inbox
        activateInbox();
        

        const modo = document.getElementById('modo');
        const containerRol = document.getElementById('container-rol');
        const rolSelect = document.getElementById('rol_id');
        const containerUsuariosGrupo = document.getElementById('container-usuarios-grupo');
        const usuariosGrupoSearch = document.getElementById('usuarios_grupo_search');
        const usuariosGrupoClear = document.getElementById('usuarios_grupo_clear');
        const usuariosGrupoResults = document.getElementById('usuarios_grupo_results');
        const hiddenInputsContainer = document.getElementById('selected-hidden-inputs');

        function toggleFields() {
            const val = modo.value;
            if (val === 'rol') {
                containerRol.style.display = '';
                // Mostrar usuarios del grupo solo si hay un rol seleccionado
                if (rolSelect.value) containerUsuariosGrupo.style.display = '';
            } else { // todos
                containerRol.style.display = 'none';
                containerUsuariosGrupo.style.display = 'none';
            }
        }

        modo.addEventListener('change', toggleFields);

        // Cuando cambia el rol: limpiar búsqueda y selecciones
        rolSelect.addEventListener('change', function(){
            const rolId = this.value;
            // limpiar búsqueda y resultados pero conservar los destinatarios ya seleccionados
            usuariosGrupoSearch.value = '';
            usuariosGrupoResults.innerHTML = '';

            if (!rolId) {
                containerUsuariosGrupo.style.display = 'none';
                return;
            }

            // mostrar el buscador (la búsqueda real es por input)
            containerUsuariosGrupo.style.display = '';
            usuariosGrupoSearch.focus();
        });

        // Debounce helper
        function debounce(fn, delay){
            let t;
            return function(...args){
                clearTimeout(t);
                t = setTimeout(()=>fn.apply(this,args), delay);
            };
        }

        // Map de usuarios seleccionados: id -> {id,name}
        const selectedUsers = new Map();

        function renderResults(items){
            usuariosGrupoResults.innerHTML = '';
            if (!items || items.length === 0) {
                usuariosGrupoResults.style.display = 'none';
                return;
            }
            usuariosGrupoResults.style.display = '';
            items.forEach(u => {
                const li = document.createElement('li');
                li.className = 'list-group-item d-flex justify-content-between align-items-start small';
                li.style.cursor = 'pointer';

                const left = document.createElement('div');
                left.className = 'ms-2 me-auto';
                left.textContent = u.name + (u.document_number ? ' — ' + u.document_number : '');

                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-sm btn-outline-primary';
                btn.textContent = selectedUsers.has(String(u.id)) ? 'Seleccionado' : 'Agregar';
                if (selectedUsers.has(String(u.id))) btn.disabled = true;
                btn.addEventListener('click', function(e){
                    e.stopPropagation();
                    addUserToSelected(u);
                    btn.disabled = true;
                    btn.textContent = 'Seleccionado';
                });

                li.appendChild(left);
                li.appendChild(btn);
                usuariosGrupoResults.appendChild(li);
            });
        }

        // Buscar usuarios dentro del grupo
        const doSearch = debounce(function(){
            const q = usuariosGrupoSearch.value.trim();
            const rolId = rolSelect.value;
            usuariosGrupoResults.innerHTML = '';
            if (!rolId) return;
            if (q.length < 1) { usuariosGrupoResults.style.display = 'none'; return; }

            fetch(`/comunicacion/usuarios-por-grupo/${rolId}?q=${encodeURIComponent(q)}`)
                .then(r => r.json())
                .then(json => renderResults(json.data || []))
                .catch(err => { console.error(err); usuariosGrupoResults.style.display = 'none'; });
        }, 300);

        usuariosGrupoSearch.addEventListener('input', doSearch);
        usuariosGrupoClear.addEventListener('click', function(){ usuariosGrupoSearch.value=''; usuariosGrupoResults.innerHTML=''; usuariosGrupoResults.style.display='none'; usuariosGrupoSearch.focus(); });

        function addUserToSelected(u){
            if (selectedUsers.has(String(u.id))) return;
            // almacenar también el rol del usuario (puede venir en la respuesta o usar el rol seleccionado)
            const userObj = { id: u.id, name: u.name, rol_id: u.rol_id ? parseInt(u.rol_id,10) : (rolSelect.value ? parseInt(rolSelect.value,10) : null) };
            selectedUsers.set(String(u.id), userObj);
            renderSelected();
        }

        function removeUserFromSelected(id){
            selectedUsers.delete(String(id));
            renderSelected();
        }

        function renderSelected(){
            const container = document.getElementById('selected-recipients');
            const containerWrapper = document.getElementById('selected-recipients-container');
            container.innerHTML = '';
            hiddenInputsContainer.innerHTML = '';

            const arr = Array.from(selectedUsers.values());
            if (arr.length === 0) { containerWrapper.style.display = 'none'; return; }

            function colorFromGrupoId(id) { if (!id) return null; const hue = (id * 53) % 360; return `hsl(${hue} 65% 45%)`; }

            arr.forEach(u => {
                const bgColor = colorFromGrupoId(u.rol_id) || '#0d6efd';
                const chip = document.createElement('div');
                chip.className = 'd-inline-flex align-items-center rounded-pill text-white';
                chip.style.background = bgColor;
                chip.style.padding = '0.25rem 0.6rem';
                chip.style.fontSize = '0.85rem';
                chip.style.marginRight = '0.35rem';
                chip.style.marginBottom = '0.35rem';

                const span = document.createElement('span');
                span.textContent = u.name;
                chip.appendChild(span);

                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'ms-2';
                btn.setAttribute('aria-label', 'Eliminar');
                btn.style.border = 'none';
                btn.style.background = 'transparent';
                btn.style.color = 'rgba(255,255,255,0.9)';
                btn.style.fontSize = '1rem';
                btn.style.lineHeight = '1';
                btn.style.padding = '0 0 0 0.35rem';
                btn.textContent = '✕';
                btn.addEventListener('click', function(){ removeUserFromSelected(u.id); });

                chip.appendChild(btn);
                container.appendChild(chip);

                // crear input oculto
                const hi = document.createElement('input');
                hi.type = 'hidden';
                hi.name = 'usuarios_grupo[]';
                hi.value = u.id;
                hiddenInputsContainer.appendChild(hi);
            });

            containerWrapper.style.display = '';
        }

        // inicial
        toggleFields();
    })();
</script>
@endpush


<!-- Modal para mostrar mensaje sin navegar -->
<div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="messageModalLabel">Mensaje</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2 small text-muted">
                    <span id="msg-from"></span>
                    <span id="msg-to" class="ms-3"></span>
                    <span id="msg-date" class="ms-3"></span>
                </div>
                <h6 id="msg-subject" class="fw-bold"></h6>
                <hr>
                <div id="msg-content" class="small"></div>
                <!-- Contenedor para respuesta inline (oculto hasta que se abra) -->
                <div id="reply-container" style="display:none; margin-top:1rem;">
                    <hr>
                    <h6 class="fw-bold">Responder</h6>
                    <div class="mb-2">
                        <input type="text" id="reply-asunto" class="form-control" placeholder="Asunto">
                    </div>
                    <div class="mb-2">
                        <textarea id="reply-contenido" class="form-control" rows="4" placeholder="Escribe tu respuesta..."></textarea>
                    </div>
                    <div id="reply-feedback" style="margin-top:.5rem;"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button id="btn-mark-unread" type="button" class="btn btn-warning" style="display:none;">Marcar como no leído</button>
                <button id="btn-open-reply" type="button" class="btn btn-primary">Responder</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
        (function(){
                // Handler para abrir mensaje en modal
                function openMessage(id){
                        fetch(`/comunicacion/mensajes/${id}`, { headers: { 'Accept': 'application/json' } })
                                .then(r => {
                                        if (!r.ok) throw new Error('Error cargando mensaje');
                                        return r.json();
                                })
                                .then(data => {
                                        document.getElementById('msg-from').textContent = data.remitente ? ('De: ' + data.remitente.name) : '';
                                        document.getElementById('msg-to').textContent = data.destinatario ? ('Para: ' + data.destinatario.name) : '';
                                        document.getElementById('msg-date').textContent = new Date(data.created_at).toLocaleString();
                                        document.getElementById('msg-subject').textContent = data.asunto || '';

                                        // Si hay hilo, renderizar historial completo; si no, mostrar contenido simple
                                        var contentContainer = document.getElementById('msg-content');
                                        if (data.thread && Array.isArray(data.thread) && data.thread.length > 0) {
                                            var threadHtml = '';
                                            data.thread.forEach(function(m, idx){
                                                threadHtml += '<div class="mb-3">';
                                                threadHtml += '<div class="small text-muted">' + (m.remitente ? ('De: ' + m.remitente.name) : '') + ' <span class="ms-3">' + (m.destinatario ? ('Para: ' + m.destinatario.name) : '') + '</span> <span class="ms-3">' + (new Date(m.created_at)).toLocaleString() + '</span></div>';
                                                threadHtml += '<div class="fw-semibold mt-1">' + (m.asunto || '') + '</div>';
                                                threadHtml += '<div class="mt-2 small">' + (m.contenido ? m.contenido.replace(/\n/g, '<br>') : '') + '</div>';
                                                if (idx < data.thread.length - 1) threadHtml += '<hr>';
                                                threadHtml += '</div>';
                                            });
                                            contentContainer.innerHTML = threadHtml;
                                        } else {
                                            contentContainer.innerHTML = data.contenido ? data.contenido.replace(/\n/g, '<br>') : '';
                                        }
                                                // almacenar id actual en el modal para acciones posteriores
                                                document.getElementById('messageModal').setAttribute('data-current-id', data.id);
                                                // mostrar/ocultar botón 'Marcar como no leído' si soy destinatario y está leído
                                                var btnMarkUnread = document.getElementById('btn-mark-unread');
                                                var currentUserEl = document.getElementById('currentUser');
                                                var currentUserId = currentUserEl ? parseInt(currentUserEl.dataset.id, 10) : null;
                                                if (btnMarkUnread) {
                                                    if (data.destinatario && (data.destinatario.id === currentUserId) && data.leido) {
                                                        btnMarkUnread.style.display = '';
                                                    } else {
                                                        btnMarkUnread.style.display = 'none';
                                                    }
                                                }

                                                // actualizar badge en la tabla a 'Leído' si corresponde
                                                try {
                                                    var row = document.querySelector('tr[data-id="' + data.id + '"]');
                                                    if (row) {
                                                        var badge = row.querySelector('td .badge');
                                                        if (badge) {
                                                            badge.className = 'badge bg-success';
                                                            badge.textContent = 'Leído';
                                                        }
                                                    }
                                                } catch (err) {
                                                    console.warn('No se pudo actualizar badge', err);
                                                }

                                                // mostrar modal
                                                var modalEl = document.getElementById('messageModal');
                                                var modal = new bootstrap.Modal(modalEl);
                                                modal.show();
                                })
                                .catch(err => {
                                        console.error(err);
                                        alert('No se pudo cargar el mensaje. Revisa la consola para más detalles.');
                                });
                }

                document.addEventListener('click', function(e){
                        var btn = e.target.closest && e.target.closest('.open-message-btn');
                        if (!btn) return;
                        var id = btn.getAttribute('data-id');
                        if (!id) return;
                        e.preventDefault();
                        openMessage(id);
                });

                // Reply flow: abrir/cancelar/enviar dentro del modal
                const btnOpenReply = document.getElementById('btn-open-reply');
                const replyContainer = document.getElementById('reply-container');
                const replyAsunto = document.getElementById('reply-asunto');
                const replyContenido = document.getElementById('reply-contenido');
                const replyFeedback = document.getElementById('reply-feedback');

                function clearReplyFeedback(){ replyFeedback.innerHTML = ''; }

                if (btnOpenReply) btnOpenReply.addEventListener('click', function(e){
                    e.preventDefault();
                    clearReplyFeedback();
                    const currentId = document.getElementById('messageModal').getAttribute('data-current-id');
                    const subj = document.getElementById('msg-subject').textContent || '';
                    // Si el contenedor está oculto, abrir y prefilar asunto
                    if (!replyContainer || replyContainer.style.display === 'none') {
                        if (replyAsunto) replyAsunto.value = subj.startsWith('RE:') ? subj : ('RE: ' + subj);
                        if (replyContenido) replyContenido.value = '';
                        if (replyContainer) replyContainer.style.display = '';
                        btnOpenReply.textContent = 'Enviar respuesta';
                        replyAsunto && replyAsunto.focus();
                        return;
                    }

                    // Si ya está abierto, proceder a enviar
                    const id = currentId;
                    if (!id) { replyFeedback.innerHTML = '<div class="alert alert-danger small">ID de mensaje no disponible</div>'; return; }
                    const asunto = replyAsunto.value.trim();
                    const contenido = replyContenido.value.trim();
                    if (!asunto || !contenido) { replyFeedback.innerHTML = '<div class="alert alert-warning small">Asunto y contenido son obligatorios.</div>'; return; }

                    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
                    const token = tokenMeta ? tokenMeta.getAttribute('content') : '';

                    fetch(`/comunicacion/mensajes/${id}/responder`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token
                        },
                        body: JSON.stringify({ asunto: asunto, contenido: contenido })
                    })
                    .then(r => {
                        if (r.status === 422) return r.json().then(j=>{ throw {validation: j}; });
                        if (!r.ok) throw new Error('Error al enviar');
                        return r.json();
                    })
                    .then(json => {
                        // cerrar modal
                        var modalEl = document.getElementById('messageModal');
                        var modalInstance = bootstrap.Modal.getInstance(modalEl);
                        if (modalInstance) modalInstance.hide();
                        // limpiar formulario
                        replyContainer.style.display = 'none';
                        replyAsunto.value = '';
                        replyContenido.value = '';
                        btnOpenReply.textContent = 'Responder';
                        // mostrar toast de éxito
                        var toastEl = document.getElementById('liveToast');
                        if (toastEl) {
                            var toastBody = toastEl.querySelector('.toast-body');
                            if (toastBody) toastBody.textContent = (json.message || 'Respuesta enviada correctamente');
                            var toast = new bootstrap.Toast(toastEl);
                            toast.show();
                        } else {
                            alert(json.message || 'Respuesta enviada correctamente');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        if (err && err.validation) {
                            // mostrar errores de validación simples
                            const messages = [];
                            for (const k in err.validation.errors) { messages.push(err.validation.errors[k].join(', ')); }
                            replyFeedback.innerHTML = '<div class="alert alert-danger small">' + messages.join('<br>') + '</div>';
                        } else {
                            replyFeedback.innerHTML = '<div class="alert alert-danger small">Error al enviar la respuesta.</div>';
                        }
                    });
                });

                // Marcar como no leído desde el modal
                const btnMarkUnread = document.getElementById('btn-mark-unread');
                if (btnMarkUnread) btnMarkUnread.addEventListener('click', function(e){
                    e.preventDefault();
                    const id = document.getElementById('messageModal').getAttribute('data-current-id');
                    if (!id) return;
                    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
                    const token = tokenMeta ? tokenMeta.getAttribute('content') : '';
                    fetch(`/comunicacion/mensajes/${id}/no-leer`, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token
                        },
                        body: JSON.stringify({})
                    })
                    .then(r => {
                        if (!r.ok) throw new Error('Error marcando como no leído');
                        return r.json();
                    })
                    .then(json => {
                        // cerrar modal
                        var modalEl = document.getElementById('messageModal');
                        var modalInstance = bootstrap.Modal.getInstance(modalEl);
                        if (modalInstance) modalInstance.hide();
                        // actualizar badge en la tabla a 'No leído'
                        try {
                            var row = document.querySelector('tr[data-id="' + id + '"]');
                            if (row) {
                                var badge = row.querySelector('td .badge');
                                if (badge) {
                                    badge.className = 'badge bg-warning text-dark';
                                    badge.textContent = 'No leído';
                                }
                            }
                        } catch (err) { console.warn('No se pudo actualizar badge', err); }
                        // mostrar toast o feedback breve
                        var toastEl = document.getElementById('liveToast');
                        if (toastEl) {
                            var toastBody = toastEl.querySelector('.toast-body');
                            if (toastBody) toastBody.textContent = (json.message || 'Mensaje marcado como no leído');
                            var toast = new bootstrap.Toast(toastEl);
                            toast.show();
                        }
                    })
                    .catch(err => { console.error(err); alert('No se pudo marcar como no leído'); });
                });

                // Eliminar mensaje (confirmación + AJAX)
                let idToDelete = null;
                const confirmDeleteModalEl = document.getElementById('confirmDeleteModal');
                const confirmDeleteModal = confirmDeleteModalEl ? new bootstrap.Modal(confirmDeleteModalEl) : null;
                document.addEventListener('click', function(e){
                    const delBtn = e.target.closest && e.target.closest('.delete-message-btn');
                    if (!delBtn) return;
                    e.preventDefault();
                    idToDelete = delBtn.getAttribute('data-id');
                    if (confirmDeleteModal) confirmDeleteModal.show();
                });

                const confirmYes = document.getElementById('confirm-delete-yes');
                if (confirmYes) confirmYes.addEventListener('click', function(e){
                    e.preventDefault();
                    if (!idToDelete) return;
                    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
                    const token = tokenMeta ? tokenMeta.getAttribute('content') : '';
                    fetch(`/comunicacion/mensajes/${idToDelete}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token
                        },
                        body: JSON.stringify({})
                    })
                    .then(r => {
                        if (!r.ok) throw new Error('Error al eliminar');
                        return r.json();
                    })
                    .then(json => {
                        if (confirmDeleteModal) confirmDeleteModal.hide();
                        // eliminar la fila del DOM en vez de recargar
                        try {
                            var row = document.querySelector('tr[data-id="' + idToDelete + '"]');
                            if (row) row.remove();

                            // comprobar si la tabla quedó vacía y mostrar fila "No hay mensajes"
                            var panelInboxBody = document.querySelector('#panel-inbox tbody');
                            var panelSentBody = document.querySelector('#panel-sent tbody');
                            if (panelInboxBody && panelInboxBody.children.length === 0) {
                                panelInboxBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No hay mensajes</td></tr>';
                            }
                            if (panelSentBody && panelSentBody.children.length === 0) {
                                panelSentBody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No hay mensajes enviados</td></tr>';
                            }
                        } catch (err) { console.warn('No se pudo eliminar fila del DOM', err); }

                        // mostrar toast de éxito
                        var toastEl = document.getElementById('liveToast');
                        if (toastEl) {
                            var toastBody = toastEl.querySelector('.toast-body');
                            if (toastBody) toastBody.textContent = (json.message || 'Mensaje eliminado');
                            var toast = new bootstrap.Toast(toastEl);
                            toast.show();
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        alert('No se pudo eliminar el mensaje');
                    });
                });
        })();
</script>
@endpush

<!-- Toast de notificaciones (éxito) -->
<div aria-live="polite" aria-atomic="true" class="position-fixed top-0 end-0 p-3" style="z-index: 1080;">
    <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <strong class="me-auto">Mensajes</strong>
            <small class="text-muted"></small>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Cerrar"></button>
        </div>
        <div class="toast-body">
            Mensaje enviado correctamente.
        </div>
    </div>
</div>

<!-- current user id for JS logic -->
<div id="currentUser" data-id="{{ \Illuminate\Support\Facades\Auth::id() }}" style="display:none"></div>

<!-- Modal de confirmación para eliminar -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmDeleteLabel">Confirmar eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                ¿Está seguro que desea eliminar este mensaje? Esta acción no se puede deshacer.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">No</button>
                <button type="button" id="confirm-delete-yes" class="btn btn-danger btn-sm">Sí, eliminar</button>
            </div>
        </div>
    </div>
</div>
