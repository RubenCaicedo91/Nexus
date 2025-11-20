@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm rounded overflow-hidden">

        <!-- Encabezado oscuro con degradado -->
        <div class="bg-dark text-white p-3 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-semibold mb-0">
                    <i class="bi bi-journal-check me-2 text-warning"></i> Mis Matrículas
                </h2>
                <p class="small mb-0 text-light">Listado de matrículas registradas en tu cuenta</p>
            </div>
            <a class="btn btn-primary" href="{{ route('matriculas.create') }}">
                <i class="bi bi-plus-circle me-1"></i> Nueva Matrícula
            </a>
        </div>

        <!-- Separador -->
        <hr class="m-0">

        <!-- Contenido -->
        <div class="p-4 bg-light">
            @if ($message = Session::get('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ $message }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            @endif

            @if ($matriculas->count() === 0)
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-1"></i> Aún no has registrado matrículas.
                </div>
            @else
                <div class="list-group">
                    @foreach ($matriculas as $m)
                        <div class="list-group-item list-group-item-action mb-3 shadow-sm rounded">
                            <div class="d-flex w-100 justify-content-between align-items-start">
                                <div class="me-3 flex-grow-1">
                                    <h5 class="mb-1 fw-bold">
                                        @if(optional($m->user)->name)
                                            {{ optional($m->user)->name }}
                                        @else
                                            {{ ($m->nombres ?? '') . ' ' . ($m->apellidos ?? '') }}
                                        @endif
                                        @if(optional($m->curso)->nombre)
                                            <small class="text-muted">— {{ optional($m->curso)->nombre }}</small>
                                        @endif
                                    </h5>

                                    <p class="mb-1">Estado:
                                        @php
                                            $estado = strtolower($m->estado ?? 'desconocido');
                                        @endphp
                                        @if($estado === 'activo')
                                            <span class="badge bg-success">Activo</span>
                                        @elseif(str_contains($estado, 'falta'))
                                            <span class="badge bg-warning text-dark">Falta documentación</span>
                                        @else
                                            <span class="badge bg-secondary">{{ ucfirst($estado) }}</span>
                                        @endif
                                    </p>

                                    <small class="text-muted">
                                        Registrada: {{ $m->created_at ? $m->created_at->format('Y-m-d H:i') : ($m->fecha_matricula ?? '') }}
                                    </small>

                                    <!-- Documentos -->
                                    <div class="mt-2">
                                        @if($m->documento_identidad_url)
                                            <a class="btn btn-outline-primary btn-sm me-1 preview-file" href="{{ $m->documento_identidad_url }}">Documento de identidad</a>
                                        @endif
                                        @if($m->rh_url)
                                            <a class="btn btn-outline-secondary btn-sm me-1 preview-file" href="{{ $m->rh_url }}">RH</a>
                                        @endif
                                        @if($m->comprobante_pago_url)
                                            <a class="btn btn-outline-success btn-sm me-1 preview-file" href="{{ $m->comprobante_pago_url }}">Comprobante pago</a>
                                        @endif
                                        @if($m->certificado_medico_url)
                                            <a class="btn btn-outline-info btn-sm me-1 preview-file" href="{{ $m->certificado_medico_url }}">Certificado médico</a>
                                        @endif
                                        @if($m->certificado_notas_url)
                                            <a class="btn btn-outline-warning btn-sm me-1 preview-file" href="{{ $m->certificado_notas_url }}">Registro de notas</a>
                                        @endif
                                    </div>
                                </div>

                                <!-- Acciones -->
                                <div class="text-end">
                                    <a class="btn btn-sm btn-info mb-1 d-block" href="{{ route('matriculas.show', $m->id) }}">
                                        <i class="bi bi-eye me-1"></i> Ver
                                    </a>
                                    <a class="btn btn-sm btn-primary mb-1 d-block" href="{{ route('matriculas.edit', $m->id) }}">
                                        <i class="bi bi-pencil-square me-1"></i> Editar
                                    </a>
                                    <form action="{{ route('matriculas.destroy', $m->id) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar esta matrícula?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger d-block">
                                            <i class="bi bi-trash me-1"></i> Eliminar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Paginación -->
                <div class="mt-3">
                    @if (method_exists($matriculas, 'links'))
                        {{ $matriculas->links() }}
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

@include('matriculas._file_preview_modal')
@endsection
