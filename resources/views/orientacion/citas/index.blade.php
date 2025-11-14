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
            <a href="{{ route('orientacion.citas.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> Solicitar nueva cita
            </a>
        </div>

        <!-- Separador -->
        <hr class="m-0">

        <!-- Contenido -->
        <div class="p-4 bg-light">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <table class="table table-hover table-striped mb-0 align-middle">
                        <thead class="table-secondary">
                            <tr>
                                <th>ID</th>
                                <th>Estudiante</th>
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th style="width:180px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($citas as $cita)
                                <tr>
                                    <td>{{ $cita->id }}</td>
                                    <td>{{ $cita->estudiante_id }}</td>
                                    <td>{{ \Carbon\Carbon::parse($cita->fecha)->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <span class="badge 
                                            @if($cita->estado === 'pendiente') bg-warning text-dark 
                                            @elseif($cita->estado === 'agendada') bg-primary 
                                            @else bg-success @endif
                                            text-uppercase">
                                            {{ $cita->estado }}
                                        </span>
                                    </td>
                                    <td>
                                        <form action="{{ route('orientacion.citas.estado', $cita->id) }}" method="POST" class="d-flex gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="estado" value="agendada">
                                            <button class="btn btn-sm btn-outline-primary" {{ $cita->estado !== 'pendiente' ? 'disabled' : '' }}>
                                                <i class="bi bi-calendar-plus me-1"></i> Agendar
                                            </button>
                                        </form>

                                        <form action="{{ route('orientacion.citas.estado', $cita->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="estado" value="atendida">
                                            <button class="btn btn-sm btn-outline-success" {{ $cita->estado !== 'agendada' ? 'disabled' : '' }}>
                                                <i class="bi bi-check-circle me-1"></i> Atendida
                                            </button>
                                        </form>
                                    </td>
                                </tr>
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
@endsection
