@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h2 class="mb-0">Editar Matrícula</h2>
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
            <form action="{{ route('matriculas.update', $matricula->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label class="form-label"><strong>Estudiante:</strong></label>
                    <select name="user_id" class="form-control">
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}" @if($student->id == $matricula->user_id) selected @endif>{{ $student->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label"><strong>Fecha de Matrícula:</strong></label>
                    <input type="date" name="fecha_matricula" value="{{ $matricula->fecha_matricula }}" class="form-control" placeholder="Fecha de Matrícula">
                </div>
                <div class="mb-3">
                    <label class="form-label"><strong>Estado:</strong></label>
                    <select name="estado" class="form-control">
                        <option value="activo" @if($matricula->estado == 'activo') selected @endif>Activo</option>
                        <option value="inactivo" @if($matricula->estado == 'inactivo') selected @endif>Inactivo</option>
                        <option value="completado" @if($matricula->estado == 'completado') selected @endif>Completado</option>
                        <option value="falta de documentacion" @if($matricula->estado == 'falta de documentacion') selected @endif>Falta de Documentación</option>
                    </select>
                </div>

                {{-- Documento de Identidad --}}
                <div class="mb-3">
                    <label class="form-label"><strong>Documento de Identidad:</strong></label>
                    @if(!empty($matricula->documento_identidad))
                        <div class="mb-2">
                            <a href="{{ $matricula->documento_identidad_url }}" target="_blank" class="btn btn-link btn-sm">Ver documento</a>
                            <button type="submit" name="delete_documento_identidad" value="1" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar documento actual?')">Eliminar</button>
                        </div>
                    @endif
                    <input type="file" name="documento_identidad" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                </div>
                {{-- RH --}}
                <div class="mb-3">
                    <label class="form-label"><strong>RH:</strong></label>
                    @if(!empty($matricula->rh))
                        <div class="mb-2">
                            <a href="{{ $matricula->rh_url }}" target="_blank" class="btn btn-link btn-sm">Ver documento</a>
                            <button type="submit" name="delete_rh" value="1" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar documento actual?')">Eliminar</button>
                        </div>
                    @endif
                    <input type="file" name="rh" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                </div>
                {{-- Certificado Médico --}}
                <div class="mb-3">
                    <label class="form-label"><strong>Certificado Médico:</strong></label>
                    @if(!empty($matricula->certificado_medico))
                        <div class="mb-2">
                            <a href="{{ $matricula->certificado_medico_url }}" target="_blank" class="btn btn-link btn-sm">Ver documento</a>
                            <button type="submit" name="delete_certificado_medico" value="1" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar documento actual?')">Eliminar</button>
                        </div>
                    @endif
                    <input type="file" name="certificado_medico" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                </div>
                {{-- Tipo de Usuario --}}
                <div class="mb-3">
                    <label class="form-label"><strong>Tipo de Usuario:</strong></label>
                    <select name="tipo_usuario" class="form-control" id="tipo_usuario_select">
                        <option value="nuevo" @if(old('tipo_usuario', $matricula->tipo_usuario ?? '') == 'nuevo') selected @endif>Nuevo</option>
                        <option value="antiguo" @if(old('tipo_usuario', $matricula->tipo_usuario ?? '') == 'antiguo') selected @endif>Antiguo</option>
                    </select>
                </div>
                {{-- Certificado de Notas (solo si es antiguo) --}}
                <div class="mb-3" id="certificado_notas_group" style="display: none;">
                    <label class="form-label"><strong>Certificado de Notas del Año Anterior:</strong></label>
                    @if(!empty($matricula->certificado_notas))
                        <div class="mb-2">
                            <a href="{{ $matricula->certificado_notas_url }}" target="_blank" class="btn btn-link btn-sm">Ver documento</a>
                            <button type="submit" name="delete_certificado_notas" value="1" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar documento actual?')">Eliminar</button>
                        </div>
                    @endif
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
        function toggleNotas() {
            if (tipoUsuario.value === 'antiguo') {
                certificadoNotas.style.display = 'block';
            } else {
                certificadoNotas.style.display = 'none';
            }
        }
        tipoUsuario.addEventListener('change', toggleNotas);
        toggleNotas();
    });
</script>
@endpush
@endsection
