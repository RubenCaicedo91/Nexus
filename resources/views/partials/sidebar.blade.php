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
                <a class="nav-link" href="{{ route('gestion.index') }}">Gestión de Horarios</a>
                <a class="nav-link" href="{{ route('matriculas.index') }}">Gestión de Matrículas</a>
                <a class="nav-link {{ request()->is('asignaciones*') ? 'active' : '' }}" href="{{ route('asignaciones.index') }}">
                    <i class="fas fa-tasks me-2"></i>Asignaciones
                </a>
                <a class="nav-link {{ request()->is('materias*') ? 'active' : '' }}" href="{{ route('materias.index') }}">
                    <i class="fas fa-book-open me-2"></i>Materias
                </a>
                <a class="nav-link {{ request()->is('gestion-academica/docentes*') ? 'active' : '' }}" href="{{ route('docentes.index') }}">
                    <i class="fas fa-chalkboard-teacher me-2"></i>Docentes
                </a>
                {{-- Gestión de Notas: moved to main menu level --}}
            </div>
            <a class="nav-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#submenuNotasMain" role="button" aria-expanded="false" aria-controls="submenuNotasMain">
                <span><i class="fas fa-clipboard-list me-2"></i>Gestión de Notas</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse ps-4" id="submenuNotasMain">
                <a class="nav-link {{ request()->is('notas') ? 'active' : '' }}" href="{{ route('notas.index') }}">
                    <i class="fas fa-edit me-2"></i>Administrar Notas
                </a>
                <a class="nav-link {{ request()->is('notas/create') ? 'active' : '' }}" href="{{ route('notas.create') }}">
                    <i class="fas fa-plus-circle me-2"></i>Crear Nota
                </a>
            </div>

            <a class="nav-link {{ request()->is('gestion-disciplinaria*') ? 'active' : '' }}" href="{{ route('gestion-disciplinaria.index') }}">
                <i class="fas fa-user-graduate me-2"></i>Gestion Disciplinaria
            </a>
            <!-- Submenú expandible para Gestión Financiera -->
            <a class="nav-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#submenuFinanciera" role="button" aria-expanded="false" aria-controls="submenuFinanciera">
                <span><i class="fas fa-chart-line me-2"></i>Gestión Financiera</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse ps-4" id="submenuFinanciera">
                <a class="nav-link {{ request()->is('gestion-financiera*') ? 'active' : '' }}" href="{{ route('financiera.index') }}">
                    <i class="fas fa-chart-bar me-2"></i>Dashboard Financiero
                </a>
                <a class="nav-link {{ request()->is('gestion-financiera/registrar-pago*') ? 'active' : '' }}" href="{{ route('financiera.formularioPago') }}">
                    <i class="fas fa-credit-card me-2"></i>Registrar Pago
                </a>
                <a class="nav-link {{ request()->is('pensiones*') ? 'active' : '' }}" href="{{ route('pensiones.index') }}">
                    <i class="fas fa-money-bill-wave me-2"></i>Gestión de Pensiones
                </a>
                <a class="nav-link {{ request()->is('pensiones/reportes*') ? 'active' : '' }}" href="{{ route('pensiones.reporte') }}">
                    <i class="fas fa-chart-pie me-2"></i>Reportes de Pensiones
                </a>
                @if(auth()->user()->roles_id != 4)
                    <a class="nav-link {{ request()->is('pensiones/create') ? 'active' : '' }}" href="{{ route('pensiones.create') }}">
                        <i class="fas fa-plus-circle me-2"></i>Nueva Pensión
                    </a>
                @endif
            </div>
            <a class="nav-link" href="#">
                <i class="fas fa-comment-dots me-2"></i>Modulo de Comunicaciones
            </a>
            <!-- Submenú expandible para Módulo de Orientación -->
            <a class="nav-link d-flex justify-content-between align-items-center" data-bs-toggle="collapse" href="#submenuOrientacion" role="button" aria-expanded="false" aria-controls="submenuOrientacion">
                <span><i class="fas fa-comments me-2"></i>Módulo de Orientación</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse ps-4" id="submenuOrientacion">
                <a class="nav-link {{ request()->is('gestion-orientacion*') ? 'active' : '' }}" href="{{ route('orientacion.index') }}">
                    <i class="fas fa-clipboard-list me-2"></i>Gestión Orientación
                </a>
                <a class="nav-link {{ request()->is('citas*') ? 'active' : '' }}" href="{{ route('citas.index') }}">
                    <i class="fas fa-calendar-alt me-2"></i>Gestión de Citas
                </a>
                <a class="nav-link {{ request()->is('citas/calendario*') ? 'active' : '' }}" href="{{ route('citas.calendario') }}">
                    <i class="fas fa-calendar me-2"></i>Calendario de Citas
                </a>
                <a class="nav-link {{ request()->is('seguimientos*') ? 'active' : '' }}" href="{{ route('seguimientos.index') }}">
                    <i class="fas fa-user-check me-2"></i>Seguimiento Estudiantes
                </a>
                <a class="nav-link {{ request()->is('seguimientos/dashboard*') ? 'active' : '' }}" href="{{ route('seguimientos.dashboard') }}">
                    <i class="fas fa-chart-bar me-2"></i>Dashboard Seguimientos
                </a>
            </div>
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