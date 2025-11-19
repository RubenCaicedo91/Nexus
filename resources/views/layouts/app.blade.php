<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Nexus Team Education')</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        /* 👇 Nueva parte: Fondo general con imagen */
        body {
            background-size: cover;
            background-attachment: fixed;
        }

        /* Sistema de colores por módulo usando variables CSS.
           Cada módulo puede definir su paleta en los selectores más abajo.
           Se mantiene la semántica de los elementos (no se cambian textos ni labels).
        */
        :root {
            /* Valores por defecto (fallback) */
            --sidebar-bg: #07315eff;
            --sidebar-text: #ecf0f1;
            
            font-weight: bold;
            color: var(--brand-color) !important;
        }



        /* Sidebar usando variables */
        .sidebar {
            background: var(--sidebar-bg);
            min-height: calc(100vh - 56px);
        }

        .sidebar .nav-link {
            color: var(--sidebar-text);
            padding: 12px 20px;
            border-radius: 5px;
            margin: 2px 0;
            transition: background-color 150ms ease, color 150ms ease, transform 120ms ease;
        }

        .sidebar .nav-link:hover {
            background: var(--sidebar-hover);
            color: #fff;
            transform: translateX(4px);
        }

        .sidebar .nav-link.active {
            background: var(--sidebar-active);
            color: #fff;
        }

        .main-content {
            background: var(--main-bg);
            min-height: calc(100vh - 56px);
        }

        /* Efecto hover para botones (cards y botones rápidos) */
        .btn {
            transition: background-color 150ms ease, color 150ms ease, box-shadow 150ms ease, transform 120ms ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--btn-hover-shadow);
        }

        /* Estilos específicos para botones outline para que cambien fondo al pasar */
        .btn-outline-primary:hover {
            background-color: #0d6efd;
            color: #fff;
            border-color: #0d6efd;
        }

        .btn-outline-info:hover {
            background-color: #0dcaf0;
            color: #fff;
            border-color: #0dcaf0;
        }

        /* Navbar con degradado */
        .navbar-gradient {
            background: linear-gradient(90deg, #013f35ff, #471173ff); /* azul oscuro a azul medio */
            color: #ffffff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .navbar-gradient .navbar-brand,
        .navbar-gradient .nav-link,
        .navbar-gradient .dropdown-item {
            color: #ffffff !important;
            font-weight: 500;
            border-radius: 0.25rem;
            padding: 0.5rem 1rem;
            transition: background-color 0.3s ease;
        }

        .navbar-gradient .nav-link:hover,
        .navbar-gradient .dropdown-item:hover {
            background-color: rgba(255, 255, 255, 0.15);
        }

        .navbar-gradient .dropdown-menu {
            background: #004080;
            border: none;
        }

        /* Floating panel removed — using Bootstrap modals for revision/motivo */

        .navbar-gradient .dropdown-divider {
        border-top: 1px solid rgba(255,255,255,0.2);
        }

        /* Forzar apariencia consistente para botones de revisión */
        .btn-revision {
            background-color: #198754 !important; /* bootstrap success */
            color: #fff !important;
            border-color: #198754 !important;
        }
        .btn-revision:hover {
            background-color: #157347 !important;
            border-color: #157347 !important;
            color: #fff !important;
        }
        .dropdown-item.btn-revision {
            color: #198754 !important;
        }




    </style>
    <!-- Floating panel removed; keep bootstrap modals for AJAX content -->
    
    @stack('styles')
</head>
@php
    // Determinar módulo y submódulo a partir de la URL para aplicar paletas por módulo
    $module = request()->segment(1) ?: 'default';
    $submodule = request()->segment(2) ?: '';
    // Normalizar como slug simple
    $moduleSlug = \Illuminate\Support\Str::slug($module ?: 'default', '-');
    $submoduleSlug = \Illuminate\Support\Str::slug($submodule ?: '', '-');
@endphp
<body data-module="{{ $moduleSlug }}" data-submodule="{{ $submoduleSlug }}">
    @include('partials.navbar')
    <div class="container-fluid">
        <div class="row">
            @if(auth()->check() && empty($hideSidebar))
                @include('partials.sidebar')
                <div class="col-md-9 col-lg-10">
            @else
                <div class="col-12">
            @endif
                    <div class="main-content p-4">
                        @yield('content')
                    </div>
                </div>
        </div>
    </div>
    <!-- jQuery (para compatibilidad) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    @yield('scripts')
        {{-- Reusable AJAX modal for Citas revision --}}
        <div class="modal fade" id="ajaxRevisionModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Revisión</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="ajaxRevisionModalBody">
                        {{-- content loaded via AJAX --}}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Legacy AJAX handler for .btn-revision removed.
            // Revisión ahora usa navegación a la página completa `citas.show`.
        </script>
    @stack('scripts')
</body>
</html>