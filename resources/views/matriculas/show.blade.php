@extends('layouts.app')

@section('title', 'Detalle Matrícula')

@section('content')
@php /** @var \App\Models\Matricula $matricula */ @endphp
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Matrícula</h2>
        <div>
            <a class="btn btn-outline-secondary me-2" href="{{ route('matriculas.index') }}">
                <i class="fas fa-arrow-left me-1"></i> Volver
            </a>
            <a class="btn btn-primary me-2" href="{{ route('matriculas.edit', $matricula->id) }}">Editar</a>
            <form action="{{ route('matriculas.destroy', $matricula->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar esta matrícula?');">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger">Eliminar</button>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <strong>Estudiante:</strong>
                    <div>{{ optional($matricula->user)->name ?? trim(($matricula->nombres ?? '') . ' ' . ($matricula->apellidos ?? '')) }}</div>
                </div>
                <div class="col-md-6">
                    <strong>Curso:</strong>
                    <div>{{ optional($matricula->curso)->nombre ?? '—' }}</div>
                </div>

                <div class="col-md-6">
                    <strong>Fecha de Matrícula:</strong>
                    <div>{{ $matricula->fecha_matricula ? 
                        \Carbon\Carbon::parse($matricula->fecha_matricula)->format('Y-m-d') : '—' }}</div>
                </div>

                <div class="col-md-6">
                    <strong>Estado:</strong>
                    <div>
                        @php $est = strtolower($matricula->estado ?? 'desconocido'); @endphp
                        @if($est === 'activo')
                            <span class="badge bg-success">Activo</span>
                        @elseif(str_contains($est, 'falta'))
                            <span class="badge bg-warning text-dark">Falta documentación</span>
                        @else
                            <span class="badge bg-secondary">{{ ucfirst($matricula->estado) }}</span>
                        @endif
                    </div>
                </div>

                <div class="col-md-6">
                    <strong>Documento identidad:</strong>
                    <div>
                        @if($matricula->documento_identidad)
                            @php $name = basename($matricula->documento_identidad); @endphp
                            <a class="preview-file" href="{{ route('matriculas.archivo', ['matricula' => $matricula->id, 'campo' => 'documento_identidad']) }}" data-href="{{ route('matriculas.archivo', ['matricula' => $matricula->id, 'campo' => 'documento_identidad']) }}" target="_blank">{{ $name }}</a>
                        @else
                            —
                        @endif
                    </div>
                </div>

                <div class="col-md-6">
                    <strong>RH:</strong>
                    <div>
                        @if($matricula->rh)
                            <a class="preview-file" href="{{ route('matriculas.archivo', ['matricula' => $matricula->id, 'campo' => 'rh']) }}" data-href="{{ route('matriculas.archivo', ['matricula' => $matricula->id, 'campo' => 'rh']) }}" target="_blank">{{ basename($matricula->rh) }}</a>
                        @else
                            —
                        @endif
                    </div>
                </div>

                <div class="col-md-6">
                    <strong>Certificado médico:</strong>
                    <div>
                        @if($matricula->certificado_medico)
                            <a class="preview-file" href="{{ route('matriculas.archivo', ['matricula' => $matricula->id, 'campo' => 'certificado_medico']) }}" data-href="{{ route('matriculas.archivo', ['matricula' => $matricula->id, 'campo' => 'certificado_medico']) }}" target="_blank">{{ basename($matricula->certificado_medico) }}</a>
                        @else
                            —
                        @endif
                    </div>
                </div>

                <div class="col-md-6">
                    <strong>Registro de notas:</strong>
                    <div>
                        @if($matricula->certificado_notas)
                            <a class="preview-file" href="{{ route('matriculas.archivo', ['matricula' => $matricula->id, 'campo' => 'certificado_notas']) }}" data-href="{{ route('matriculas.archivo', ['matricula' => $matricula->id, 'campo' => 'certificado_notas']) }}" target="_blank">{{ basename($matricula->certificado_notas) }}</a>
                        @else
                            —
                        @endif
                    </div>
                </div>

                <div class="col-md-6">
                    <strong>Comprobante de pago (Matrícula):</strong>
                    <div>
                        @if($matricula->comprobante_pago)
                            <a class="preview-file" href="{{ route('matriculas.archivo', ['matricula' => $matricula->id, 'campo' => 'comprobante_pago']) }}" data-href="{{ route('matriculas.archivo', ['matricula' => $matricula->id, 'campo' => 'comprobante_pago']) }}" target="_blank">{{ basename($matricula->comprobante_pago) }}</a>
                        @else
                            —
                        @endif
                    </div>
                </div>

                <div class="col-12">
                    <strong>Contacto:</strong>
                    <div>
                        <div>Email: {{ $matricula->email ?? '—' }}</div>
                        <div>Teléfono: {{ $matricula->telefono ?? '—' }}</div>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <a href="{{ route('matriculas.index') }}" class="btn btn-outline-secondary">Volver</a>
            </div>
        </div>
    </div>

</div>
@include('matriculas._file_preview_modal')
@endsection
