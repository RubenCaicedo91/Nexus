{{-- historial_sanciones.blade.php --}}
@extends('layouts.app')
@section('content')
<h2>Listado de Sanciones</h2>
@if(count($sanciones))
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Descripción</th>
                <th>Tipo</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sanciones as $sancion)
            <tr>
                <td>{{ $sancion->id }}</td>
                <td>{{ $sancion->descripcion }}</td>
                <td>{{ $sancion->tipo }}</td>
                <td>{{ $sancion->fecha }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
@else
    <p>No hay sanciones registradas para este usuario.</p>
@endif
@endsection