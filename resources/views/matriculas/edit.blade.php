@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h2 class="mb-0">Editar Matrícula</h2>
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
            <form action="{{ route('matriculas.update', $matricula->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-lg-4 mb-3">
                        <div class="card p-3 border-0 shadow-sm">
                            <div class="mb-2">
                                @if($matricula->user)
                                    <div class="h6 mb-1">{{ $matricula->user->name }}</div>
                                    @if(!empty($matricula->user->document_type) || !empty($matricula->user->document_number))
                                        <div class="text-muted small">{{ $matricula->user->document_type ?? '' }} {{ $matricula->user->document_number ?? '' }}</div>
                                    @endif
                                    @if(!empty($matricula->user->email))<div class="mt-2"><i class="fas fa-envelope me-1"></i> {{ $matricula->user->email }}</div>@endif
                                    @if(!empty($matricula->user->celular))<div><i class="fas fa-phone me-1"></i> {{ $matricula->user->celular }}</div>@endif
                                @else
                                    <div class="text-muted">No hay estudiante asignado.</div>
                                @endif
                            </div>
                            <input type="hidden" name="user_id" id="user_id_input" value="{{ $matricula->user_id }}">

                            <hr>
                            <div>
                                <label class="form-label"><strong>Estado de documentos</strong></label>
                                <div class="d-flex flex-column gap-2">
                                    <div>
                                        Documento de Identidad:
                                        @if(!empty($matricula->documento_identidad))
                                            <span class="badge bg-success ms-2">Presente</span>
                                            <a href="{{ route('matriculas.archivo', ['matricula' => $matricula->id, 'campo' => 'documento_identidad']) }}" class="btn btn-link btn-sm ms-2" target="_blank">Ver</a>
                                        @else
                                            <span class="badge bg-danger ms-2">Faltante</span>
                                        @endif
                                    </div>
                                    <div>
                                        RH:
                                        @if(!empty($matricula->rh))
                                            <span class="badge bg-success ms-2">Presente</span>
                                            <a href="{{ route('matriculas.archivo', ['matricula' => $matricula->id, 'campo' => 'rh']) }}" class="btn btn-link btn-sm ms-2" target="_blank">Ver</a>
                                        @else
                                            <span class="badge bg-danger ms-2">Faltante</span>
                                        @endif
                                    </div>
                                    <div>
                                        Comprobante de Pago:
                                        @if(!empty($matricula->comprobante_pago))
                                            <span class="badge bg-success ms-2">Presente</span>
                                            <a href="{{ route('matriculas.archivo', ['matricula' => $matricula->id, 'campo' => 'comprobante_pago']) }}" class="btn btn-link btn-sm ms-2" target="_blank">Ver</a>
                                        @else
                                            <span class="badge bg-danger ms-2">Faltante</span>
                                        @endif
                                    </div>
                                    <div>
                                        Certificado Médico:
                                        @if(!empty($matricula->certificado_medico))
                                            <span class="badge bg-success ms-2">Presente</span>
                                            <a href="{{ route('matriculas.archivo', ['matricula' => $matricula->id, 'campo' => 'certificado_medico']) }}" class="btn btn-link btn-sm ms-2" target="_blank">Ver</a>
                                        @else
                                            <span class="badge bg-danger ms-2">Faltante</span>
                                        @endif
                                    </div>
                                    <div>
                                        Certificado de Notas:
                                        @if(!empty($matricula->certificado_notas))
                                            <span class="badge bg-success ms-2">Presente</span>
                                            <a href="{{ route('matriculas.archivo', ['matricula' => $matricula->id, 'campo' => 'certificado_notas']) }}" class="btn btn-link btn-sm ms-2" target="_blank">Ver</a>
                                        @else
                                            <span class="badge bg-danger ms-2">Faltante</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-8">
                        <div class="mb-3">
                            <label class="form-label"><strong>Fecha de Matrícula:</strong></label>
                            <input type="date" name="fecha_matricula" value="{{ $matricula->fecha_matricula }}" class="form-control" placeholder="Fecha de Matrícula">
                        </div>
                        @if(isset($baseCursos) && count($baseCursos) > 0)
                        <div class="mb-3">
                            <label class="form-label"><strong>Curso (base):</strong></label>
                            <select name="curso_nombre" class="form-control" id="curso_nombre_select">
                                <option value="">-- Seleccionar curso --</option>
                                @foreach($baseCursos as $c)
                                    <option value="{{ $c }}" @if(isset($currentCursoBase) && $currentCursoBase == $c) selected @endif>{{ $c }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Seleccione (o revise) el curso base asignado.</div>
                            <div id="cursos_lista_info" class="mt-2"></div>
                        </div>
                        @elseif(isset($matricula->curso) && !empty($matricula->curso->nombre))
                        <div class="mb-3">
                            <label class="form-label"><strong>Curso asignado:</strong></label>
                            <div class="form-control-plaintext">{{ $matricula->curso->nombre }}</div>
                        </div>
                        @endif
                        @php
                            $roleName = optional(Auth::user()->role)->nombre;
                            $allowedRoles = ['Administrador_sistema', 'Administrador de sistema', 'Rector', 'Coordinador Académico', 'Coordinador Academico'];
                            $canChangeEstado = in_array($roleName, $allowedRoles);
                        @endphp
                        <div class="mb-3">
                            <label class="form-label"><strong>Estado:</strong></label>
                            @if($canChangeEstado)
                                <select name="estado" class="form-control">
                                    <option value="activo" @if($matricula->estado == 'activo') selected @endif>Activo</option>
                                    <option value="inactivo" @if($matricula->estado == 'inactivo') selected @endif>Inactivo</option>
                                    <option value="completado" @if($matricula->estado == 'completado') selected @endif>Completado</option>
                                    <option value="suspendido" @if($matricula->estado == 'suspendido') selected @endif>Suspendido</option>
                                </select>
                            @else
                                <div class="form-control-plaintext">{{ $matricula->estado }}</div>
                            @endif
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><strong>Documento de Identidad:</strong></label>
                                @if(!empty($matricula->documento_identidad))
                                    <div class="mb-2">
                                        <a href="{{ $matricula->documento_identidad_url }}" target="_blank" class="btn btn-outline-primary btn-sm">Ver documento</a>
                                        <button type="submit" name="delete_documento_identidad" value="1" class="btn btn-outline-danger btn-sm ms-2" onclick="return confirm('¿Eliminar documento actual?')">Eliminar</button>
                                    </div>
                                @endif
                                <input type="file" name="documento_identidad" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><strong>RH:</strong></label>
                                @if(!empty($matricula->rh))
                                    <div class="mb-2">
                                        <a href="{{ $matricula->rh_url }}" target="_blank" class="btn btn-outline-primary btn-sm">Ver documento</a>
                                        <button type="submit" name="delete_rh" value="1" class="btn btn-outline-danger btn-sm ms-2" onclick="return confirm('¿Eliminar documento actual?')">Eliminar</button>
                                    </div>
                                @endif
                                <input type="file" name="rh" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><strong>Comprobante de Pago (Matrícula):</strong></label>
                                @if(!empty($matricula->comprobante_pago))
                                    <div class="mb-2">
                                        <a href="{{ $matricula->comprobante_pago_url }}" target="_blank" class="btn btn-outline-primary btn-sm">Ver comprobante</a>
                                        <button type="submit" name="delete_comprobante_pago" value="1" class="btn btn-outline-danger btn-sm ms-2" onclick="return confirm('¿Eliminar comprobante actual?')">Eliminar</button>
                                    </div>
                                @endif
                                <input type="file" name="comprobante_pago" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label"><strong>Certificado Médico:</strong></label>
                                @if(!empty($matricula->certificado_medico))
                                    <div class="mb-2">
                                        <a href="{{ $matricula->certificado_medico_url }}" target="_blank" class="btn btn-outline-primary btn-sm">Ver documento</a>
                                        <button type="submit" name="delete_certificado_medico" value="1" class="btn btn-outline-danger btn-sm ms-2" onclick="return confirm('¿Eliminar documento actual?')">Eliminar</button>
                                    </div>
                                @endif
                                <input type="file" name="certificado_medico" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><strong>Tipo de Usuario:</strong></label>
                            <select name="tipo_usuario" class="form-control" id="tipo_usuario_select">
                                <option value="nuevo" @if(old('tipo_usuario', $matricula->tipo_usuario ?? '') == 'nuevo') selected @endif>Nuevo</option>
                                <option value="antiguo" @if(old('tipo_usuario', $matricula->tipo_usuario ?? '') == 'antiguo') selected @endif>Antiguo</option>
                            </select>
                        </div>

                        <div class="mb-3" id="certificado_notas_group" style="display: none;">
                            <label class="form-label"><strong>Certificado de Notas del Año Anterior:</strong></label>
                            @if(!empty($matricula->certificado_notas))
                                <div class="mb-2">
                                    <a href="{{ $matricula->certificado_notas_url }}" target="_blank" class="btn btn-outline-primary btn-sm">Ver documento</a>
                                    <button type="submit" name="delete_certificado_notas" value="1" class="btn btn-outline-danger btn-sm ms-2" onclick="return confirm('¿Eliminar documento actual?')">Eliminar</button>
                                </div>
                            @endif
                            <input type="file" name="certificado_notas" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                        </div>

                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-primary">Guardar cambios</button>
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
        function toggleNotas() {
            if (tipoUsuario.value === 'antiguo') {
                certificadoNotas.style.display = 'block';
            } else {
                certificadoNotas.style.display = 'none';
            }
        }
        tipoUsuario.addEventListener('change', toggleNotas);
        toggleNotas();
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const cursosPorBaseTemplate = '/matriculas/json/cursos-por-base/__BASE__';
        const cursoSelect = document.getElementById('curso_nombre_select');
        const cursosLista = document.getElementById('cursos_lista_info');

        async function fetchCursosPorBase(base) {
            if (!base) { cursosLista.innerHTML = ''; return; }
            const url = cursosPorBaseTemplate.replace('__BASE__', encodeURIComponent(base));
            try {
                const res = await fetch(url);
                if (!res.ok) throw new Error('Error al obtener cursos');
                const data = await res.json();
                if (!data || data.length === 0) {
                    cursosLista.innerHTML = '<div class="text-muted small">No hay grupos creados para este nivel.</div>';
                    return;
                }
                const badges = data.map(c => '<span class="badge bg-info me-1 mb-1">' + c.nombre + '</span>').join('');
                cursosLista.innerHTML = '<div class="small"><strong>Grupos disponibles:</strong></div><div class="mt-1">' + badges + '</div>' +
                    '<div class="form-text text-muted mt-1">Esto es solo informativo; la asignación real se realiza desde el módulo de Asignaciones.</div>';
            } catch (err) {
                console.error(err);
                cursosLista.innerHTML = '<div class="text-danger">No se pudieron cargar los cursos informativos.</div>';
            }
        }

        if (cursoSelect) {
            cursoSelect.addEventListener('change', function() {
                fetchCursosPorBase(this.value);
            });
            if (cursoSelect.value) fetchCursosPorBase(cursoSelect.value);
        }
    });
</script>
@endpush
@endsection
