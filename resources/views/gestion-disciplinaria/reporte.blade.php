{{-- index.blade.php --}}
@extends('layouts.app')
@section('content')
<h2>Reporte Disciplinario</h2>
<table class="table">
    <thead>
        <tr>
            <th>ID Usuario</th>
            <th>Descripción Sanción</th>
            <th>Tipo Sanción</th>
            <th>Fecha Registro</th>
        </tr>
    </thead>
    <tbody>
        @php $total = 0; @endphp
        @foreach($reporte as $sancion)
        <tr>
            <td>{{ $sancion->usuario_id }}</td>
            <td>{{ $sancion->descripcion }}</td>
            <td>{{ $sancion->tipo }}</td>
            <td>{{ \Carbon\Carbon::parse($sancion->fecha)->format('d/m/Y') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<p><strong>Total de Sanciones: </strong>{{ count($reporte) }}</p>
@endsection