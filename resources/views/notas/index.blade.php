@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4>Listado de Notas</h4>
            <a href="{{ route('notas.create') }}" class="btn btn-sm btn-primary">Nueva</a>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-2 mb-3">
                <div class="col-md-3">
                    <select name="curso_id" class="form-select">
                        <option value="">-- Curso --</option>
                        @foreach($cursos as $c)
                            <option value="{{ $c->id }}" {{ request('curso_id') == $c->id ? 'selected' : '' }}>{{ $c->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="materia_id" class="form-select">
                        <option value="">-- Materia --</option>
                        @foreach($materias as $m)
                            <option value="{{ $m->id }}" {{ request('materia_id') == $m->id ? 'selected' : '' }}>{{ $m->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="text" name="periodo" class="form-control" placeholder="Periodo (ej: 2025-10)" value="{{ request('periodo') }}">
                </div>
                <div class="col-md-3">
                    <button class="btn btn-secondary">Filtrar</button>
                </div>
            </form>

            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Estudiante</th>
                        <th>Materia</th>
                        <th>Periodo</th>
                        <th>Valor</th>
                        <th>Aprobada</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($notas as $nota)
                        <tr>
                            <td>{{ $nota->matricula->user->name ?? 'N/A' }}</td>
                            <td>{{ $nota->materia->nombre ?? 'N/A' }}</td>
                            <td>{{ $nota->periodo }}</td>
                            <td>{{ number_format($nota->valor, 2) }}</td>
                            <td>{!! $nota->aprobada ? '<span class="badge bg-success">Sí</span>' : '<span class="badge bg-warning">No</span>' !!}</td>
                            <td class="text-end">
                                <a href="{{ route('notas.edit', $nota) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                                @if(! $nota->aprobada && optional(Auth::user())->roles_id == 1)
                                    <form action="{{ route('notas.approve', $nota) }}" method="POST" style="display:inline-block" onsubmit="return confirm('Aprobar nota?')">
                                        @csrf
                                        <button class="btn btn-sm btn-success">Aprobar</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{ $notas->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection
