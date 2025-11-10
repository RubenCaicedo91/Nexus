@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="mb-4">📄 Circulares Institucionales</h2>

    <div class="card">
        <div class="card-header bg-info text-white">Listado de Circulares</div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Contenido</th>
                        <th>Fecha</th>
                        <th>Archivo</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($circulares as $circ)
                        <tr>
                            <td>{{ $circ->titulo }}</td>
                            <td>{{ Str::limit($circ->contenido, 80) }}</td>
                            <td>{{ $circ->fecha_publicacion }}</td>
                            <td>
                                @if($circ->archivo)
                                    <a href="{{ asset('storage/'.$circ->archivo) }}" target="_blank" class="btn btn-sm btn-outline-info">📥 Descargar</a>
                                @else
                                    Sin archivo
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center">No hay circulares</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
