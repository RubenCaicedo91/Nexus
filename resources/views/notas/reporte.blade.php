@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4>Reporte de Notas por Materia</h4>
        </div>
        <div class="card-body">
            @if($stats->isEmpty())
                <div class="alert alert-info">No hay datos para el periodo seleccionado.</div>
            @else
                <table class="table">
                    <thead>
                        <tr>
                            <th>Materia</th>
                            <th>Promedio</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stats as $s)
                            <tr>
                                <td>{{ $s->materia }}</td>
                                <td>{{ number_format($s->promedio, 2) }}</td>
                                <td>{{ $s->total }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
@endsection
