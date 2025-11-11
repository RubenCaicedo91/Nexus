{{-- historial_sanciones.blade.php --}}
@extends('layouts.app')
@section('content')
<h2>Listado de Sanciones</h2>
<!-- Buscador para filtrar sanciones por nombre de estudiante -->
<div class="mb-3">
    <label for="buscador_historial" class="form-label">Filtrar por estudiante</label>
    <input id="buscador_historial" class="form-control" placeholder="Escribe un nombre para filtrar...">
</div>
@if(count($sanciones))
    <table class="table">
        <thead>
            <tr>
                <th>Estudiante</th>
                <th>Descripción</th>
                <th>Tipo</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sanciones as $sancion)
            <tr>
                <td>{{ optional($sancion->usuario)->name ?? $sancion->usuario_id }}</td>
                <td>{{ $sancion->descripcion }}</td>
                <td>{{ $sancion->tipo }}</td>
                <td>{{ $sancion->fecha }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

<script>
    (function(){
        const input = document.getElementById('buscador_historial');
        const table = document.querySelector('table');
        if (!input || !table) return;

        input.addEventListener('input', function(e) {
            const q = (e.target.value || '').toLowerCase().trim();
            const rows = table.querySelectorAll('tbody tr');
            if (q === '') {
                rows.forEach(r => r.style.display = '');
                return;
            }

            rows.forEach(r => {
                const estudianteCell = r.querySelector('td');
                const text = estudianteCell ? estudianteCell.textContent.toLowerCase() : '';
                if (text.indexOf(q) !== -1) {
                    r.style.display = '';
                } else {
                    r.style.display = 'none';
                }
            });
        });
    })();
</script>
@else
    <p>No hay sanciones registradas para este usuario.</p>
@endif
@endsection