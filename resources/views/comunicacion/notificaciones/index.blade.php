@extends('layouts.app')

@section('content')
<div class="container py-4">
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
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-send-fill me-1"></i> Enviar Notificación
                        </button>
                    </form>
                </div>
            </div>

            <div class="row">
                @forelse($notificaciones as $notif)
                    <div class="col-md-4 mb-3">
                        <div class="card shadow-sm h-100 {{ $notif->leida ? 'border-secondary' : 'border-warning' }}">
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
            const containerUsuariosGrupo = document.getElementById('container-usuarios-grupo-notif');
            const usuariosGrupoSearch = document.getElementById('usuarios_notif_search');
            const usuariosGrupoClear = document.getElementById('usuarios_notif_clear');
            const usuariosGrupoResults = document.getElementById('usuarios_notif_results');
            const hiddenInputsContainer = document.getElementById('selected-hidden-inputs-notif');

            function toggleFields() {
                const val = modo.value;
                if (val === 'rol') {
                    containerRol.style.display = '';
                    rolSelect.disabled = false;
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
                    return;
                }
                containerUsuariosGrupo.style.display = '';
                usuariosGrupoSearch.focus();
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
                const q = usuariosGrupoSearch.value.trim(); const rolId = rolSelect.value; usuariosGrupoResults.innerHTML=''; if (!rolId) return; if (q.length<1){ usuariosGrupoResults.style.display='none'; return; }
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
                function colorFromGrupoId(id){ if (!id) return null; const hue=(id*53)%360; return `hsl(${hue} 65% 45%)`; }
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
