@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Seguimientos</h2>
        <a href="{{ route('orientacion.seguimientos.create') }}" class="btn btn-info">Nuevo seguimiento</a>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Estudiante</th>
                        <th>Fecha</th>
                        <th>Observaciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($seguimientos as $seg)
                        <tr>
                            <td>{{ $seg->id }}</td>
                            <td>{{ $seg->estudiante_id }}</td>
                            <td>{{ \Carbon\Carbon::parse($seg->fecha)->format('d/m/Y') }}</td>
                            <td>{{ Str::limit($seg->observaciones, 80) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-4">No hay seguimientos registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection