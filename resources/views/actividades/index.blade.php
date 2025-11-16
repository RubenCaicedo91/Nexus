
@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <strong>Actividades:</strong> {{ $nota->matricula->user->name ?? 'N/A' }} - {{ $nota->materia->nombre ?? 'N/A' }}
            </div>
            <div>
                <a href="{{ route('notas.index') }}" class="btn btn-sm btn-secondary">Volver</a>
                <a href="{{ route('notas.actividades.create', $nota) }}" class="btn btn-sm btn-primary">Crear actividad</a>
            </div>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Valor (0-5)</th>
                        <th>Creado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($nota->actividades as $act)
                        <tr>
                            <td>{{ $act->nombre }}</td>
                            <td>{{ number_format($act->valor, 2) }}</td>
                            <td>{{ $act->created_at->format('Y-m-d') }}</td>
                            <td class="text-end">
                                <form action="{{ route('notas.actividades.destroy', [$nota, $act]) }}" method="POST" onsubmit="return confirm('Eliminar actividad?')" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="mt-3">
                <strong>Promedio actividades:</strong>
                @if($nota->actividades->count() > 0)
                    {{ number_format($nota->actividades->avg('valor'), 2) }}
                @else
                    --
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
