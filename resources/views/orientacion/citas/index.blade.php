@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Citas</h2>
        <a href="{{ route('orientacion.citas.create') }}" class="btn btn-primary">Solicitar nueva cita</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
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

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead class="table-light">
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
                            <td><span class="badge bg-secondary text-uppercase">{{ $cita->estado }}</span></td>
                            <td>
                                <form action="{{ route('orientacion.citas.estado', $cita->id) }}" method="POST" class="d-flex gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="estado" value="agendada">
                                    <button class="btn btn-sm btn-outline-primary" {{ $cita->estado !== 'pendiente' ? 'disabled' : '' }}>
                                        Agendar
                                    </button>
                                </form>

                                <form action="{{ route('orientacion.citas.estado', $cita->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="estado" value="atendida">
                                    <button class="btn btn-sm btn-outline-success" {{ $cita->estado !== 'agendada' ? 'disabled' : '' }}>
                                        Marcar atendida
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4">No hay citas registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection