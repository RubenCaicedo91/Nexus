@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="mb-4 fw-bold">🔔 Notificaciones</h2>

    <div class="row">
        @forelse($notificaciones as $notif)
            <div class="col-md-4 mb-3">
                <div class="card shadow-sm h-100 {{ $notif->leida ? 'border-secondary' : 'border-warning' }}">
                    <div class="card-header d-flex align-items-center">
                        <i class="fas fa-bell me-2 {{ $notif->leida ? 'text-secondary' : 'text-warning' }}"></i>
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
                                    <i class="fas fa-check"></i> Marcar como leída
                                </button>
                            </form>
                        @else
                            <span class="text-muted"><i class="fas fa-eye"></i> Leída</span>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle"></i> No hay notificaciones disponibles
                </div>
            </div>
        @endforelse
    </div>
</div>
@endsection
