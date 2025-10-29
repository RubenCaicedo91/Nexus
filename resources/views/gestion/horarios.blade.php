@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="h4">Horarios</h1>
    <p class="text-muted">Página de ejemplo para gestionar horarios.</p>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- La funcionalidad de asignar docentes no pertenece al módulo Horarios; se gestiona desde el panel de Gestión Académica. --}}

    {{-- Formulario para crear horario --}}
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Crear nuevo horario</h5>
            <form action="{{ route('horarios.guardar') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="curso" class="form-label">Curso</label>
                    <input type="text" name="curso" class="form-control" required>
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
                <div class="mb-3">
                    <label for="hora" class="form-label">Hora</label>
                    <input type="time" name="hora" class="form-control" required>
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
                        <th>Día</th>
                        <th>Hora</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($horarios as $horario)
                        <tr>
                            <td>{{ $horario->curso }}</td>
                            <td>{{ $horario->dia }}</td>
                            <td>{{ $horario->hora }}</td>
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
                            <td colspan="4" class="text-center text-muted">No hay horarios registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('btnIrAsignar')?.addEventListener('click', function(){
    const sel = document.getElementById('selectCursoParaAsignar');
    const id = sel?.value;
    if (!id) {
        alert('Selecciona un curso para continuar');
        return;
    }
    // Redirigimos a la ruta de materias para el curso seleccionado
    window.location.href = '/gestion-academica/cursos/' + encodeURIComponent(id) + '/materias';
});
</script>
@endpush