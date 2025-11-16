@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-edit me-2"></i>Editar Asignación de Estudiante
                    </h4>
                    <div>
                        <a href="{{ route('asignaciones.show', $asignacion) }}" class="btn btn-info me-2">
                            <i class="fas fa-eye me-2"></i>Ver Detalles
                        </a>
                        <a href="{{ route('asignaciones.index') }}" class="btn btn-dark">
                            <i class="fas fa-arrow-left me-2"></i>Volver al Listado
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Editando asignación #{{ $asignacion->id }}</strong> - 
                        Puede actualizar documentos individualmente o modificar la información básica.
                    </div>

                    <form action="{{ route('asignaciones.update', $asignacion) }}" method="POST" enctype="multipart/form-data" data-existing-certificado="{{ !empty($asignacion->certificado_notas) ? '1' : '0' }}">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            {{-- Información básica --}}
                            <div class="col-md-6">
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0">
                                            <i class="fas fa-user me-2"></i>Información Básica
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="user_id" class="form-label">Estudiante <span class="text-danger">*</span></label>
                                            <select name="user_id" id="user_id" class="form-select @error('user_id') is-invalid @enderror" required>
                                                <option value="">-- Seleccionar estudiante --</option>
                                                @foreach($estudiantes as $estudiante)
                                                    <option value="{{ $estudiante->id }}" {{ (old('user_id', $asignacion->user_id) == $estudiante->id) ? 'selected' : '' }}>
                                                        {{ $estudiante->name }} - {{ $estudiante->email }} (ID: {{ $estudiante->id }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('user_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="curso_id" class="form-label">Curso <span class="text-danger">*</span></label>
                                            <select name="curso_id" id="curso_id" class="form-select @error('curso_id') is-invalid @enderror" required>
                                                <option value="">-- Seleccionar curso --</option>
                                                @foreach($cursos as $curso)
                                                    <option value="{{ $curso->id }}" {{ (old('curso_id', $asignacion->curso_id) == $curso->id) ? 'selected' : '' }}>
                                                        {{ $curso->nombre }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('curso_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="fecha_matricula" class="form-label">Fecha de Matrícula <span class="text-danger">*</span></label>
                                            <input type="date" name="fecha_matricula" id="fecha_matricula" 
                                                   class="form-control @error('fecha_matricula') is-invalid @enderror" 
                                                   value="{{ old('fecha_matricula', $asignacion->fecha_matricula) }}" required>
                                            @error('fecha_matricula')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="tipo_usuario" class="form-label">Tipo de Usuario <span class="text-danger">*</span></label>
                                            <select name="tipo_usuario" id="tipo_usuario" class="form-select @error('tipo_usuario') is-invalid @enderror" required>
                                                <option value="nuevo" {{ old('tipo_usuario', $asignacion->tipo_usuario ?? 'nuevo') == 'nuevo' ? 'selected' : '' }}>Nuevo</option>
                                                <option value="antiguo" {{ old('tipo_usuario', $asignacion->tipo_usuario) == 'antiguo' ? 'selected' : '' }}>Antiguo</option>
                                            </select>
                                            <div class="form-text">Si el estudiante es nuevo, el certificado de notas no es obligatorio.</div>
                                            @error('tipo_usuario')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="estado" class="form-label">Estado <span class="text-danger">*</span></label>
                                            <select name="estado" id="estado" class="form-select @error('estado') is-invalid @enderror" required>
                                                <option value="activo" {{ old('estado', $asignacion->estado) == 'activo' ? 'selected' : '' }}>Activo</option>
                                                <option value="inactivo" {{ old('estado', $asignacion->estado) == 'inactivo' ? 'selected' : '' }}>Inactivo</option>
                                                <option value="suspendido" {{ old('estado', $asignacion->estado) == 'suspendido' ? 'selected' : '' }}>Suspendido</option>
                                            </select>
                                            @error('estado')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Información de pago --}}
                            <div class="col-md-6">
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0">
                                            <i class="fas fa-money-bill me-2"></i>Información de Pago
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="monto_pago" class="form-label">Monto del Pago</label>
                                            <div class="input-group">
                                                <span class="input-group-text">$</span>
                                                <input type="number" name="monto_pago" id="monto_pago" 
                                                       class="form-control @error('monto_pago') is-invalid @enderror" 
                                                       step="0.01" min="0" value="{{ old('monto_pago', $asignacion->monto_pago) }}">
                                            </div>
                                            @error('monto_pago')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="fecha_pago" class="form-label">Fecha de Pago</label>
                                            <input type="date" name="fecha_pago" id="fecha_pago" 
                                                   class="form-control @error('fecha_pago') is-invalid @enderror" 
                                                   value="{{ old('fecha_pago', $asignacion->fecha_pago) }}">
                                            @error('fecha_pago')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="comprobante_pago" class="form-label">Nuevo Comprobante de Pago</label>
                                            @if($asignacion->comprobante_pago)
                                                <div class="mb-2">
                                                    <small class="text-success">
                                                        <i class="fas fa-check-circle me-1"></i>Archivo actual: 
                                                        <a href="{{ route('matriculas.archivo', ['matricula' => $asignacion->id, 'campo' => 'comprobante_pago']) }}" 
                                                           target="_blank" class="text-primary">Ver comprobante actual</a>
                                                    </small>
                                                </div>
                                            @endif
                                            <input type="file" name="comprobante_pago" id="comprobante_pago" 
                                                   class="form-control @error('comprobante_pago') is-invalid @enderror" 
                                                   accept=".pdf,.jpg,.jpeg,.png">
                                            <div class="form-text">Solo cargar si desea reemplazar el archivo actual. Formatos: PDF, JPG, PNG. Máximo 2MB.</div>
                                            @error('comprobante_pago')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Documentos actuales y actualizaciones --}}
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">
                                    <i class="fas fa-file-alt me-2"></i>Documentos 
                                    @if($asignacion->tieneDocumentosCompletos())
                                        <span class="badge bg-success ms-2">Completos</span>
                                    @else
                                        <span class="badge bg-danger ms-2">Incompletos</span>
                                    @endif
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    @php
                                        $documentos = [
                                            'documento_identidad' => 'Documento de Identidad',
                                            'rh' => 'Certificado de RH',
                                            'certificado_medico' => 'Certificado Médico',
                                            'certificado_notas' => 'Certificado de Notas'
                                        ];
                                    @endphp

                                    @foreach($documentos as $campo => $nombre)
                                        <div class="col-md-6 mb-4">
                                            <div class="card border-left-primary">
                                                <div class="card-body">
                                                    <h6 class="card-title">{{ $nombre }}</h6>
                                                    
                                                    @if($asignacion->$campo)
                                                        <div class="mb-2">
                                                            <small class="text-success">
                                                                <i class="fas fa-check-circle me-1"></i>Archivo actual: 
                                                                <a href="{{ route('matriculas.archivo', ['matricula' => $asignacion->id, 'campo' => $campo]) }}" 
                                                                   target="_blank" class="text-primary">Ver documento</a>
                                                            </small>
                                                        </div>
                                                    @else
                                                        <div class="mb-2">
                                                            <small class="text-danger">
                                                                <i class="fas fa-times-circle me-1"></i>No cargado
                                                            </small>
                                                        </div>
                                                    @endif
                                                    
                                                    <input type="file" name="{{ $campo }}" id="{{ $campo }}" 
                                                           class="form-control @error($campo) is-invalid @enderror" 
                                                           accept=".pdf,.jpg,.jpeg,.png">
                                                    <div class="form-text">
                                                        {{ $asignacion->$campo ? 'Solo cargar si desea reemplazar el archivo actual.' : 'Requerido para completar la asignación.' }}
                                                    </div>
                                                    @error($campo)
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                @if(!$asignacion->tieneDocumentosCompletos())
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <strong>Nota:</strong> La asignación no estará completa hasta que todos los documentos estén cargados.
                                        Los documentos faltantes deben ser cargados para que el estado se actualice automáticamente.
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Botones de acción --}}
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('asignaciones.show', $asignacion) }}" class="btn btn-secondary">
                                        <i class="fas fa-times me-2"></i>Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-warning" id="submitBtn">
                                        <i class="fas fa-save me-2"></i>Actualizar Asignación
                                    </button>
                                </div>
                            </div>
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
    const form = document.querySelector('form');
    const submitBtn = document.getElementById('submitBtn');
    const tipoUsuarioSelect = document.getElementById('tipo_usuario');
    const certificadoNotasInput = document.getElementById('certificado_notas');
    const existingCertificadoNotas = form && form.dataset && form.dataset.existingCertificado === '1';

    function toggleCertificadoNotasRequirement() {
        if (!tipoUsuarioSelect || !certificadoNotasInput) return;
        if (tipoUsuarioSelect.value === 'antiguo' && !existingCertificadoNotas) {
            certificadoNotasInput.required = true;
        } else {
            certificadoNotasInput.required = false;
        }
    }

    // Inicializar y escuchar cambios
    toggleCertificadoNotasRequirement();
    if (tipoUsuarioSelect) {
        tipoUsuarioSelect.addEventListener('change', toggleCertificadoNotasRequirement);
    }
    
    // Validar archivos antes del envío
    form.addEventListener('submit', function(e) {
        const fileInputs = form.querySelectorAll('input[type="file"]');
        let allFilesValid = true;
        
        fileInputs.forEach(input => {
            if (input.files.length > 0) {
                const file = input.files[0];
                const maxSize = 2 * 1024 * 1024; // 2MB
                const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
                
                if (file.size > maxSize) {
                    allFilesValid = false;
                    alert(`El archivo ${input.labels[0].textContent} es demasiado grande. Máximo 2MB.`);
                    input.classList.add('is-invalid');
                } else if (!allowedTypes.includes(file.type)) {
                    allFilesValid = false;
                    alert(`El archivo ${input.labels[0].textContent} no tiene un formato válido.`);
                    input.classList.add('is-invalid');
                } else {
                    input.classList.remove('is-invalid');
                }
            }
        });
        
        if (!allFilesValid) {
            e.preventDefault();
            return false;
        }
        
        // Mostrar loading
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Actualizando...';
    });
    
    // Preview de archivos
    document.querySelectorAll('input[type="file"]').forEach(input => {
        input.addEventListener('change', function() {
            if (this.files.length > 0) {
                const fileName = this.files[0].name;
                const fileSize = (this.files[0].size / 1024 / 1024).toFixed(2);
                
                let preview = this.parentNode.querySelector('.file-preview');
                if (!preview) {
                    preview = document.createElement('div');
                    preview.className = 'file-preview mt-2 p-2 bg-light rounded';
                    this.parentNode.appendChild(preview);
                }
                
                preview.innerHTML = `
                    <small class="text-success">
                        <i class="fas fa-file me-1"></i>
                        ${fileName} (${fileSize} MB) - Será reemplazado al guardar
                    </small>
                `;
            }
        });
    });
});
</script>
@endsection