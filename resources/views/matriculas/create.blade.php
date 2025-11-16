@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h2 class="mb-0">Crear Nueva Matrícula</h2>
            <a class="btn btn-primary" href="{{ route('matriculas.index') }}">
                <i class="fas fa-arrow-left me-2"></i>Volver
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Whoops!</strong> Hubo algunos problemas con tu entrada.<br><br>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form action="{{ route('matriculas.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-lg-4 mb-3">
                        <div class="card p-3 border-0 shadow-sm">
                            <div id="student_card">
                                <div class="text-muted">Estudiante seleccionado</div>
                                <div id="selected_student" class="mt-2">
                        
                                    <div class="text-muted">Ninguno seleccionado</div>
                                </div>
                            </div>
                            <input type="hidden" name="user_id" id="user_id_input">
                            <hr>
                            <label class="form-label mb-1"><strong>Buscar estudiante</strong></label>
                            <div class="input-group">
                                <input id="student_search" type="text" class="form-control" placeholder="Número de documento" autocomplete="off">
                                <button id="student_search_btn" class="btn btn-outline-secondary" type="button">Buscar</button>
                            </div>
                            <small class="text-muted">Busca únicamente por número de documento.</small>
                            <div id="student_results" class="list-group mt-2" style="max-height:260px; overflow:auto;"></div>
                        </div>
                    </div>

                    <div class="col-lg-8">
                        <div class="mb-3">
                            <label class="form-label"><strong>Fecha de Matrícula:</strong></label>
                            <input type="date" name="fecha_matricula" class="form-control" placeholder="Fecha de Matrícula">
                        </div>

                        @if(isset($baseCursos) && count($baseCursos) > 0)
                        <div class="mb-3">
                            <label class="form-label"><strong>Curso (base):</strong></label>
                            <select name="curso_nombre" class="form-control" id="curso_nombre_select">
                                <option value="">-- Seleccionar curso --</option>
                                   @foreach($baseCursos as $c)
                                    <option value="{{ $c }}">{{ $c }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Seleccione el curso a matricular.</div>
                        </div>
                        @endif

                        @php
                            $roleName = optional(Auth::user()->role)->nombre;
                            $allowedRoles = ['Administrador_sistema', 'Administrador de sistema', 'Rector', 'Coordinador Académico', 'Coordinador Academico'];
                            $canChangeEstado = in_array($roleName, $allowedRoles);
                        @endphp
                        @if($canChangeEstado)
                            <div class="mb-3">
                                <label class="form-label"><strong>Estado:</strong></label>
                                <select name="estado" class="form-control">
                                    <option value="activo">Activo</option>
                                    <option value="inactivo">Inactivo</option>
                                    <option value="completado">Completado</option>
                                    <option value="suspendido">Suspendido</option>
                                </select>
                            </div>
                        @else
                            <div class="mb-3">
                                <label class="form-label"><strong>Estado:</strong></label>
                                <div class="form-control-plaintext text-muted">El estado lo asigna el sistema; solo el Administrador de sistema, Rector o Coordinador Académico pueden modificarlo.</div>
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><strong>Documento de Identidad:</strong></label>
                                <input type="file" name="documento_identidad" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><strong>RH:</strong></label>
                                <input type="file" name="rh" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><strong>Comprobante de Pago (Matrícula):</strong></label>
                                <input type="file" name="comprobante_pago" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><strong>Certificado Médico:</strong></label>
                                <input type="file" name="certificado_medico" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><strong>Tipo de Usuario:</strong></label>
                            <select name="tipo_usuario" class="form-control" id="tipo_usuario_select">
                                <option value="nuevo">Nuevo</option>
                                <option value="antiguo">Antiguo</option>
                            </select>
                        </div>

                        

                        <div class="mb-3" id="certificado_notas_group" style="display: none;">
                            <label class="form-label"><strong>Certificado de Notas del Año Anterior:</strong></label>
                            <input type="file" name="certificado_notas" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                        </div>

                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-primary">Enviar Matrícula</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tipoUsuario = document.getElementById('tipo_usuario_select');
        const certificadoNotas = document.getElementById('certificado_notas_group');
        tipoUsuario.addEventListener('change', function() {
            if (this.value === 'antiguo') {
                certificadoNotas.style.display = 'block';
            } else {
                certificadoNotas.style.display = 'none';
            }
        });
        
        // Búsqueda de estudiante por número de documento (solo en create)
        const searchInput = document.getElementById('student_search');
        const searchBtn = document.getElementById('student_search_btn');
        const resultsBox = document.getElementById('student_results');
        const userIdInput = document.getElementById('user_id_input');
        const selectedBox = document.getElementById('selected_student');

        const searchUrl = '{{ route("matriculas.create") }}';

        function renderResults(list) {
            resultsBox.innerHTML = '';
            if (!list || list.length === 0) {
                resultsBox.innerHTML = '<div class="text-muted">No se encontró ningún estudiante.</div>';
                return;
            }
            list.forEach(s => {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-start';
                const displayName = s.name || ((s.first_name || '') + ' ' + (s.first_last || '')).trim();
                item.innerHTML = '<div>' + (displayName || 'Sin nombre') + '</div>' +
                                 '<small class="text-muted">' + (s.document_number ? s.document_number : '') + '</small>';
                item.addEventListener('click', function() {
                    userIdInput.value = s.id;
                    // Mostrar datos del estudiante sin contraseña
                    const parts = [];
                    const fullName = displayName;
                    parts.push('<div><strong>' + (fullName || 'Sin nombre') + '</strong></div>');
                    if (s.document_type || s.document_number) {
                        parts.push('<div>Documento: ' + (s.document_type ? s.document_type + ' ' : '') + (s.document_number || '') + '</div>');
                    }
                    if (s.email) parts.push('<div>Email: ' + s.email + '</div>');
                    if (s.celular) parts.push('<div>Celular: ' + s.celular + '</div>');
                    parts.push('<div><button type="button" id="clear_student" class="btn btn-sm btn-link">Cambiar</button></div>');
                    selectedBox.innerHTML = parts.join('');
                    resultsBox.innerHTML = '';
                    const clearBtn = document.getElementById('clear_student');
                    clearBtn.addEventListener('click', function() {
                        userIdInput.value = '';
                        selectedBox.innerHTML = '';
                        searchInput.focus();
                    });
                });
                resultsBox.appendChild(item);
            });
        }

        async function doSearch() {
            const q = searchInput.value.trim();
            if (q === '') return;
            try {
                const resp = await fetch(searchUrl + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } });
                if (!resp.ok) throw new Error('Network response was not ok');
                const payload = await resp.json();
                renderResults(payload.data || []);
            } catch (e) {
                resultsBox.innerHTML = '<div class="text-danger">Error al buscar. Intenta de nuevo.</div>';
                console.error(e);
            }
        }

        searchBtn.addEventListener('click', doSearch);
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); doSearch(); }
        });

        // Validación simple antes de enviar: requerir user_id
        const form = document.querySelector('form[action="' + '{{ route("matriculas.store") }}' + '"]');
        if (form) {
            form.addEventListener('submit', function(e) {
                if (!userIdInput.value) {
                    e.preventDefault();
                    alert('Selecciona un estudiante válido usando su número de documento.');
                    searchInput.focus();
                }
            });
        }
    });
</script>
@endpush
@endsection
