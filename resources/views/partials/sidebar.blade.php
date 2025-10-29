<div class="col-md-3 col-lg-2 p-0">
    <div class="sidebar">
        <div class="p-3">
            <h6 class="text-white-50 text-uppercase">Menú Principal</h6>
        </div>
        <nav class="nav flex-column px-3">
            <a class="nav-link active" href="{{ route('dashboard') }}">
                <i class="fas fa-book me-2"></i>Gestion Institucional
            </a>
            <!-- Submenú expandible para Gestion Academica -->
            <a class="nav-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#submenuAcademica" role="button" aria-expanded="false" aria-controls="submenuAcademica">
                <span><i class="fas fa-chalkboard-teacher me-2"></i>Gestion Academica</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse ps-4" id="submenuAcademica">
                    <a class="nav-link" href="{{ route('gestion.index') }}">
                        <i class="fas fa-clock me-2"></i>Gestión de Horarios
                    </a>
                    <a class="nav-link" href="{{ route('matriculas.index') }}">
                        <i class="fas fa-clipboard-list me-2"></i>Gestión de Matrículas
                    </a>
                    
            </div>
            <a class="nav-link" href="#">
                <i class="fas fa-user-graduate me-2"></i>Gestion Disciplinaria
            </a>
            <a class="nav-link" href="#">
                <i class="fas fa-chart-line me-2"></i>Gestion Financiera
            </a>
            <a class="nav-link" href="#">
                <i class="fas fa-comment-dots me-2"></i>Orientacion
            </a>
            <a class="nav-link" href="#">
                <i class="fas fa-comments me-2"></i>Modulo de Comunicación
            </a>
            <!-- Submenú expandible para Configuración -->
            <a class="nav-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#submenuConfig" role="button" aria-expanded="false" aria-controls="submenuConfig">
                <span><i class="fas fa-cog me-2"></i>Configuración</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse ps-4" id="submenuConfig">
                <a class="nav-link" href="#">Ajustes Generales</a>
                @if(auth()->check() && (
                    auth()->user()->hasPermission('gestionar_usuarios') ||
                    (optional(auth()->user()->role)->nombre && (
                        stripos(optional(auth()->user()->role)->nombre, 'admin') !== false ||
                        stripos(optional(auth()->user()->role)->nombre, 'administrador') !== false
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
                        stripos(optional(auth()->user()->role)->nombre, 'administrador') !== false
                    )) || auth()->user()->roles_id == 1
                ))
                    <a class="nav-link" href="{{ route('roles.index') }}">
                        <i class="fas fa-users-cog me-2"></i>Roles
                    </a>
                @endif
            </div>
            <!-- Acceso a Roles movido dentro de Configuración -->
        </nav>
    </div>
</div>
