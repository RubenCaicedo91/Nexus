{{-- index.blade.php --}}
@extends('layouts.app')
@section('content')
<h2>Registrar Sanción</h2>

@if($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if(!empty($isCoordinator) && $isCoordinator)
    <div class="alert alert-warning">No tienes permiso para registrar sanciones desde este perfil.</div>
    <form action="#" method="POST" onsubmit="return false;">
        <fieldset disabled>
            @csrf
            <div class="mb-3">
                <label for="buscador_documento_registrar" class="form-label">Número de documento</label>
                <input id="buscador_documento_registrar" class="form-control" placeholder="Escribe el número de documento..." autocomplete="off">
                <small class="form-text text-muted">Introduce al menos 4 dígitos para buscar por documento.</small>
            </div>
            <div class="mb-3">
                <label for="buscador_estudiante_registrar" class="form-label">Estudiante</label>
                {{-- Campo de texto con sugerencias; el id real se guarda en el input hidden usuario_id --}}
                <input id="buscador_estudiante_registrar" list="lista_estudiantes_registrar" class="form-control" placeholder="Escribe un nombre..." autocomplete="off" required>
                <datalist id="lista_estudiantes_registrar">
                    @if(isset($students) && count($students))
                        @foreach($students as $stu)
                            @php $disp = $stu->name . ' (ID: ' . $stu->id . ')'; @endphp
                            <option value="{{ $disp }}"></option>
                        @endforeach
                    @endif
                </datalist>
                <input type="hidden" name="usuario_id" id="usuario_id_hidden">
                <div id="student_info" class="mt-2 text-muted"></div>
                <small class="form-text text-muted">Selecciona un estudiante de la lista. Si no se selecciona, el formulario no enviará el id.</small>
            </div>
            <!-- campos restantes (deshabilitados) -->
            <div class="mb-3">
                <label for="descripcion" class="form-label">Descripción</label>
                <input type="text" name="descripcion" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="tipo_id" class="form-label">Tipo</label>
                <select name="tipo_id" id="tipo_id" class="form-control" required>
                    <option value="">-- Selecciona un tipo --</option>
                        @if(isset($tipos) && count($tipos))
                            @foreach($tipos as $t)
                                <option value="{{ $t->id }}" {{ old('tipo_id') == $t->id ? 'selected' : '' }}>{{ $t->nombre }}</option>
                            @endforeach
                        @endif
                </select>
            </div>
            <div class="mb-3">
                <label for="fecha" class="form-label">Fecha</label>
                <input type="date" name="fecha" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-success" disabled title="No tienes permiso">Registrar</button>
            <a href="{{ route('gestion-disciplinaria.index') }}" class="btn btn-secondary">Cancelar</a>
        </fieldset>
    </form>
@else
    <form action="{{ route('gestion-disciplinaria.store') }}" method="POST">
        @csrf
    <div class="mb-3">
        <label for="buscador_documento_registrar" class="form-label">Número de documento</label>
        <input id="buscador_documento_registrar" class="form-control" placeholder="Escribe el número de documento..." autocomplete="off">
        <small class="form-text text-muted">Introduce al menos 4 dígitos para buscar por documento.</small>
    </div>
    <div class="mb-3">
        <label for="buscador_estudiante_registrar" class="form-label">Estudiante</label>
        {{-- Campo de texto con sugerencias; el id real se guarda en el input hidden usuario_id --}}
        <input id="buscador_estudiante_registrar" list="lista_estudiantes_registrar" class="form-control" placeholder="Escribe un nombre..." autocomplete="off" required>
        <datalist id="lista_estudiantes_registrar">
            @if(isset($students) && count($students))
                @foreach($students as $stu)
                    @php $disp = $stu->name . ' (ID: ' . $stu->id . ')'; @endphp
                    <option value="{{ $disp }}"></option>
                @endforeach
            @endif
        </datalist>
        <input type="hidden" name="usuario_id" id="usuario_id_hidden">
        <div id="student_info" class="mt-2 text-muted"></div>
        <small class="form-text text-muted">Selecciona un estudiante de la lista. Si no se selecciona, el formulario no enviará el id.</small>
    </div>


<script>
    (function(){
    // Mapa nombre -> id para asignar el usuario_id al seleccionar
    // Usamos base64 para evitar que el editor/lingüeta JS interprete mal sintaxis Blade/JSON
    const studentList = JSON.parse(atob('{{ base64_encode(json_encode($studentArray ?? [])) }}'));

        const input = document.getElementById('buscador_estudiante_registrar');
        const hidden = document.getElementById('usuario_id_hidden');
        const studentInfoEl = document.getElementById('student_info');
        const docInput = document.getElementById('buscador_documento_registrar');
        const buscarUrl = '{{ route("gestion-disciplinaria.buscar") }}';

        // Debounce timer for AJAX
        let debounceTimer = null;
        let debounceDocTimer = null;

        function findStudentIdByInput(text) {
            if (!text) return '';
            const q = text.trim().toLowerCase();
            // 1) exact match on display
            for (const s of studentList) {
                if ((s.display || '').toLowerCase() === q) return s.id;
            }
            // 2) exact match on name
            for (const s of studentList) {
                if ((s.name || '').toLowerCase() === q) return s.id;
            }
            // 2b) exact match on document number
            for (const s of studentList) {
                if (s.document_number && ('' + s.document_number).toLowerCase() === q) return s.id;
            }
            // 3) startsWith on name
            for (const s of studentList) {
                if ((s.name || '').toLowerCase().startsWith(q)) return s.id;
            }
            // 4) includes on name
            for (const s of studentList) {
                if ((s.name || '').toLowerCase().includes(q)) return s.id;
            }
            // 5) includes on document number (partial match)
            for (const s of studentList) {
                if (s.document_number && ('' + s.document_number).toLowerCase().includes(q)) return s.id;
            }
            return '';
        }

        if (input) {
            input.addEventListener('input', function(e){
                const v = (e.target.value || '').trim();
                if (v === '') { hidden.value = ''; studentInfoEl.innerHTML = ''; return; }

                // Si el término contiene una cantidad significativa de dígitos, sugerimos al usuario usar el campo de documento
                const digits = (v.match(/\d/g) || []).length;
                if (digits >= 4) {
                    // no hacemos la búsqueda principal por aquí; preferimos que el usuario use el campo "Número de documento"
                    // Limpiamos info para evitar confusión
                    // (la búsqueda por documento se maneja en el campo específico)
                    studentInfoEl.innerHTML = '';
                    return;
                }

                // Si no es búsqueda numérica, intentar búsqueda local por nombre/documento
                const id = findStudentIdByInput(v);
                hidden.value = id || '';
                if (hidden.value) {
                    const s = studentList.find(x => (''+x.id) === (''+hidden.value));
                    if (s) {
                        studentInfoEl.innerHTML = '<div><strong>' + (s.name || '-') + '</strong> - ' + (s.document_number || '') + '</div>';
                    }
                } else {
                    studentInfoEl.innerHTML = '';
                }
            });

            // Escuchar el campo de documento y realizar búsqueda por documento (prioritaria)
            if (docInput) {
                docInput.addEventListener('input', function(ev){
                    const q = (ev.target.value || '').trim();
                    const digits = (q.match(/\d/g) || []).length;
                    if (q === '' || digits < 4) {
                        // limpiar si es corto
                        if (debounceDocTimer) clearTimeout(debounceDocTimer);
                        studentInfoEl.innerHTML = '';
                        hidden.value = '';
                        return;
                    }

                    if (debounceDocTimer) clearTimeout(debounceDocTimer);
                    debounceDocTimer = setTimeout(function(){
                        fetch(buscarUrl + '?document=' + encodeURIComponent(q), {
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                        }).then(r => r.json()).then(data => {
                            if (data && data.success) {
                                const u = data.user;
                                hidden.value = u.id;
                                // rellenar el campo de nombre para visibilidad
                                if (input) input.value = (u.name || '') + ' (ID: ' + u.id + ')';
                                let courseHtml = '';
                                if (data.matricula && data.matricula.curso) {
                                    courseHtml = '<div><strong>Curso:</strong> ' + (data.matricula.curso.nombre || data.matricula.curso_nombre || '-') + '</div>';
                                } else if (data.matricula && data.matricula.curso_nombre) {
                                    courseHtml = '<div><strong>Curso:</strong> ' + data.matricula.curso_nombre + '</div>';
                                }
                                studentInfoEl.innerHTML = '<div><strong>' + (u.name || '-') + '</strong> - ' + (u.document_number || '') + '</div>' + courseHtml;
                            } else {
                                hidden.value = '';
                                studentInfoEl.innerHTML = '<div class="text-danger">Estudiante no encontrado</div>';
                            }
                        }).catch(err => {
                            hidden.value = '';
                            studentInfoEl.innerHTML = '<div class="text-danger">Error en búsqueda</div>';
                        });
                    }, 300);
                });
            }

            // Al enviar el formulario verificar que hidden tenga valor
            const form = input.closest('form');
            if (form) {
                form.addEventListener('submit', function(ev){
                    if (!hidden.value) {
                        ev.preventDefault();
                        alert('Por favor selecciona un estudiante válido de la lista.');
                        input.focus();
                    }
                });
            }
        }
    })();
</script>
    <div class="mb-3">
        <label for="descripcion" class="form-label">Descripción</label>
        <input type="text" name="descripcion" class="form-control" required>
    </div>
    <div class="mb-3">
        <label for="tipo_id" class="form-label">Tipo</label>
        <select name="tipo_id" id="tipo_id" class="form-control" required>
            <option value="">-- Selecciona un tipo --</option>
                @if(isset($tipos) && count($tipos))
                    @foreach($tipos as $t)
                        <option value="{{ $t->id }}" {{ old('tipo_id') == $t->id ? 'selected' : '' }}>{{ $t->nombre }}</option>
                    @endforeach
                @endif
        </select>
    </div>
    <div id="suspension_fields" class="mb-3" style="display:none">
        <label class="form-label">Fecha inicio sanción</label>
        <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control" value="{{ old('fecha_inicio') }}">
        <div id="fecha_fin_wrap">
            <label class="form-label mt-2">Fecha fin sanción</label>
            <input type="date" name="fecha_fin" id="fecha_fin" class="form-control" value="{{ old('fecha_fin') }}">
        </div>
    </div>
    <div id="meeting_fields" class="mb-3" style="display:none">
        <label class="form-label">Fecha y hora de reunión (padre/tutor)</label>
        <input type="datetime-local" name="reunion_at" id="reunion_at" class="form-control" value="{{ old('reunion_at') }}">
    </div>
    <div id="monetary_fields" class="mb-3" style="display:none">
        <label class="form-label">Monto a pagar</label>
        <input type="number" step="0.01" min="0" name="monto" id="monto" class="form-control" value="{{ old('monto') }}">
        <small class="form-text text-muted">Este monto debe ser pagado por el responsable.</small>
        <input type="hidden" name="pago_observacion" id="pago_observacion" value="{{ old('pago_observacion', '') }}">
    </div>
    <div class="mb-3">
        <label for="fecha" class="form-label">Fecha</label>
        <input type="date" name="fecha" class="form-control" required>
    </div>
        <button type="submit" class="btn btn-success">Registrar</button>
        <a href="{{ route('gestion-disciplinaria.index') }}" class="btn btn-secondary">Cancelar</a>

        <a href="{{ route('gestion-disciplinaria.index') }}" class="btn btn-secondary">
        Volver
    </a>
    </form>
@endif

@endsection

@push('scripts')
<script>
    (function(){
        // tipos list para JS: id y nombre
        const tiposList = @json($tiposForJs);
        const tipoSelect = document.getElementById('tipo_id');
        const suspensionEl = document.getElementById('suspension_fields');
        const monetaryEl = document.getElementById('monetary_fields');
        const meetingEl = document.getElementById('meeting_fields');
        const fechaInicio = document.getElementById('fecha_inicio');
        const fechaFin = document.getElementById('fecha_fin');
        const fechaFinWrap = document.getElementById('fecha_fin_wrap');
        const montoEl = document.getElementById('monto');
        const pagoObs = document.getElementById('pago_observacion');
        const reunionAtEl = document.getElementById('reunion_at');

        function updateFieldsByTipoId(id) {
            const tipo = tiposList.find(t => (''+t.id) === (''+id));
            const categoria = tipo && tipo.categoria ? tipo.categoria : 'normal';
            const isSusp = categoria === 'suspension';
            const isMoney = categoria === 'monetary';
            const isExp = categoria === 'expulsion';
            const isPriv = categoria === 'privileges';
            const isMeeting = categoria === 'meeting';

            // Suspension-like: suspension or privileges or expulsion
            if (isSusp || isPriv || isExp) {
                suspensionEl.style.display = '';
                fechaInicio.required = true;
            } else {
                suspensionEl.style.display = 'none';
                fechaInicio.required = false;
            }

            // Fecha fin handling: required for suspension and privileges, hidden/optional for expulsion
            if (isSusp || isPriv) {
                fechaFinWrap.style.display = '';
                fechaFin.required = true;
            } else if (isExp) {
                fechaFinWrap.style.display = 'none';
                fechaFin.required = false;
                fechaFin.value = '';
            } else {
                fechaFinWrap.style.display = 'none';
                fechaFin.required = false;
                fechaFin.value = '';
            }

            // Monetary
            if (isMoney) {
                monetaryEl.style.display = '';
                montoEl.required = true;
                pagoObs.value = pagoObs.value || 'Pago obligatorio';
            } else {
                monetaryEl.style.display = 'none';
                montoEl.required = false;
                pagoObs.value = '';
            }

            // Meeting
            if (isMeeting) {
                meetingEl.style.display = '';
                if (reunionAtEl) reunionAtEl.required = true;
            } else {
                meetingEl.style.display = 'none';
                if (reunionAtEl) reunionAtEl.required = false;
                if (reunionAtEl) reunionAtEl.value = '';
            }
        }

        if (tipoSelect) {
            tipoSelect.addEventListener('change', function(e){
                updateFieldsByTipoId(e.target.value);
            });

            // Inicializar al cargar (por si viene old input)
            if (tipoSelect.value) {
                updateFieldsByTipoId(tipoSelect.value);
            }
        }
    })();
</script>
@endpush