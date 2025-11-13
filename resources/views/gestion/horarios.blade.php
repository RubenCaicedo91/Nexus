@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="h4">Horarios</h1>
    <p class="text-muted">Página de ejemplo para gestionar horarios.</p>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Formulario para crear horario --}}
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Crear nuevo horario</h5>
            <form action="{{ route('horarios.guardar') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="curso" class="form-label">Curso</label>
                    <select name="curso_id" id="curso" class="form-select" required>
                        <option value="">Selecciona un curso</option>
                        @foreach($cursos as $c)
                            <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label for="materia_id" class="form-label">Materia</label>
                    <select name="materia_id" id="materia_id" class="form-select">
                        <option value="">(Selecciona un curso primero)</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="dia" class="form-label">Día</label>
                    <select name="dia" class="form-select" required>
                        <option value="">Selecciona un día</option>
                        <option>Lunes</option>
                        <option>Martes</option>
                        <option>Miércoles</option>
                        <option>Jueves</option>
                        <option>Viernes</option>
                    </select>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="hora_inicio" class="form-label">Hora inicio</label>
                        <input type="time" name="hora_inicio" id="hora_inicio" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="hora_fin" class="form-label">Hora fin</label>
                        <input type="time" name="hora_fin" id="hora_fin" class="form-control" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Guardar horario</button>
            </form>
        </div>
    </div>

    {{-- Tabla de horarios existentes --}}
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Horarios registrados</h5>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Curso</th>
                        <th>Materia</th>
                        <th>Día</th>
                        <th>Hora inicio</th>
                        <th>Hora fin</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($horarios as $horario)
                        <tr>
                            <td>{{ $horario->curso }}</td>
                            <td>{{ $horario->materia_nombre ?? '—' }}</td>
                            <td>{{ $horario->dia }}</td>
                            <td>{{ $horario->hora_inicio ?? ($horario->hora) }}</td>
                            <td>{{ $horario->hora_fin ?? '' }}</td>
                            <td>
                                <a href="{{ route('horarios.editar', $horario->id) }}" class="btn btn-sm btn-warning">✏️ Editar</a>

                                <form action="{{ route('horarios.eliminar', $horario->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Estás segura de eliminar este horario?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">🗑️ Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">No hay horarios registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Select2 CSS & JS (CDN) para mejorar dropdown con scroll -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<style>
/* Limitar la altura del dropdown de Select2 a aproximadamente 5 elementos y activar scroll */
.select2-container--default .select2-results__options { max-height: 10rem; overflow-y: auto; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function(){
    // Inicializar Select2 en el select de cursos
    if (window.jQuery && window.jQuery().select2) {
        $('#curso').select2({
            width: '100%',
            dropdownAutoWidth: true,
            placeholder: 'Selecciona un curso',
            allowClear: true,
            minimumResultsForSearch: 10 // mostrar buscador si hay muchos cursos
        });

        $('#materia_id').select2({
            width: '100%',
            placeholder: '(Selecciona una materia)'
        });
    }

    // Cargar materias vía AJAX al cambiar el curso
    $('#curso').on('change', function(){
        const cursoId = $(this).val();
        const $materia = $('#materia_id');
        $materia.empty().append(new Option('(Cargando...)', ''));
        if (!cursoId) {
            $materia.empty().append(new Option('(Selecciona un curso primero)', ''));
            return;
        }
        fetch('/gestion-academica/cursos/' + encodeURIComponent(cursoId) + '/materias-json')
            .then(r => r.json())
            .then(data => {
                $materia.empty().append(new Option('(Sin materia seleccionada)', ''));
                data.forEach(m => {
                    const opt = new Option(m.nombre, m.id);
                    $materia.append(opt);
                });
                $materia.trigger('change');
            }).catch(err => {
                console.error('Error cargando materias:', err);
                $materia.empty().append(new Option('(Error al cargar materias)', ''));
            });
    });
});
</script>
@endpush