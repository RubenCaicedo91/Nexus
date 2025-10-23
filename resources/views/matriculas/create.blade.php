@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h2 class="mb-0">Crear Nueva Matrícula</h2>
            <a class="btn btn-primary" href="{{ route('matriculas.index') }}">
                <i class="fas fa-arrow-left me-2"></i>Volver
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Whoops!</strong> Hubo algunos problemas con tu entrada.<br><br>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form action="{{ route('matriculas.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label"><strong>Estudiante:</strong></label>
                    <select name="user_id" class="form-control">
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}">{{ $student->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label"><strong>Fecha de Matrícula:</strong></label>
                    <input type="date" name="fecha_matricula" class="form-control" placeholder="Fecha de Matrícula">
                </div>
                <div class="mb-3">
                    <label class="form-label"><strong>Estado:</strong></label>
                    <select name="estado" class="form-control">
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                        <option value="completado">Completado</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label"><strong>Documento de Identidad:</strong></label>
                    <input type="file" name="documento_identidad" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                </div>
                <div class="mb-3">
                    <label class="form-label"><strong>RH:</strong></label>
                    <input type="file" name="rh" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                </div>
                <div class="mb-3">
                    <label class="form-label"><strong>Certificado Médico:</strong></label>
                    <input type="file" name="certificado_medico" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                </div>
                <div class="mb-3">
                    <label class="form-label"><strong>Tipo de Usuario:</strong></label>
                    <select name="tipo_usuario" class="form-control" id="tipo_usuario_select">
                        <option value="nuevo">Nuevo</option>
                        <option value="antiguo">Antiguo</option>
                    </select>
                </div>
                <div class="mb-3" id="certificado_notas_group" style="display: none;">
                    <label class="form-label"><strong>Certificado de Notas del Año Anterior:</strong></label>
                    <input type="file" name="certificado_notas" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                </div>
                <div class="text-center">
                    <button type="submit" class="btn btn-primary w-100">Enviar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tipoUsuario = document.getElementById('tipo_usuario_select');
        const certificadoNotas = document.getElementById('certificado_notas_group');
        tipoUsuario.addEventListener('change', function() {
            if (this.value === 'antiguo') {
                certificadoNotas.style.display = 'block';
            } else {
                certificadoNotas.style.display = 'none';
            }
        });
    });
</script>
@endpush
@endsection
