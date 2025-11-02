@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Informes psicosociales</h2>
        <a href="{{ route('orientacion.informes.create') }}" class="btn btn-success">Nuevo informe</a>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Cita</th>
                        <th>Fecha cita</th>
                        <th>Descripción</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($informes as $informe)
                        <tr>
                            <td>{{ $informe->id }}</td>
                            <td>#{{ $informe->cita_id }}</td>
                            <td>{{ optional($informe->cita)->fecha }}</td>
                            <td>{{ Str::limit($informe->descripcion, 80) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-4">No hay informes registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection