@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm rounded overflow-hidden">

        <!-- Encabezado oscuro -->
        <div class="bg-dark text-white p-3 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-semibold mb-0">
                    <i class="bi bi-calendar-check-fill me-2 text-warning"></i> Citas
                </h2>
                <p class="small mb-0 text-light">Gestión de citas de orientación estudiantil.</p>
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#solicitarCitaModal">
                <i class="bi bi-plus-circle me-1"></i> Solicitar nueva cita
            </button>
        </div>

        <!-- Separador -->
        <hr class="m-0">

        <!-- Contenido -->
        <div class="p-4 bg-light">
            @if(session('success'))
                                        <div class="d-flex flex-wrap gap-2 align-items-center">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                                        </div>
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

                    <div class="card shadow-sm">
                <div class="card-body p-0">
                    <table class="table table-hover table-striped mb-0 align-middle" style="table-layout:fixed; width:100%;">
                        <thead class="table-secondary">
                            <tr>
                                <th>Solicitante</th>
                                <th>Fecha / Hora</th>
                                <th>Tipo</th>
                                <th>Estado</th>
                                <th style="width:240px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($citas as $cita)
                                <tr>
                                    @php
                                        $roleName = strtolower(trim((string)(optional(auth()->user()->role)->nombre ?? '')));
                                        if (empty($roleName) && isset(auth()->user()->roles) && is_object(auth()->user()->roles) && isset(auth()->user()->roles->nombre)) {
                                            $roleName = strtolower(trim((string) auth()->user()->roles->nombre));
                                        }
                                        if (empty($roleName) && isset(auth()->user()->roles_id)) {
                                            try {
                                                $r = \App\Models\RolesModel::find(auth()->user()->roles_id);
                                                $roleName = $r ? strtolower(trim((string)$r->nombre)) : '';
                                            } catch (\Throwable $e) {
                                                $roleName = '';
                                            }
                                        }

                                        $isOrientador = in_array($roleName, ['orientador']);
                                        $isSolicitante = (auth()->id() === $cita->solicitante_id);
                                        $esAcudienteDelEstudiante = optional($cita->estudianteReferido)->acudiente_id === auth()->id();
                                        // Calcular clases para el botón/elemento de Revisión según estado (seguimiento -> azul, completada -> verde)
                                        $revisionBtnClass = ($cita->children && $cita->children->count() > 0)
                                            ? 'btn-outline-info'
                                            : ($cita->esCompletada() ? 'btn-outline-success' : 'btn-outline-info');
                                        $revisionTextClass = ($cita->children && $cita->children->count() > 0)
                                            ? 'text-info'
                                            : ($cita->esCompletada() ? 'text-success' : 'text-info');
                                    @endphp

                                    {{-- ID column removed per request --}}
                                    <td>{{ optional($cita->solicitante)->name ?? 'N/A' }}</td>
                                    <td>
                                        @php
                                            $date = $cita->fecha_solicitada ?? $cita->fecha_asignada;
                                            $time = $cita->hora_solicitada ?? $cita->hora_asignada;
                                        @endphp
                                        @if($date)
                                            {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }} @if($time) - {{ $time }} @endif
                                        @else
                                            --
                                        @endif
                                        @if(!empty($cita->requiere_seguimiento) && !empty($cita->fecha_seguimiento))
                                            <div class="small text-info mt-1">
                                                <i class="bi bi-clock me-1"></i>
                                                Seguimiento: {{ \Carbon\Carbon::parse($cita->fecha_seguimiento)->format('d/m/Y') }}
                                                @if(!empty($cita->hora_seguimiento))
                                                    a las {{ $cita->hora_seguimiento }}
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td>{{ $cita->tipo_cita === 'otro' ? 'Otro' : ($cita->tipo_cita_formateado ?? $cita->tipo_cita) }}</td>
                                    <td>
                                        @if($cita->esCompletada())
                                            <span class="badge bg-success">Atendido</span>
                                        @elseif($cita->esCancelada())
                                            <span class="badge bg-danger">No atendido</span>
                                        @elseif($cita->children && $cita->children->count() > 0)
                                            <span class="badge bg-info">Con seguimiento</span>
                                        @else
                                            <span class="badge bg-secondary">Sin acción aún</span>
                                        @endif
                                    </td>

                                    <td style="width:240px; max-width:240px; white-space:normal; overflow:hidden;">
                                        <div style="display:flex; flex-wrap:wrap; gap:.5rem; width:100%;">
                                            @php
                                                $canViewRevision = !empty($cita->resumen_cita) && ($isSolicitante || $isOrientador || (optional($cita->estudianteReferido)->acudiente_id === auth()->id()));
                                            @endphp

                                        @php
                                            // Bloquear acciones si la cita ya fue atendida o marcada como no atendida
                                            $isFinalizado = $cita->esCompletada() || $cita->esCancelada();
                                        @endphp

                                            <div class="d-none d-sm-flex gap-2 w-100">
                                                @if($isSolicitante)
                                                    @if($canViewRevision)
                                                            <a href="{{ route('citas.show', $cita) }}" class="btn btn-sm btn-success open-cita">
                                                                <i class="bi bi-sticky me-1"></i> Revisión
                                                            </a>
                                                    @endif
                                                @elseif($isOrientador)
                                                    @if(! $isFinalizado)
                                                        <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#atenderModal-{{ $cita->id }}">
                                                            <i class="bi bi-check-circle me-1"></i> Atendió
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#noAsistioModal-{{ $cita->id }}">
                                                            <i class="bi bi-x-circle me-1"></i> No atendió
                                                        </button>
                                                        {{-- Botón para asignar / cambiar orientador responsable --}}
                                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#asignarOrientadorModal-{{ $cita->id }}">
                                                            <i class="bi bi-person-check me-1"></i> Asignar
                                                        </button>
                                                    @else
                                                        <button class="btn btn-sm btn-outline-secondary" disabled title="Acción bloqueada: cita ya finalizada">
                                                            <i class="bi bi-lock-fill me-1"></i> Bloqueado
                                                        </button>
                                                    @endif
                                                    @if($canViewRevision)
                                                            <a href="{{ route('citas.show', $cita) }}" class="btn btn-sm btn-success open-cita">
                                                                <i class="bi bi-sticky me-1"></i> Revisión de notas
                                                            </a>
                                                    @endif
                                                @else
                                                    @if($canViewRevision)
                                                            <a href="{{ route('citas.show', $cita) }}" class="btn btn-sm btn-success open-cita">
                                                                <i class="bi bi-sticky me-1"></i> Revisión
                                                            </a>
                                                    @endif
                                                @endif
                                                {{-- Mostrar botón Motivo cuando la cita fue no atendida --}}
                                                @if($cita->esCancelada() && !empty($cita->motivo_cancelacion))
                                                    <a href="{{ route('citas.show', $cita) }}" class="btn btn-sm btn-outline-danger ms-1 open-cita">
                                                        <i class="bi bi-chat-left-text me-1"></i> Motivo
                                                    </a>
                                                @endif
                                            </div>

                                            <div class="d-sm-none dropdown w-100">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle w-100" type="button" id="accionesOrient{{ $cita->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                                    Acciones
                                                </button>
                                                <ul class="dropdown-menu" aria-labelledby="accionesOrient{{ $cita->id }}">
                                                    @if($isSolicitante)
                                                    @if($canViewRevision)
                                                        <li><a class="dropdown-item open-cita text-success" href="{{ route('citas.show', $cita) }}">Revisión</a></li>
                                                    @endif
                                                    @elseif($isOrientador)
                                                        @if(! $isFinalizado)
                                                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#atenderModal-{{ $cita->id }}">Atendió</a></li>
                                                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#noAsistioModal-{{ $cita->id }}">No atendió</a></li>
                                                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#asignarOrientadorModal-{{ $cita->id }}">Asignar orientador</a></li>
                                                        @else
                                                            <li><span class="dropdown-item text-muted">Acciones bloqueadas (cita finalizada)</span></li>
                                                        @endif
                                                        @if($canViewRevision)
                                                            <li><a class="dropdown-item open-cita text-success" href="{{ route('citas.show', $cita) }}">Revisión de notas</a></li>
                                                        @endif
                                                    @else
                                                        @if($canViewRevision)
                                                            <li><a class="dropdown-item open-cita text-success" href="{{ route('citas.show', $cita) }}">Revisión</a></li>
                                                        @endif
                                                    @endif
                                                    @if($cita->esCancelada() && !empty($cita->motivo_cancelacion))
                                                        <li><a class="dropdown-item open-cita text-danger" href="{{ route('citas.show', $cita) }}">Motivo</a></li>
                                                    @endif
                                                </ul>
                                            </div>
                                        </div>
                                    </td>
                                </tr>

                                    {{-- Modales por cita (solo orientador) --}}
                                    @if($isOrientador && ! $isFinalizado)
                                        {{-- Modal: Atender / completar --}}
                                        <div class="modal fade" id="atenderModal-{{ $cita->id }}" tabindex="-1">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Registrar Atención - Cita #{{ $cita->id }}</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form action="{{ route('orientacion.citas.completar', $cita->id) }}" method="POST">
                                                        @csrf
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label class="form-label">Observaciones / Resumen <span class="text-danger">*</span></label>
                                                                <textarea name="resumen_cita" class="form-control" rows="5" required></textarea>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-label">Recomendaciones</label>
                                                                <textarea name="recomendaciones" class="form-control" rows="3"></textarea>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-label">Plan de seguimiento (opcional)</label>
                                                                <textarea name="plan_seguimiento" class="form-control" rows="3"></textarea>
                                                            </div>

                                                            <div class="form-check mb-3">
                                                                <input class="form-check-input" type="checkbox" value="1" id="requiere_seguimiento_{{ $cita->id }}" name="requiere_seguimiento">
                                                                <label class="form-check-label" for="requiere_seguimiento_{{ $cita->id }}">Requiere seguimiento</label>
                                                            </div>

                                                            <div class="row">
                                                                <div class="col-md-6 mb-3">
                                                                    <label class="form-label">Fecha de seguimiento (si aplica)</label>
                                                                    <input type="date" name="fecha_seguimiento" class="form-control" min="{{ date('Y-m-d', strtotime('+1 day')) }}">
                                                                </div>
                                                                <div class="col-md-6 mb-3">
                                                                    <label class="form-label">Hora de seguimiento (si aplica)</label>
                                                                    <input type="time" name="hora_seguimiento" class="form-control">
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                                            <button type="submit" class="btn btn-success">Guardar y Marcar como Completada</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Modal: No asistió / cancelar --}}
                                        <div class="modal fade" id="noAsistioModal-{{ $cita->id }}" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Registrar No Atención - Cita #{{ $cita->id }}</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form action="{{ route('orientacion.citas.cancelar', $cita->id) }}" method="POST">
                                                        @csrf
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label class="form-label">Motivo por el cual no se atendió la cita <span class="text-danger">*</span></label>
                                                                <textarea name="motivo_cancelacion" class="form-control" rows="4" required></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                                            <button type="submit" class="btn btn-danger">Registrar No Atención</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        {{-- Modal: Asignar / cambiar orientador --}}
                                        <div class="modal fade" id="asignarOrientadorModal-{{ $cita->id }}" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Asignar Orientador - Cita #{{ $cita->id }}</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form action="{{ route('orientacion.citas.asignar_orientador', $cita->id) }}" method="POST">
                                                        @csrf
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label class="form-label">Orientador</label>
                                                                <select name="orientador_id" class="form-select" required>
                                                                    <option value="">-- Selecciona orientador --</option>
                                                                    @foreach($orientadores ?? [] as $ori)
                                                                        <option value="{{ $ori->id }}" {{ $cita->orientador_id == $ori->id ? 'selected' : '' }}>{{ $ori->name }}</option>
                                                                    @endforeach
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
                                    @endif
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        <i class="bi bi-info-circle me-1"></i> No hay citas registradas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

            <!-- Modal: Solicitar Cita -->
            <div class="modal fade" id="solicitarCitaModal" tabindex="-1" aria-labelledby="solicitarCitaModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="solicitarCitaModalLabel">Solicitar cita</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <form action="{{ route('orientacion.citas.store') }}" method="POST" class="row g-3" id="formSolicitarCita">
                                    @csrf

                                    <div class="col-12">
                                        <label for="fecha_modal" class="form-label">Fecha y hora</label>
                                        <input type="datetime-local" id="fecha_modal" name="fecha" class="form-control" required value="{{ old('fecha') }}">
                                        <div class="form-text">Horario disponible: 08:00–11:59 y 13:00–16:00</div>
                                </div>

                                    <div class="col-12">
                                        <label for="tipo_cita" class="form-label">Tipo de cita</label>
                                        <select name="tipo_cita" id="tipo_cita" class="form-select" required>
                                            <option value="">-- Selecciona tipo de cita --</option>
                                            <option value="orientacion" {{ old('tipo_cita') == 'orientacion' ? 'selected' : '' }}>Orientación Académica</option>
                                            <option value="psicologica" {{ old('tipo_cita') == 'psicologica' ? 'selected' : '' }}>Orientación Psicológica</option>
                                            <option value="vocacional" {{ old('tipo_cita') == 'vocacional' ? 'selected' : '' }}>Orientación Vocacional</option>
                                            <option value="otro" {{ old('tipo_cita') == 'otro' ? 'selected' : '' }}>Otro</option>
                                        </select>
                                    </div>

                                    <div class="col-12">
                                        <label for="motivo_modal" class="form-label">Descripción breve</label>
                                        <textarea id="motivo_modal" name="motivo" class="form-control" rows="3" required>{{ old('motivo') }}</textarea>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">Solicitante</label>
                                        @if(isset($students) && $students && $students->count() > 0)
                                            <select name="estudiante_id" class="form-select" required>
                                                <option value="">-- Selecciona un estudiante --</option>
                                                @foreach($students as $st)
                                                    <option value="{{ $st->id }}" {{ old('estudiante_id') == $st->id ? 'selected' : '' }}>{{ $st->name }}</option>
                                                @endforeach
                                            </select>
                                        @else
                                            <input type="text" class="form-control" value="{{ auth()->user()->name }}" disabled>
                                            <input type="hidden" name="estudiante_id" value="{{ auth()->id() }}">
                                        @endif
                                    </div>

                                    <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                            <button type="submit" class="btn btn-success">Solicitar</button>
                                    </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            @if($errors->any() && old('fecha'))
                    <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                    var myModal = new bootstrap.Modal(document.getElementById('solicitarCitaModal'));
                                    myModal.show();
                            });
                    </script>
            @endif

            <!-- Modal: Mostrar motivo completo -->
            <div class="modal fade" id="motivoModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Motivo</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body" id="motivoModalBody" style="white-space:pre-wrap;"></div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // inicializar tooltips
                    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                    tooltipTriggerList.forEach(function (el) { new bootstrap.Tooltip(el); });

                    // mostrar motivo completo en modal
                    document.querySelectorAll('.ver-motivo').forEach(function(btn){
                        btn.addEventListener('click', function(e){
                            e.preventDefault();
                            var motivo = this.getAttribute('data-motivo') || '';
                            var body = document.getElementById('motivoModalBody');
                            body.textContent = motivo;
                            var modal = new bootstrap.Modal(document.getElementById('motivoModal'));
                            modal.show();
                        });
                    });
                });
            </script>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var fechaInput = document.getElementById('fecha_modal');
                    var form = document.getElementById('formSolicitarCita');

                    function validarHoraLocal(value) {
                        if (!value) return false;
                        var dt = new Date(value);
                        var h = dt.getHours();
                        var m = dt.getMinutes();
                        var minutes = h * 60 + m;
                        var morningStart = 8 * 60;
                        var morningEnd = 12 * 60; // exclusive
                        var afternoonStart = 13 * 60;
                        var afternoonEnd = 16 * 60; // inclusive

                        var isMorning = (minutes >= morningStart && minutes < morningEnd);
                        var isAfternoon = (minutes >= afternoonStart && minutes <= afternoonEnd);
                        return (isMorning || isAfternoon);
                    }

                    form.addEventListener('submit', function(e) {
                        var val = fechaInput.value;
                        if (!validarHoraLocal(val)) {
                            e.preventDefault();
                            alert('La hora seleccionada no está disponible. Usa horario 08:00–11:59 o 13:00–16:00.');
                        }
                    });
                });
            </script>
@endsection
