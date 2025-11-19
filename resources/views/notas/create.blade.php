@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Registrar Notas</h4>
                </div>
                <div class="card-body">
                                @php
                                    $roleNameCreate = optional(Auth::user()->role)->nombre ?? '';
                                    $isRectorCreate = stripos($roleNameCreate, 'rector') !== false;
                                @endphp
                    @php
                        $hideSearch = request()->filled('matricula_id') || (request()->filled('curso_id') && request()->filled('materia_id'));
                    @endphp

                                @unless($hideSearch)
                                @if($isRectorCreate)
                                    <div class="alert alert-warning">No tienes permiso para crear notas.</div>
                                @else
                                <form method="GET" action="{{ route('notas.create') }}" class="mb-3">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-4">
                                <label>Curso</label>
                                <select name="curso_id" class="form-select" onchange="this.form.submit()">
                                    <option value="">-- Seleccionar curso --</option>
                                    @foreach($cursos as $curso)
                                        <option value="{{ $curso->id }}" {{ request('curso_id') == $curso->id ? 'selected' : '' }}>{{ $curso->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label>Materia</label>
                                <select name="materia_id" id="materia_id" class="form-select">
                                    <option value="">-- Seleccionar materia --</option>
                                    @foreach($materias as $m)
                                        <option value="{{ $m->id }}" {{ (isset($selectedMateriaId) && $selectedMateriaId == $m->id) || request('materia_id') == $m->id ? 'selected' : '' }}>{{ $m->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <button type="button" class="btn btn-primary w-100" id="btnLoad">Cargar Estudiantes</button>
                            </div>
                        </div>
                    </form>
                    @endif
                    @endunless

                    @if(request()->filled('matricula_id') && $matriculas->count())
                        @php $m = $matriculas->first(); @endphp
                        <div class="mb-3 p-3 border rounded bg-white">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Estudiante:</strong>
                                    <div>{{ $m->user->name }} <br><small class="text-muted">{{ $m->user->email }}</small></div>
                                </div>
                                <div class="col-md-2">
                                    <strong>Curso</strong>
                                    <div>{{ $m->curso->nombre ?? '--' }}</div>
                                </div>
                                <div class="col-md-2">
                                    <strong>Materia</strong>
                                    @php $selMat = $materias->firstWhere('id', $selectedMateriaId); @endphp
                                    <div>{{ $selMat->nombre ?? ($materias->first()->nombre ?? '--') }}</div>
                                </div>
                                
                            </div>
                        </div>
                    @endif

                    @if(!request()->filled('matricula_id') && request()->filled('curso_id') && request()->filled('materia_id') && $matriculas->count())
                        @php
                            $selectedCurso = $cursos->firstWhere('id', request('curso_id'));
                            $selMat = $materias->firstWhere('id', request('materia_id'));
                        @endphp
                        <div class="mb-3 p-3 border rounded bg-white">
                            <div class="row">
                                <div class="col-md-4">
                                    <strong>Curso:</strong>
                                    <div>{{ $selectedCurso->nombre ?? '--' }}</div>
                                </div>
                                <div class="col-md-4">
                                    <strong>Materia:</strong>
                                    <div>{{ $selMat->nombre ?? '--' }}</div>
                                </div>
                                
                            </div>
                        </div>
                    @endif

                    @if($isRectorCreate)
                        <div class="alert alert-warning">No tienes permiso para crear notas.</div>
                    @else
                    <form method="POST" action="{{ route('notas.store') }}">
                        @csrf
                        <input type="hidden" name="back" value="{{ request('back') ?? '' }}" />
                        <input type="hidden" name="materia_id" id="materia_id_hidden" value="{{ old('materia_id') ?? (request('materia_id') ?? ($selectedMateriaId ?? '')) }}" />

                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label class="form-label">Año</label>
                                <input type="text" name="anio" class="form-control" value="{{ old('anio') ?? request('anio') ?? (isset($selectedAnio) ? $selectedAnio : date('Y')) }}">
                            </div>
                        </div>

                        <div id="studentsTable">
                            @if($matriculas && $matriculas->count())
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Estudiante</th>
                                            <th>Documento</th>
                                            <th style="width:120px">Nota</th>
                                            <th>Observaciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($matriculas as $mat)
                                            <tr>
                                                <td>{{ $mat->user->name }}</td>
                                                <td>
                                                    {{-- Mostrar el número de identificación guardado en la base de datos --}}
                                                    @if(!empty(optional($mat->user)->document_number))
                                                        {{ $mat->user->document_number }}
                                                    @else
                                                        --
                                                    @endif
                                                </td>
                                                <td>
                                                    <input type="hidden" name="notas[{{ $loop->index }}][matricula_id]" value="{{ $mat->id }}">
                                                    <input type="number" step="0.01" name="notas[{{ $loop->index }}][valor]" class="form-control" min="0" max="100">
                                                </td>
                                                <td>
                                                    <input type="text" name="notas[{{ $loop->index }}][observaciones]" class="form-control">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>

                                <div class="d-flex justify-content-end">
                                    <button class="btn btn-success" type="submit">Guardar Notas</button>
                                </div>
                            @else
                                <div class="alert alert-info">Seleccione un curso y cargue estudiantes para registrar notas.</div>
                            @endif
                        </div>
                    </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnLoad = document.getElementById('btnLoad');
    btnLoad && btnLoad.addEventListener('click', function() {
        const materia = document.getElementById('materia_id');
        const curso = document.querySelector('select[name="curso_id"]');

        if (!curso || !curso.value) {
            alert('Seleccione primero un curso.');
            return;
        }

        if (!materia || !materia.value) {
            alert('Seleccione una materia.');
            return;
        }

        // llenar campos ocultos
        document.getElementById('materia_id_hidden').value = materia.value;

        // Scroll hacia el formulario de notas
        const actionUrl = "{{ route('notas.store') }}";
        const form = document.querySelector('form[action]');
        // Asegurar que el formulario sea el de guardado comparando action
        if (form && form.getAttribute('action') === actionUrl) {
            form.scrollIntoView({behavior: 'smooth'});
        }
    });
});
</script>
@endsection
