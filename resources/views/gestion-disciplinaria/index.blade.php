<!-- index.blade.php -->
@extends('layouts.app')
@section('content')
<h2>Gestión Disciplinaria</h2>
<p>Selecciona una opción para continuar:</p>

<div class="d-grid gap-3 col-6 mx-auto mt-4">
    <a href="{{ route('gestion-disciplinaria.registrar') }}" class="btn btn-outline-primary btn-lg">
        <i class="fas fa-gavel me-2"></i> Registrar Sanción
    </a>
    <a href="{{ route('historial.sanciones', auth()->user()->id ?? 1) }}" class="btn btn-outline-secondary btn-lg">
        <i class="fas fa-list-alt me-2"></i> Historial de Sanciones
    </a>
    <a href="{{ route('gestion-disciplinaria.reporte') }}" class="btn btn-outline-success btn-lg">
        <i class="fas fa-file-alt me-2"></i> Reporte Disciplinario
    </a>
</div>


@endsection