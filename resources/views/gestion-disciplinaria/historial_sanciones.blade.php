{{-- historial_sanciones.blade.php --}}
@extends('layouts.app')
@section('content')
<h2>Historial de sanciones</h2>

<!-- Buscador por número de identificación -->
<div class="row mb-4">
    <div class="col-md-6">
        <label for="document_search" class="form-label">Número de identificación</label>
        <div class="input-group">
            <input id="document_search" class="form-control" placeholder="Escribe el número de identificación...">
            <button id="btn_search_document" class="btn btn-primary">Buscar</button>
        </div>
        <div id="search_error" class="text-danger mt-2" style="display:none;"></div>
    </div>
</div>

<!-- Contenedor donde se mostrará la información del estudiante y sanciones -->
<div id="resultado_busqueda">
    @if(isset($estudiante) && isset($sanciones))
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="card-title">{{ $estudiante->name }} <small class="text-muted">(ID: {{ $estudiante->id }})</small></h5>
                <p class="card-text mb-1"><strong>Documento:</strong> {{ $estudiante->document_number ?? 'No registrado' }}</p>
                @if(isset($matricula) && $matricula)
                    <p class="card-text mb-0"><strong>Curso:</strong> {{ optional($matricula->curso)->nombre ?? 'Sin curso asignado' }}</p>
                    <p class="card-text"><small class="text-muted">Matrícula: {{ \Illuminate\Support\Carbon::parse($matricula->fecha_matricula)->format('Y/m/d') }}</small></p>
                @endif
            </div>
        </div>

        @if($sanciones->isEmpty())
            <div class="alert alert-info">El estudiante no tiene sanciones registradas.</div>
        @else
            <table class="table">
                <thead>
                    <tr>
                        <th>Descripción</th>
                        <th>Tipo</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sanciones as $sancion)
                        <tr>
                            <td>{{ $sancion->descripcion }}</td>
                            <td>{{ $sancion->tipo }}</td>
                            <td>{{ \Illuminate\Support\Carbon::parse($sancion->fecha)->format('Y/m/d') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endif
</div>

<script>
    (function(){
        const buscarUrl = '{{ route("gestion-disciplinaria.buscar") }}';
        const btn = document.getElementById('btn_search_document');
        const input = document.getElementById('document_search');
        const resultado = document.getElementById('resultado_busqueda');
        const errorDiv = document.getElementById('search_error');

        function formatDateISO(dateStr) {
            if (!dateStr) return '';
            // Esperamos formatos ISO como 2025-11-16T00:00:00.000000Z o YYYY-MM-DD
            try {
                const dateOnly = (dateStr.split('T')[0] || dateStr).split('.')[0];
                const parts = dateOnly.split('-');
                if (parts.length === 3) return `${parts[0]}/${parts[1]}/${parts[2]}`;
                return dateStr;
            } catch (e) {
                return dateStr;
            }
        }

        function renderStudentCard(user, matricula) {
            const curso = matricula && matricula.curso ? matricula.curso.nombre : (matricula ? (matricula.curso_nombre ?? '') : '');
            return `
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">${user.name} <small class="text-muted">(ID: ${user.id})</small></h5>
                        <p class="card-text mb-1"><strong>Documento:</strong> ${user.document_number || 'No registrado'}</p>
                        ${matricula ? `<p class="card-text mb-0"><strong>Curso:</strong> ${curso || 'Sin curso asignado'}</p>
                        <p class="card-text"><small class="text-muted">Matrícula: ${formatDateISO(matricula.fecha_matricula) || ''}</small></p>` : ''}
                    </div>
                </div>
            `;
        }

        function renderSancionesTable(sanciones) {
            if (!sanciones || sanciones.length === 0) {
                return `<div class="alert alert-info">El estudiante no tiene sanciones registradas.</div>`;
            }
            let rows = sanciones.map(s => `<tr><td>${s.descripcion || ''}</td><td>${s.tipo || ''}</td><td>${formatDateISO(s.fecha) || ''}</td></tr>`).join('');
            return `
                <table class="table">
                    <thead>
                        <tr><th>Descripción</th><th>Tipo</th><th>Fecha</th></tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            `;
        }

        async function buscarDocumento() {
            const document = (input.value || '').trim();
            errorDiv.style.display = 'none';
            if (!document) {
                errorDiv.textContent = 'Ingresa un número de identificación.';
                errorDiv.style.display = 'block';
                return;
            }

            resultado.innerHTML = '<div class="text-muted">Buscando...</div>';

            try {
                const url = buscarUrl + '?document=' + encodeURIComponent(document);
                const res = await fetch(url, { credentials: 'same-origin' });
                if (!res.ok) {
                    const err = await res.json().catch(() => ({ message: 'Error en la búsqueda' }));
                    resultado.innerHTML = `<div class="alert alert-warning">${err.message || 'Estudiante no encontrado'}</div>`;
                    return;
                }
                const data = await res.json();
                if (!data.success) {
                    resultado.innerHTML = `<div class="alert alert-warning">${data.message || 'No se encontraron resultados'}</div>`;
                    return;
                }

                // Renderizar datos
                const studentHtml = renderStudentCard(data.user, data.matricula);
                const sancionesHtml = renderSancionesTable(data.sanciones);
                resultado.innerHTML = studentHtml + sancionesHtml;
            } catch (e) {
                resultado.innerHTML = `<div class="alert alert-danger">Error al realizar la búsqueda.</div>`;
                console.error(e);
            }
        }

        btn.addEventListener('click', function(e){ e.preventDefault(); buscarDocumento(); });
        input.addEventListener('keydown', function(e){ if (e.key === 'Enter') { e.preventDefault(); buscarDocumento(); } });
    })();
</script>

@endsection