@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="mb-0">Mis Matrículas</h2>
            <small class="text-muted">Listado de matrículas registradas en tu cuenta.</small>
        </div>
        <a class="btn btn-primary" href="{{ route('matriculas.create') }}">Nueva Matrícula</a>
    </div>

    @if ($message = Session::get('success'))
        <div class="alert alert-success">{{ $message }}</div>
    @endif

    @if ($matriculas->count() === 0)
        <div class="alert alert-info">Aún no has registrado matrículas.</div>
    @else
        <div class="list-group">
            @foreach ($matriculas as $m)
                <div class="list-group-item list-group-item-action mb-2">
                    <div class="d-flex w-100 justify-content-between align-items-start">
                        <div class="me-3 flex-grow-1">
                            <h5 class="mb-1">
                                {{-- Preferir datos del usuario relacionado, si existen --}}
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

                            <small class="text-muted">Registrada: {{ $m->created_at ? $m->created_at->format('Y-m-d H:i') : ($m->fecha_matricula ?? '') }}</small>

                            {{-- Enlaces a documentos si existen --}}
                            <div class="mt-2">
                                @if($m->documento_identidad_url)
                                    <a class="btn btn-outline-primary btn-sm me-1" href="{{ $m->documento_identidad_url }}" target="_blank">Documento de identidad</a>
                                @endif
                                @if($m->rh_url)
                                    <a class="btn btn-outline-secondary btn-sm me-1" href="{{ $m->rh_url }}" target="_blank">RH</a>
                                @endif
                                @if($m->certificado_medico_url)
                                    <a class="btn btn-outline-info btn-sm me-1" href="{{ $m->certificado_medico_url }}" target="_blank">Certificado médico</a>
                                @endif
                                @if($m->certificado_notas_url)
                                    <a class="btn btn-outline-warning btn-sm me-1" href="{{ $m->certificado_notas_url }}" target="_blank">Registro de notas</a>
                                @endif
                            </div>
                        </div>

                        <div class="text-end">
                            <a class="btn btn-sm btn-info mb-1 d-block" href="{{ route('matriculas.show', $m->id) }}">Ver</a>
                            <a class="btn btn-sm btn-primary mb-1 d-block" href="{{ route('matriculas.edit', $m->id) }}">Editar</a>
                            <form action="{{ route('matriculas.destroy', $m->id) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar esta matrícula?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger d-block">Eliminar</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-3">
            @if (method_exists($matriculas, 'links'))
                {{ $matriculas->links() }}
            @endif
        </div>
    @endif
</div>
@endsection
