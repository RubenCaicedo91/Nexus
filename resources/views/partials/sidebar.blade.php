<div class="col-md-3 col-lg-2 p-0">
    <div class="sidebar">
        <div class="p-3">
            <h6 class="text-white-50 text-uppercase">Menú Principal</h6>
        </div>
        <nav class="nav flex-column px-3">

            <!-- Gestión Institucional -->
            <a class="nav-link active" href="{{ route('dashboard') }}">
                <i class="fas fa-book me-2"></i>Gestión Institucional
            </a>

            <!-- Gestión Académica -->
            @if(auth()->check() && (
                (method_exists(auth()->user(), 'hasPermission') && auth()->user()->hasPermission('gestionar_academica')) ||
                (optional(auth()->user()->role)->nombre && (
                    stripos(optional(auth()->user()->role)->nombre, 'admin') !== false ||
                    stripos(optional(auth()->user()->role)->nombre, 'administrador') !== false ||
                    stripos(optional(auth()->user()->role)->nombre, 'rector') !== false ||
                    stripos(optional(auth()->user()->role)->nombre, 'docente') !== false
                )) || auth()->user()->roles_id == 1
            ) && !(optional(auth()->user()->role)->nombre && stripos(optional(auth()->user()->role)->nombre, 'coordinador disciplina') !== false))
                <a class="nav-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#submenuAcademica" role="button" aria-expanded="false" aria-controls="submenuAcademica">
                    <span><i class="fas fa-chalkboard-teacher me-2"></i>Gestión Académica</span>
                    <i class="fas fa-chevron-down small"></i>
                </a>
                <div class="collapse ps-4" id="submenuAcademica">
                    <a class="nav-link" href="{{ route('gestion.index') }}">Dashboard Horarios</a>
                    <a class="nav-link" href="{{ route('matriculas.index') }}">Dashboard Matrículas</a>
                   
                </div>
            @endif

            <!-- Gestión de Notas -->
            @if(auth()->check() && (
                (method_exists(auth()->user(), 'hasAnyPermission') && auth()->user()->hasAnyPermission(['ver_notas','registrar_notas','consultar_reporte_academicos'])) ||
                (optional(auth()->user()->role)->nombre && (
                    stripos(optional(auth()->user()->role)->nombre, 'admin') !== false ||
                    stripos(optional(auth()->user()->role)->nombre, 'administrador') !== false ||
                    stripos(optional(auth()->user()->role)->nombre, 'rector') !== false
                )) || auth()->user()->roles_id == 1
            ) && !(optional(auth()->user()->role)->nombre && stripos(optional(auth()->user()->role)->nombre, 'coordinador disciplina') !== false))
                <a class="nav-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#submenuNotasMain" role="button" aria-expanded="false" aria-controls="submenuNotasMain">
                    <span><i class="fas fa-clipboard-list me-2"></i>Gestión de Notas</span>
                    <i class="fas fa-chevron-down small"></i>
                </a>
                <div class="collapse ps-4" id="submenuNotasMain">
                    <a class="nav-link" href="{{ route('notas.index') }}">
                        <i class="fas fa-edit me-2"></i>Dashboard Notas
                    </a>
                </div>
            @endif

            <!-- Gestión Disciplinaria -->
            @if(auth()->check() && (
                auth()->user()->hasAnyPermission([
                    'registrar_reporte_disciplinario',
                    'asignar_sanciones',
                    'consultar_informes_disciplinarios'
                ]) ||
                (optional(auth()->user()->role)->nombre && (
                    stripos(optional(auth()->user()->role)->nombre, 'admin') !== false ||
                    stripos(optional(auth()->user()->role)->nombre, 'administrador') !== false ||
                    stripos(optional(auth()->user()->role)->nombre, 'rector') !== false ||
                    stripos(optional(auth()->user()->role)->nombre, 'coordinador disciplina') !== false
                )) || auth()->user()->roles_id == 1
            ))
                <a class="nav-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#submenuDisciplinaria" role="button" aria-expanded="false" aria-controls="submenuDisciplinaria">
                    <span><i class="fas fa-user-graduate me-2"></i>Gestión Disciplinaria</span>
                    <i class="fas fa-chevron-down small"></i>
                </a>
                <div class="collapse ps-4" id="submenuDisciplinaria">
                    <a class="nav-link" href="{{ route('gestion-disciplinaria.index') }}">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard Disciplinario
                    </a>
                </div>
            @endif

            <!-- Gestión Financiera -->
            @if(auth()->check() && (
                (method_exists(auth()->user(), 'hasPermission') && auth()->user()->hasPermission('generar_reportes_financieros')) ||
                (optional(auth()->user()->role)->nombre && (
                    stripos(optional(auth()->user()->role)->nombre, 'tesor') !== false ||
                    stripos(optional(auth()->user()->role)->nombre, 'admin') !== false ||
                    stripos(optional(auth()->user()->role)->nombre, 'administrador') !== false ||
                    stripos(optional(auth()->user()->role)->nombre, 'rector') !== false
                )) || auth()->user()->roles_id == 1
            ) && !(optional(auth()->user()->role)->nombre && stripos(optional(auth()->user()->role)->nombre, 'coordinador disciplina') !== false))
                <a class="nav-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#submenuFinanciera" role="button" aria-expanded="false" aria-controls="submenuFinanciera">
                    <span><i class="fas fa-chart-line me-2"></i>Gestión Financiera</span>
                    <i class="fas fa-chevron-down small"></i>
                </a>
                <div class="collapse ps-4" id="submenuFinanciera">
                    <a class="nav-link" href="{{ route('financiera.index') }}">
                        <i class="fas fa-chart-bar me-2"></i>Dashboard Financiero
                    </a>
                </div>
            @endif

            <!-- Comunicaciones -->
            <a class="nav-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#submenuComunicacion" role="button" aria-expanded="false" aria-controls="submenuComunicacion">
                <span><i class="fas fa-bullhorn me-2"></i>Comunicaciones</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse ps-4" id="submenuComunicacion">
                <a class="nav-link" href="{{ route('comunicacion.index') }}">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard Comunicación
                </a>
            </div>

            <!-- Orientación -->
            <a class="nav-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#submenuOrientacion" role="button" aria-expanded="false" aria-controls="submenuOrientacion">
                <span><i class="fas fa-comments me-2"></i>Orientación</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse ps-4" id="submenuOrientacion">
                <a class="nav-link" href="{{ route('orientacion.index') }}">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard Orientación
                </a>
            </div>

            <!-- Configuración -->
            <a class="nav-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#submenuConfig" role="button" aria-expanded="false" aria-controls="submenuConfig">
                <span><i class="fas fa-cog me-2"></i>Configuración</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse ps-4" id="submenuConfig">
                @auth
                    <a class="nav-link" href="{{ route('perfil') }}">
                        <i class="fas fa-user me-2"></i>Perfil
                    </a>
                @endauth

                @if(auth()->check() && (
                    auth()->user()->hasPermission('gestionar_usuarios') ||
                    (optional(auth()->user()->role)->nombre && (
                        stripos(optional(auth()->user()->role)->nombre, 'admin') !== false ||
                        stripos(optional(auth()->user()->role)->nombre, 'administrador') !== false ||
                        stripos(optional(auth()->user()->role)->nombre, 'rector') !== false
                    )) || auth()->user()->roles_id == 1
                ))
                    <a class="nav-link" href="{{ route('usuarios.index') }}">
                        <i class="fas fa-users me-2"></i>Usuarios
                    </a>
                @else
                    <a class="nav-link text-muted" href="#">Usuarios</a>
                @endif

                @if(auth()->check() && (
                    auth()->user()->hasPermission('ver_roles') ||
                    (optional(auth()->user()->role)->nombre && (
                        stripos(optional(auth()->user()->role)->nombre, 'admin') !== false ||
                        stripos(optional(auth()->user()->role)->nombre, 'administrador') !== false ||
                        stripos(optional(auth()->user()->role)->nombre, 'rector') !== false
                    )) || auth()->user()->roles_id == 1
                ))
                    <a class="nav-link" href="{{ route('roles.index') }}">
                        <i class="fas fa-users-cog me-2"></i>Roles
                    </a>
                @endif
            </div>
        </nav>
    </div>
</div>
