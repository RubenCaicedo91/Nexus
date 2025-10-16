@extends('layouts.app')

@section('title', 'Crear Nuevo Rol - Colegio Nexus')

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar (opcional) -->
        <div class="col-md-2">
            <!-- Sidebar content -->
        </div>
        
        <!-- Main content -->
        <div class="col-md-10">
            <!-- Header con navegación -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="{{ route('roles.index') }}" class="text-decoration-none">
                                    <i class="fas fa-users-cog me-1"></i>Roles
                                </a>
                            </li>
                            <li class="breadcrumb-item active">Crear Nuevo Rol</li>
                        </ol>
                    </nav>
                    <h2 class="fw-bold text-dark mb-0">
                        <i class="fas fa-plus-circle text-primary me-2"></i>
                        Crear Nuevo Rol
                    </h2>
                </div>
                
                <div class="btn-group" role="group">
                    <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        Cancelar
                    </a>
                </div>
            </div>

            <!-- Mensajes de error -->
            @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <strong>¡Error!</strong> Por favor corrige los siguientes errores:
                <ul class="mb-0 mt-2">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            <form action="{{ route('roles.store') }}" method="POST" id="createRoleForm">
                @csrf
                
                <div class="row">
                    <!-- Información básica del rol -->
                    <div class="col-lg-4 mb-4">
                        <div class="card shadow">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Información Básica
                                </h5>
                            </div>
                            <div class="card-body">
                                <!-- Nombre del rol -->
                                <div class="mb-3">
                                    <label for="name" class="form-label fw-bold">
                                        <i class="fas fa-tag me-1"></i>
                                        Nombre del Rol <span class="text-danger">*</span>
                                    </label>
                                    <input type="text"
                                           class="form-control @error('nombre') is-invalid @enderror"
                                           id="nombre"
                                           name="nombre"
                                           value="{{ old('nombre') }}"
                                           placeholder="Ej: rector, coordinador_academico, docente"
                                           required>
                                    <div class="form-text">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Use solo letras minúsculas y guiones bajos (ej: rector, docente)
                                    </div>
                                    @error('nombre')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Descripción del rol -->
                                <div class="mb-3">
                                    <label for="description" class="form-label fw-bold">
                                        <i class="fas fa-align-left me-1"></i>
                                        Descripción <span class="text-danger">*</span>
                                    </label>
                                    <textarea class="form-control @error('descripcion') is-invalid @enderror"
                                              id="descripcion"
                                              name="descripcion"
                                              rows="4"
                                              placeholder="Describe las responsabilidades y funciones de este rol..."
                                              required>{{ old('descripcion') }}</textarea>
                                    <div class="form-text">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Máximo 500 caracteres
                                    </div>
                                    @error('descripcion')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Estadísticas de permisos seleccionados -->
                                <div class="mb-3">
                                    <div class="card bg-light">
                                        <div class="card-body py-2">
                                            <h6 class="mb-1">
                                                <i class="fas fa-chart-pie me-1"></i>
                                                Resumen de Permisos
                                            </h6>
                                            <div class="d-flex justify-content-between">
                                                <small class="text-muted">Permisos seleccionados:</small>
                                                <span class="badge bg-info" id="selectedCount">0</span>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <small class="text-muted">Total disponibles:</small>
                                                <span class="badge bg-secondary">{{ isset($availablePermissions) ? count($availablePermissions) : 0 }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Permisos disponibles (adaptados a colegio) -->
                    <div class="col-lg-8 mb-4">
                        <div class="card shadow">
                            <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-key me-2"></i>
                                    Asignar Permisos
                                </h5>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-light btn-sm" onclick="selectAllPermissions()">
                                        <i class="fas fa-check-square me-1"></i>
                                        Seleccionar Todo
                                    </button>
                                    <button type="button" class="btn btn-outline-light btn-sm" onclick="clearAllPermissions()">
                                        <i class="fas fa-square me-1"></i>
                                        Limpiar Todo
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                @php
                                $permissionCategories = [
                                    'Académico' => [
                                        'ver_estudiantes' => 'Ver estudiantes',
                                        'ver_matriculas' => 'Ver matrículas',
                                        'registrar_notas' => 'Registrar notas',
                                        'editar_notas' => 'Editar notas',
                                        'gestion_cursos' => 'Gestionar cursos y materias',
                                        'crear_horarios' => 'Crear horarios'
                                    ],
                                    'Disciplina' => [
                                        'registrar_reporte_disciplinario' => 'Registrar reporte disciplinario',
                                        'ver_reporte_disciplinario' => 'Ver reportes disciplinarios',
                                        'asignar_sanciones' => 'Asignar sanciones',
                                        'generar_informes_disciplina' => 'Generar informes de disciplina'
                                    ],
                                    'Asistencia' => [
                                        'registrar_asistencia' => 'Registrar asistencia',
                                        'ver_asistencias' => 'Ver registros de asistencia',
                                        'justificar_inasistencias' => 'Justificar inasistencias'
                                    ],
                                    'Horarios' => [
                                        'ver_horarios' => 'Ver horarios',
                                        'editar_horarios' => 'Editar horarios'
                                    ],
                                    'Finanzas' => [
                                        'ver_pensiones' => 'Ver pensiones',
                                        'registrar_pago_pension' => 'Registrar pagos de pensión',
                                        'generar_recibos' => 'Generar recibos'
                                    ],
                                    'Orientación' => [
                                        'ver_citas_orientacion' => 'Ver citas de orientación',
                                        'registrar_sesiones_orientacion' => 'Registrar sesiones de orientación'
                                    ],
                                    'Administración' => [
                                        'gestionar_usuarios' => 'Gestionar usuarios',
                                        'asignar_roles' => 'Asignar roles',
                                        'configurar_parametros' => 'Configurar parámetros del sistema'
                                    ],
                                    'Documentos' => [
                                        'subir_documentos' => 'Subir documentos',
                                        'ver_documentos' => 'Ver documentos institucionales'
                                    ],
                                    'Reportes' => [
                                        'ver_reportes_academicos' => 'Ver reportes académicos',
                                        'ver_reportes_financieros' => 'Ver reportes financieros',
                                        'exportar_reportes' => 'Exportar reportes'
                                    ],
                                    'Otros' => [
                                        'enviar_comunicados' => 'Enviar comunicados',
                                        'ver_notificaciones' => 'Ver notificaciones'
                                    ]
                                ];
                                @endphp

                                <div class="row">
                                    @foreach($permissionCategories as $category => $categoryPermissions)
                                    <div class="col-md-6 mb-4">
                                        <div class="border rounded p-3 h-100">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <h6 class="fw-bold text-primary mb-0">
                                                    @switch($category)
                                                        @case('Académico')
                                                            <i class="fas fa-graduation-cap me-1"></i>
                                                            @break
                                                        @case('Disciplina')
                                                            <i class="fas fa-user-shield me-1"></i>
                                                            @break
                                                        @case('Asistencia')
                                                            <i class="fas fa-user-check me-1"></i>
                                                            @break
                                                        @case('Horarios')
                                                            <i class="fas fa-clock me-1"></i>
                                                            @break
                                                        @case('Finanzas')
                                                            <i class="fas fa-wallet me-1"></i>
                                                            @break
                                                        @case('Orientación')
                                                            <i class="fas fa-hand-holding-heart me-1"></i>
                                                            @break
                                                        @case('Administración')
                                                            <i class="fas fa-cogs me-1"></i>
                                                            @break
                                                        @case('Documentos')
                                                            <i class="fas fa-file-alt me-1"></i>
                                                            @break
                                                        @case('Reportes')
                                                            <i class="fas fa-chart-bar me-1"></i>
                                                            @break
                                                        @default
                                                            <i class="fas fa-ellipsis-h me-1"></i>
                                                    @endswitch
                                                    {{ $category }}
                                                </h6>
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-primary toggle-category-btn"
                                                        data-category="{{ strtolower(str_replace(' ', '_', $category)) }}">
                                                    <i class="fas fa-check-square"></i>
                                                </button>
                                            </div>
                                            
                                            @foreach($categoryPermissions as $permission => $description)
                                            <div class="form-check mb-2">
                              <input class="form-check-input permission-checkbox {{ strtolower(str_replace(' ', '_', $category)) }}-permission"
                                  type="checkbox"
                                  id="permission_{{ $permission }}"
                                  name="permisos[]"
                                  value="{{ $permission }}"
                                  {{ in_array($permission, old('permisos', [])) ? 'checked' : '' }}
                                  onchange="updatePermissionCount()">
                                                <label class="form-check-label" for="permission_{{ $permission }}">
                                                    <small>{{ $description }}</small>
                                                </label>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones de acción -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Los campos marcados con <span class="text-danger">*</span> son obligatorios
                                    </div>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">
                                            <i class="fas fa-times me-1"></i>
                                            Cancelar
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i>
                                            Crear Rol
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
<style>
    .card {
        border: none;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    
    .form-check-input:checked {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }
    
    .breadcrumb-item + .breadcrumb-item::before {
        content: ">";
    }
    
    .permission-checkbox {
        cursor: pointer;
    }
    
    .form-check-label {
        cursor: pointer;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Auto-ocultar alertas después de 5 segundos (sin jQuery)
    setTimeout(function () {
        document.querySelectorAll('.alert').forEach(function (el) {
            el.style.transition = 'opacity 0.5s ease';
            el.style.opacity = '0';
            setTimeout(function(){ el.style.display = 'none'; }, 600);
        });
    }, 5000);

    // Contador de permisos seleccionados
    function updatePermissionCount() {
        const selectedCountEl = document.getElementById('selectedCount');
        if (!selectedCountEl) return;
        const checkedPermissions = document.querySelectorAll('input[name="permisos[]"]:checked');
        selectedCountEl.textContent = checkedPermissions.length;
    }

    // Seleccionar todos los permisos
    function selectAllPermissions() {
        const checkboxes = document.querySelectorAll('input[name="permisos[]"]');
        if (!checkboxes.length) return;
        checkboxes.forEach(checkbox => checkbox.checked = true);
        updatePermissionCount();
    }

    // Limpiar todos los permisos
    function clearAllPermissions() {
        const checkboxes = document.querySelectorAll('input[name="permisos[]"]');
        if (!checkboxes.length) return;
        checkboxes.forEach(checkbox => checkbox.checked = false);
        updatePermissionCount();
    }

    // Alternar permisos de una categoría
    function toggleCategoryPermissions(category) {
        if (!category) return;
        const categoryCheckboxes = document.querySelectorAll('.' + category + '-permission');
        if (!categoryCheckboxes.length) return;
        const checkedCount = Array.from(categoryCheckboxes).filter(cb => cb.checked).length;
        const shouldCheck = checkedCount === 0;
        categoryCheckboxes.forEach(checkbox => checkbox.checked = shouldCheck);
        updatePermissionCount();
    }

    // Exponer funciones globalmente para los botones con onclick
    window.updatePermissionCount = updatePermissionCount;
    window.selectAllPermissions = selectAllPermissions;
    window.clearAllPermissions = clearAllPermissions;
    window.toggleCategoryPermissions = toggleCategoryPermissions;

    // Conectar botones de alternar categoría (data-category)
    document.querySelectorAll('.toggle-category-btn').forEach(function(btn){
        btn.addEventListener('click', function(){
            const cat = btn.getAttribute('data-category');
            toggleCategoryPermissions(cat);
        });
    });

    // Validación del formulario
    const form = document.getElementById('createRoleForm');
    const nameInput = document.getElementById('nombre');
    const descriptionInput = document.getElementById('descripcion');

    if (form) {
        form.addEventListener('submit', function(e) {
            const name = nameInput ? nameInput.value.trim() : '';
            const description = descriptionInput ? descriptionInput.value.trim() : '';

            if (!name || !description) {
                e.preventDefault();
                alert('Por favor complete todos los campos obligatorios.');
                return false;
            }

            const namePattern = /^[a-z_]+$/;
            if (!namePattern.test(name)) {
                e.preventDefault();
                alert('El nombre del rol solo puede contener letras minúsculas y guiones bajos.');
                return false;
            }
        });
    }

    // Formatear el nombre automáticamente
    if (nameInput) {
        nameInput.addEventListener('input', function(e) {
            let value = e.target.value;
            // Reemplazar espacios por guión bajo y permitir letras minúsculas y guión bajo
            value = value.toLowerCase().replace(/\s+/g, '_').replace(/[^a-z_]/g, '');
            e.target.value = value;
        });
    }

    // Inicializar contador al cargar la página
    updatePermissionCount();
});
</script>
@endpush
@endsection