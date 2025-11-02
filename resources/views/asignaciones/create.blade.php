@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-user-plus me-2"></i>Nueva Asignación de Estudiante
                    </h4>
                    <a href="{{ route('asignaciones.index') }}" class="btn btn-light">
                        <i class="fas fa-arrow-left me-2"></i>Volver al Listado
                    </a>
                </div>

                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Importante:</strong> Todos los documentos y el comprobante de pago son obligatorios para completar la asignación.
                        Solo se permite asignar estudiantes con documentación completa.
                    </div>

                    @if(isset($debug_data))
                        <div class="alert alert-warning">
                            <strong>🔍 DEBUG INFO:</strong><br>
                            📊 Total estudiantes: {{ $debug_data['total_estudiantes'] }}<br>
                            👥 Nombres: {{ implode(', ', $debug_data['estudiantes_nombres']) }}<br>
                            🔗 Query: {{ $debug_data['sql_query'] }}
                        </div>
                    @endif



                    <form action="{{ route('asignaciones.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
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
                                                @if(isset($estudiantes) && count($estudiantes) > 0)
                                                    @foreach($estudiantes as $estudiante)
                                                        <option value="{{ $estudiante->id }}" {{ old('user_id') == $estudiante->id ? 'selected' : '' }}>
                                                            {{ $estudiante->name }} - {{ $estudiante->email }} (ID: {{ $estudiante->id }})
                                                        </option>
                                                    @endforeach
                                                @else
                                                    <option value="" disabled>No hay estudiantes disponibles</option>
                                                @endif
                                            </select>
                                            
                                            @if(isset($estudiantes))
                                                <div class="form-text">
                                                    <small class="text-muted">Estudiantes cargados: {{ count($estudiantes) }}</small>
                                                    <button type="button" class="btn btn-sm btn-info ms-2" onclick="debugSelect()">🔍 Debug Select</button>
                                                </div>
                                            @endif
                                            @error('user_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="curso_id" class="form-label">Curso <span class="text-danger">*</span></label>
                                            <select name="curso_id" id="curso_id" class="form-select @error('curso_id') is-invalid @enderror" required>
                                                <option value="">-- Seleccionar curso --</option>
                                                @foreach($cursos as $curso)
                                                    <option value="{{ $curso->id }}" {{ old('curso_id') == $curso->id ? 'selected' : '' }}>
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
                                                   value="{{ old('fecha_matricula', date('Y-m-d')) }}" required>
                                            @error('fecha_matricula')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="estado" class="form-label">Estado <span class="text-danger">*</span></label>
                                            <select name="estado" id="estado" class="form-select @error('estado') is-invalid @enderror" required>
                                                <option value="activa" {{ old('estado', 'activa') == 'activa' ? 'selected' : '' }}>Activa</option>
                                                <option value="inactiva" {{ old('estado') == 'inactiva' ? 'selected' : '' }}>Inactiva</option>
                                                <option value="suspendida" {{ old('estado') == 'suspendida' ? 'selected' : '' }}>Suspendida</option>
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
                                            <label for="monto_pago" class="form-label">Monto del Pago <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text">$</span>
                                                <input type="number" name="monto_pago" id="monto_pago" 
                                                       class="form-control @error('monto_pago') is-invalid @enderror" 
                                                       step="0.01" min="0" value="{{ old('monto_pago') }}" required>
                                            </div>
                                            @error('monto_pago')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="fecha_pago" class="form-label">Fecha de Pago <span class="text-danger">*</span></label>
                                            <input type="date" name="fecha_pago" id="fecha_pago" 
                                                   class="form-control @error('fecha_pago') is-invalid @enderror" 
                                                   value="{{ old('fecha_pago', date('Y-m-d')) }}" required>
                                            @error('fecha_pago')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="comprobante_pago" class="form-label">Comprobante de Pago <span class="text-danger">*</span></label>
                                            <input type="file" name="comprobante_pago" id="comprobante_pago" 
                                                   class="form-control @error('comprobante_pago') is-invalid @enderror" 
                                                   accept=".pdf,.jpg,.jpeg,.png" required>
                                            <div class="form-text">Formatos permitidos: PDF, JPG, PNG. Máximo 2MB.</div>
                                            @error('comprobante_pago')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Documentos requeridos --}}
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">
                                    <i class="fas fa-file-alt me-2"></i>Documentos Requeridos
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="documento_identidad" class="form-label">
                                                Documento de Identidad <span class="text-danger">*</span>
                                            </label>
                                            <input type="file" name="documento_identidad" id="documento_identidad" 
                                                   class="form-control @error('documento_identidad') is-invalid @enderror" 
                                                   accept=".pdf,.jpg,.jpeg,.png" required>
                                            <div class="form-text">Cédula, tarjeta de identidad o pasaporte</div>
                                            @error('documento_identidad')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="rh" class="form-label">
                                                Certificado de Tipo RH <span class="text-danger">*</span>
                                            </label>
                                            <input type="file" name="rh" id="rh" 
                                                   class="form-control @error('rh') is-invalid @enderror" 
                                                   accept=".pdf,.jpg,.jpeg,.png" required>
                                            <div class="form-text">Certificado médico con tipo de sangre</div>
                                            @error('rh')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="certificado_medico" class="form-label">
                                                Certificado Médico <span class="text-danger">*</span>
                                            </label>
                                            <input type="file" name="certificado_medico" id="certificado_medico" 
                                                   class="form-control @error('certificado_medico') is-invalid @enderror" 
                                                   accept=".pdf,.jpg,.jpeg,.png" required>
                                            <div class="form-text">Certificado médico general</div>
                                            @error('certificado_medico')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="certificado_notas" class="form-label">
                                                Certificado de Notas <span class="text-danger">*</span>
                                            </label>
                                            <input type="file" name="certificado_notas" id="certificado_notas" 
                                                   class="form-control @error('certificado_notas') is-invalid @enderror" 
                                                   accept=".pdf,.jpg,.jpeg,.png" required>
                                            <div class="form-text">Boletín o certificado de notas anterior</div>
                                            @error('certificado_notas')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Nota:</strong> Todos los documentos son obligatorios. 
                                    Los formatos permitidos son PDF, JPG, JPEG y PNG. El tamaño máximo por archivo es de 2MB.
                                    La asignación solo se completará si todos los documentos están presentes.
                                </div>
                            </div>
                        </div>

                        {{-- Botones de acción --}}
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('asignaciones.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-times me-2"></i>Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-success" id="submitBtn">
                                        <i class="fas fa-save me-2"></i>Crear Asignación
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
    
    // Validar archivos antes del envío
    form.addEventListener('submit', function(e) {
        const fileInputs = form.querySelectorAll('input[type="file"]');
        let allFilesValid = true;
        
        fileInputs.forEach(input => {
            if (input.required && !input.files.length) {
                allFilesValid = false;
                input.classList.add('is-invalid');
            } else if (input.files.length > 0) {
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
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creando...';
    });
    
    // Preview de archivos (opcional)
    document.querySelectorAll('input[type="file"]').forEach(input => {
        input.addEventListener('change', function() {
            if (this.files.length > 0) {
                const fileName = this.files[0].name;
                const fileSize = (this.files[0].size / 1024 / 1024).toFixed(2);
                
                // Crear o actualizar el preview
                let preview = this.parentNode.querySelector('.file-preview');
                if (!preview) {
                    preview = document.createElement('div');
                    preview.className = 'file-preview mt-2 p-2 bg-light rounded';
                    this.parentNode.appendChild(preview);
                }
                
                preview.innerHTML = `
                    <small class="text-success">
                        <i class="fas fa-file me-1"></i>
                        ${fileName} (${fileSize} MB)
                    </small>
                `;
            }
        });
    });
    
    // Función global para debug del select
    window.debugSelect = function() {
        const select = document.getElementById('user_id');
        console.log('=== DEBUG SELECT DE ESTUDIANTES ===');
        console.log('Select element:', select);
        console.log('Total opciones:', select ? select.options.length : 'No encontrado');
        console.log('HTML interno:', select ? select.innerHTML : 'No hay HTML');
        
        if (select && select.options.length > 0) {
            console.log('Listado de opciones:');
            for (let i = 0; i < select.options.length; i++) {
                console.log(`  ${i}: value="${select.options[i].value}" text="${select.options[i].text}"`);
            }
        }
        
        const mensaje = `Select encontrado: ${select ? 'SÍ' : 'NO'}\nTotal opciones: ${select ? select.options.length : 0}\n\nRevisa la consola (F12) para más detalles.`;
        alert(mensaje);
    };
    
    // Debug automático al cargar la página
    console.log('=== AUTO DEBUG AL CARGAR ===');
    const autoSelect = document.getElementById('user_id');
    console.log('Select cargado:', autoSelect);
    console.log('Opciones cargadas:', autoSelect ? autoSelect.options.length : 0);
    if (autoSelect && autoSelect.options.length > 1) {  // > 1 porque incluye la opción por defecto
        console.log('¡Estudiantes encontrados correctamente!');
    } else {
        console.log('⚠️ PROBLEMA: No se cargaron estudiantes en el select');
    }
});
</script>
@endsection