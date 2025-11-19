{{-- index.blade.php --}}
@extends('layouts.app')
@section('content')
<h2>Reporte Disciplinario</h2>

<!-- Filtros: rango de fechas y tipo de sanción -->
<form method="get" class="row g-3 mb-4">
    <div class="col-md-3">
        <label for="start_date" class="form-label">Fecha desde</label>
        <input type="date" id="start_date" name="start_date" class="form-control" value="{{ request()->query('start_date') }}">
    </div>
    <div class="col-md-3">
        <label for="end_date" class="form-label">Fecha hasta</label>
        <input type="date" id="end_date" name="end_date" class="form-control" value="{{ request()->query('end_date') }}">
    </div>
    <div class="col-md-4">
        <label for="tipo_id" class="form-label">Tipo de sanción</label>
        <select id="tipo_id" name="tipo_id" class="form-select">
            <option value="">Todos</option>
            @if(isset($tipos))
                @foreach($tipos as $tipo)
                    <option value="{{ $tipo->id }}" {{ request()->query('tipo_id') == $tipo->id ? 'selected' : '' }}>{{ $tipo->nombre }}</option>
                @endforeach
            @endif
        </select>
    </div>
    <div class="col-md-2 d-flex align-items-end">
        <div>
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="{{ route('gestion-disciplinaria.reporte') }}" class="btn btn-secondary ms-2">Limpiar</a>
            <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="btn btn-outline-danger ms-2">Exportar PDF</a>
            <a href="{{ request()->fullUrlWithQuery(['export' => 'excel']) }}" class="btn btn-outline-primary ms-2">Exportar Excel</a>
        </div>
    </div>
</form>
<table class="table">
    <thead>
        <tr>
            <th>Estudiante</th>
            <th>Descripción Sanción</th>
            <th>Tipo Sanción</th>
            <th>Fecha Registro</th>
        </tr>
    </thead>
    <tbody>
        @foreach($reporte as $sancion)
        @php $doc = optional($sancion->usuario)->document_number ?? '' ; @endphp
        <tr data-doc="{{ $doc }}">
            <td>{{ optional($sancion->usuario)->name ?? $sancion->usuario_id }}</td>
            <td>{{ $sancion->descripcion }}</td>
            <td>{{ $sancion->tipo }}</td>
            <td>{{ \Illuminate\Support\Carbon::parse($sancion->fecha)->format('Y/m/d') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="d-flex justify-content-between align-items-center">
    <p class="mb-0"><strong>Total de Sanciones: </strong>{{ method_exists($reporte, 'total') ? $reporte->total() : $reporte->count() }}</p>
    <div>
        @if(method_exists($reporte, 'links'))
            {{ $reporte->links() }}
        @endif
    </div>
</div>
 
<script>
    (function(){
        // No hay buscador por estudiante en esta vista; el filtrado se realiza en el servidor mediante GET
    })();
</script>
@endsection