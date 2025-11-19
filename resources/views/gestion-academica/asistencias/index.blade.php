@extends('layouts.app')

@section('title', 'Asistencias')

@section('content')
    @php
        $isEstudiante = isset($isEstudiante) ? $isEstudiante : (auth()->check() && optional(auth()->user()->role)->nombre && stripos(optional(auth()->user()->role)->nombre, 'estudiante') !== false);
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Asistencias</h3>
        <div>
            @if(! $isEstudiante)
                <a href="{{ route('asistencias.create') }}" class="btn btn-primary">Registrar asistencia</a>
                <a href="{{ route('asistencias.index') }}" class="btn btn-secondary">Actualizar</a>
            @endif
        </div>
    </div>

    <form method="GET" class="row g-2 align-items-end mb-3">
        <div class="col-md-3">
            <label>Fecha</label>
            <input type="date" name="fecha" value="{{ $fecha ?? '' }}" class="form-control">
        </div>
        <div class="col-md-3">
            <label>Curso</label>
            <select name="curso_id" id="filter-curso" class="form-control">
                <option value="">-- Todos --</option>
                @foreach($cursos as $c)
                    <option value="{{ $c->id }}" {{ (isset($cursoId) && $cursoId == $c->id) ? 'selected' : '' }}>{{ $c->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label>Materia</label>
            <select name="materia_id" id="filter-materia" class="form-control">
                <option value="">-- Todas --</option>
                @foreach($materias as $m)
                    <option value="{{ $m->id }}" {{ (isset($materiaId) && $materiaId == $m->id) ? 'selected' : '' }}>{{ $m->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <button class="btn btn-primary">Filtrar</button>
            @if(! $isEstudiante)
                <a href="{{ route('asistencias.export', request()->query()) }}" class="btn btn-outline-success">Exportar</a>
            @endif
        </div>
    </form>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if(isset($stats) && ($cursoId))
        <div class="mb-3">
            <div class="row">
                <div class="col-md-3"><div class="p-2 border rounded bg-light">Total estudiantes: <strong>{{ $stats['total'] }}</strong></div></div>
                <div class="col-md-3"><div class="p-2 border rounded bg-success text-white">Asistieron: <strong>{{ $stats['present'] }}</strong></div></div>
                <div class="col-md-3"><div class="p-2 border rounded bg-warning">Faltaron: <strong>{{ $stats['absent'] }}</strong></div></div>
                <div class="col-md-3"><div class="p-2 border rounded bg-info text-white">Con excusa: <strong>{{ $stats['excuse'] }}</strong></div></div>
            </div>
        </div>
    @endif

    <table class="table table-striped">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Curso</th>
                <th>Materia</th>
                @if($isEstudiante)
                    <th>Tu estado</th>
                @else
                    <th></th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($asistencias as $a)
                <tr>
                    <td>{{ $a->fecha->format('Y-m-d') }}</td>
                    <td>{{ optional($a->curso)->nombre }}</td>
                    <td>{{ optional($a->materia)->nombre }}</td>
                    <td>
                        @if($isEstudiante)
                            @php
                                // Usar el valor casteado de Eloquent para determinar presencia
                                // (esto respeta el cast 'boolean' en el modelo)
                                $presentBool = isset($a->presente) ? (bool)$a->presente : false;
                                $obsRaw = isset($a->observacion) ? trim((string)$a->observacion) : '';

                                // Priorizar la observación (Excusa) si existe, luego presencia
                                // 1) Si hay observación => 'Excusa'
                                // 2) Si presente => 'Asistio'
                                // 3) En otro caso => 'No asistio'
                                if ($obsRaw !== '') {
                                    $statusText = 'Excusa';
                                    $obsText = '';
                                } elseif ($presentBool) {
                                    $statusText = 'Asistio';
                                    $obsText = '';
                                } else {
                                    $statusText = 'No asistio';
                                    $obsText = '';
                                }
                            @endphp
                            <span title="presente cast={{ $a->presente }}; presente raw={{ method_exists($a, 'getOriginal') ? $a->getOriginal('presente') : $a->presente }}; observacion={{ e($a->observacion) }}">{{ $statusText }}@if(!empty($obsText)) : {{ $obsText }}@endif</span>
                        @else
                            @php $rs = $rowStats[$a->id] ?? null; @endphp
                            @if($rs)
                                <div style="font-size:0.9rem">
                                    <span class="badge bg-secondary text-white" title="Total de estudiantes matriculados" aria-label="Total estudiantes: {{ $rs['total'] }}">Total: <strong>{{ $rs['total'] }}</strong></span>
                                    <span class="badge bg-success text-white" title="Asistieron (Presente)" aria-label="Asistieron: {{ $rs['present'] }}">Asistieron: <strong>{{ $rs['present'] }}</strong></span>
                                    <span class="badge bg-warning" title="Faltaron (No asistieron)" aria-label="Faltaron: {{ $rs['absent'] }}">Faltaron: <strong>{{ $rs['absent'] }}</strong></span>
                                    <span class="badge bg-info text-white" title="Con excusa" aria-label="Con excusa: {{ $rs['excuse'] }}">Con excusa: <strong>{{ $rs['excuse'] }}</strong></span>
                                </div>
                            @endif
                        @endif
                    </td>
                    <td class="text-end">
                        @if(! $isEstudiante)
                            <a href="{{ route('asistencias.curso.registro', ['cursoId' => $a->curso_id, 'fecha' => $a->fecha->format('Y-m-d')]) }}" class="btn btn-sm btn-outline-info">Editar curso</a>
                            <a href="{{ route('asistencias.export_single', $a->id) }}" class="btn btn-sm btn-outline-success" target="_blank">Exportar</a>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $asistencias->links() }}
@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    const cursoSelect = document.getElementById('filter-curso');
    const materiaSelect = document.getElementById('filter-materia');
    if(!cursoSelect) return;
    cursoSelect.addEventListener('change', function(){
        const cursoId = this.value;
        materiaSelect.innerHTML = '<option value="">Cargando...</option>';
        if(!cursoId){
            materiaSelect.innerHTML = '<option value="">-- Todas --</option>';
            return;
        }
        fetch(`/gestion-academica/cursos/${cursoId}/materias-json`).then(r => r.json()).then(data => {
            materiaSelect.innerHTML = '<option value="">-- Todas --</option>';
            (data || []).forEach(m => {
                const opt = document.createElement('option');
                opt.value = m.id; opt.textContent = m.nombre;
                materiaSelect.appendChild(opt);
            });
        }).catch(err => {
            materiaSelect.innerHTML = '<option value="">-- Todas --</option>';
        });
    });
});
</script>
@endsection
@endsection
