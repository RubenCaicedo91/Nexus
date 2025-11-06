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
                                        <option value="{{ $m->id }}">{{ $m->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>Periodo</label>
                                <input type="text" name="periodo" id="periodo" class="form-control" placeholder="Ej: 2025-10" />
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-primary w-100" id="btnLoad">Cargar Estudiantes</button>
                            </div>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('notas.store') }}">
                        @csrf
                        <input type="hidden" name="materia_id" id="materia_id_hidden" />
                        <input type="hidden" name="periodo" id="periodo_hidden" />

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
                                                <td>{{ $mat->user->email }}</td>
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
        const periodo = document.getElementById('periodo');
        const curso = document.querySelector('select[name="curso_id"]');

        if (!curso || !curso.value) {
            alert('Seleccione primero un curso.');
            return;
        }

        if (!materia || !materia.value) {
            alert('Seleccione una materia.');
            return;
        }

        if (!periodo || !periodo.value) {
            alert('Ingrese el periodo.');
            return;
        }

        // llenar campos ocultos y enviar el formulario de post
        document.getElementById('materia_id_hidden').value = materia.value;
        document.getElementById('periodo_hidden').value = periodo.value;

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
