{{-- index.blade.php --}}
@extends('layouts.app')
@section('content')
<h2>Reporte Disciplinario</h2>
<!-- Buscador de estudiantes (muestra sugerencias y filtra la tabla en tiempo real) -->
<div class="mb-3">
    <label for="buscador_estudiante" class="form-label">Buscar estudiante</label>
    <input id="buscador_estudiante" list="lista_estudiantes" class="form-control" placeholder="Escribe un nombre...">
    <datalist id="lista_estudiantes">
        @php
            // Crear lista única de nombres desde el reporte
            $nombres = [];
        @endphp
        @foreach($reporte as $sancion)
            @php $nombres[optional($sancion->usuario)->name ?? 'ID_'.$sancion->usuario_id] = optional($sancion->usuario)->name ?? 'ID_'.$sancion->usuario_id; @endphp
        @endforeach
        @foreach($nombres as $nombre)
            <option value="{{ $nombre }}"></option>
        @endforeach
    </datalist>
</div>
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
        @php $total = 0; @endphp
        @foreach($reporte as $sancion)
        <tr>
            <td>{{ optional($sancion->usuario)->name ?? $sancion->usuario_id }}</td>
            <td>{{ $sancion->descripcion }}</td>
            <td>{{ $sancion->tipo }}</td>
            <td>{{ \Carbon\Carbon::parse($sancion->fecha)->format('d/m/Y') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<p><strong>Total de Sanciones: </strong>{{ count($reporte) }}</p>
 
<script>
    (function(){
        const input = document.getElementById('buscador_estudiante');
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
                const estudianteCell = r.querySelector('td'); // primera columna es estudiante
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
@endsection