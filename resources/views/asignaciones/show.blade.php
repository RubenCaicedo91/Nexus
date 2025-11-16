@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-eye me-2"></i>Detalles de Asignación
                    </h4>
                    <div>
                        <a href="{{ route('asignaciones.edit', $asignacion) }}" class="btn btn-light me-2">
                            <i class="fas fa-edit me-2"></i>Editar
                        </a>
                        <a href="{{ route('asignaciones.index') }}" class="btn btn-outline-light">
                            <i class="fas fa-arrow-left me-2"></i>Volver al Listado
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        {{-- Información del estudiante --}}
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">
                                        <i class="fas fa-user me-2"></i>Información del Estudiante
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        <tr>
                                            <th width="40%">Nombre:</th>
                                            <td>{{ $asignacion->user->name }}</td>
                                        </tr>
                                        <tr>
                                            <th>Email:</th>
                                            <td>{{ $asignacion->user->email }}</td>
                                        </tr>
                                        <tr>
                                            <th>Curso Asignado:</th>
                                            <td>
                                                <span class="badge bg-info fs-6">{{ $asignacion->curso->nombre ?? 'Sin asignar' }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Fecha de Matrícula:</th>
                                            <td>{{ \Carbon\Carbon::parse($asignacion->fecha_matricula)->format('d/m/Y') }}</td>
                                        </tr>
                                        <tr>
                                            <th>Estado:</th>
                                            <td>
                                                @switch($asignacion->estado)
                                                    @case('activa')
                                                        <span class="badge bg-success fs-6">Activa</span>
                                                        @break
                                                    @case('inactiva')
                                                        <span class="badge bg-secondary fs-6">Inactiva</span>
                                                        @break
                                                    @case('suspendido')
                                                        <span class="badge bg-warning fs-6">Suspendido</span>
                                                        @break
                                                    @default
                                                        <span class="badge bg-light text-dark fs-6">{{ $asignacion->estado }}</span>
                                                @endswitch
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        {{-- Información de pago --}}
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">
                                        <i class="fas fa-money-bill me-2"></i>Información de Pago
                                    </h5>
                                </div>
                                <div class="card-body">
                                    @if($asignacion->monto_pago && $asignacion->fecha_pago)
                                        <table class="table table-sm">
                                            <tr>
                                                <th width="40%">Monto:</th>
                                                <td class="text-success fw-bold">${{ number_format($asignacion->monto_pago, 0, ',', '.') }}</td>
                                            </tr>
                                            <tr>
                                                <th>Fecha de Pago:</th>
                                                <td>{{ \Carbon\Carbon::parse($asignacion->fecha_pago)->format('d/m/Y') }}</td>
                                            </tr>
                                            <tr>
                                                <th>Comprobante:</th>
                                                <td>
                                                    @if($asignacion->comprobante_pago)
                                                        <a href="{{ route('matriculas.archivo', ['matricula' => $asignacion->id, 'campo' => 'comprobante_pago']) }}" 
                                                           class="btn btn-sm btn-outline-primary" target="_blank">
                                                            <i class="fas fa-file me-1"></i>Ver Comprobante
                                                        </a>
                                                    @else
                                                        <span class="text-muted">No disponible</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        </table>
                                    @else
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            No se ha registrado información de pago para esta asignación.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Estado de documentos --}}
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">
                                <i class="fas fa-file-alt me-2"></i>Estado de Documentos
                                @if($asignacion->tieneDocumentosCompletos())
                                    <span class="badge bg-success ms-2">Completos</span>
                                @else
                                    <span class="badge bg-danger ms-2">Incompletos</span>
                                @endif
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @php
                                    $documentos = [
                                        'documento_identidad' => 'Documento de Identidad',
                                        'rh' => 'Certificado de RH',
                                        'certificado_medico' => 'Certificado Médico',
                                        'certificado_notas' => 'Certificado de Notas',
                                        'comprobante_pago' => 'Comprobante de Pago'
                                    ];
                                @endphp

                                @foreach($documentos as $campo => $nombre)
                                    <div class="col-md-4 mb-3">
                                        <div class="d-flex align-items-center">
                                            @if($asignacion->$campo)
                                                <i class="fas fa-check-circle text-success me-2"></i>
                                                <div>
                                                    <strong>{{ $nombre }}</strong>
                                                    <br>
                                                    <a href="{{ route('matriculas.archivo', ['matricula' => $asignacion->id, 'campo' => $campo]) }}" 
                                                       class="btn btn-sm btn-outline-primary" target="_blank">
                                                        <i class="fas fa-eye me-1"></i>Ver
                                                    </a>
                                                </div>
                                            @else
                                                <i class="fas fa-times-circle text-danger me-2"></i>
                                                <div>
                                                    <strong>{{ $nombre }}</strong>
                                                    <br>
                                                    <small class="text-muted">No cargado</small>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if(!$asignacion->tieneDocumentosCompletos())
                                <div class="alert alert-warning mt-3">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Atención:</strong> Esta asignación no puede ser activada hasta que todos los documentos estén completos.
                                    Faltan documentos por cargar.
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Horarios del curso --}}
                    @if($asignacion->curso && count($horarios) > 0)
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">
                                    <i class="fas fa-clock me-2"></i>Horarios del Curso: {{ $asignacion->curso->nombre }}
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Día</th>
                                                <th>Hora</th>
                                                <th>Curso</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($horarios as $horario)
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-primary">{{ $horario->dia }}</span>
                                                    </td>
                                                    <td>
                                                        <i class="fas fa-clock me-1"></i>{{ $horario->hora }}
                                                    </td>
                                                    <td>{{ $horario->curso }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @elseif($asignacion->curso)
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">
                                    <i class="fas fa-clock me-2"></i>Horarios del Curso: {{ $asignacion->curso->nombre }}
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    No hay horarios definidos para este curso aún.
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Información adicional --}}
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>Información Adicional
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-sm">
                                        <tr>
                                            <th width="40%">ID de Asignación:</th>
                                            <td>{{ $asignacion->id }}</td>
                                        </tr>
                                        <tr>
                                            <th>Fecha de Registro:</th>
                                            <td>{{ $asignacion->created_at->format('d/m/Y H:i:s') }}</td>
                                        </tr>
                                        <tr>
                                            <th>Última Actualización:</th>
                                            <td>{{ $asignacion->updated_at->format('d/m/Y H:i:s') }}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <div class="alert alert-info">
                                        <h6><i class="fas fa-lightbulb me-2"></i>Acciones Disponibles:</h6>
                                        <ul class="mb-0">
                                            <li>Editar información básica y documentos</li>
                                            <li>Cambiar estado de la asignación</li>
                                            <li>Actualizar información de pago</li>
                                            @if($asignacion->curso)
                                                <li>Ver horarios del curso asignado</li>
                                            @endif
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection