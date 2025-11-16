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
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-send-check-fill me-1"></i> Enviar Mensaje
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
                <div class="card-header bg-secondary text-white">
                    <i class="bi bi-inbox-fill me-1"></i> Bandeja de Entrada
                </div>
                <div class="card-body">
                    <table class="table table-hover table-striped align-middle">
                        <thead class="table-secondary">
                            <tr>
                                <th>Remitente</th>
                                <th>Asunto</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($mensajes as $msg)
                                <tr>
                                    <td>{{ $msg->remitente_id }}</td>
                                    <td>{{ $msg->asunto }}</td>
                                    <td>{{ $msg->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        @if($msg->leido)
                                            <span class="badge bg-success">Leído</span>
                                        @else
                                            <span class="badge bg-warning text-dark">No leído</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted">No hay mensajes</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function(){
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
