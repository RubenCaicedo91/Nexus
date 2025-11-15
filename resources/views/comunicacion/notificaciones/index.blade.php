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
                                    <form action="{{ route('comunicacion.notificaciones') }}" method="POST">
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
