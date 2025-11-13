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
            --sidebar-bg: #2c3e50;
            --sidebar-text: #ecf0f1;
            --sidebar-hover: #34495e;
            --sidebar-active: #3498db;
            --main-bg: #f8f9fa;
            --brand-color: #ebeef1;
            --btn-hover-shadow: 0 8px 20px rgba(0,0,0,0.08);
        }

        /* Mapear módulos a paletas: un color (primario) por módulo y submódulos lo heredan. */
        body[data-module="comunicacion"] {
            --sidebar-bg: #1f2937; /* gris oscuro */
            --sidebar-text: #f8fafc;
            --sidebar-hover: #374151;
            --sidebar-active: #0ea5e9; /* azul claro característico de comunicaciones */
            --main-bg: #f8fafc;
        }

        body[data-module="gestion-academica"] {
            --sidebar-bg: #0f172a;
            --sidebar-text: #f1f5f9;
            --sidebar-hover: #111827;
            --sidebar-active: #16a34a; /* verde */
            --main-bg: #f8fafc;
        }

        body[data-module="gestion-financiera"] {
            --sidebar-bg: #2b0b3a;
            --sidebar-text: #fff5f7;
            --sidebar-hover: #3a0f4c;
            --sidebar-active: #7c3aed; /* morado */
            --main-bg: #fffafc;
        }

        body[data-module="gestion-disciplinaria"] {
            --sidebar-bg: #3e215cff;
            --sidebar-text: #f1fffcff;
            --sidebar-hover: #4b351c;
            --sidebar-active: #4043e8ff; /* ámbar/orange */
            --main-bg: #fffbf7;
        }

        body[data-module="orientacion"] {
            --sidebar-bg: #083344;
            --sidebar-text: #e6f6fb;
            --sidebar-hover: #0b4f61;
            --sidebar-active: #06b6d4; /* teal */
            --main-bg: #f7feff;
        }

        body[data-module="configuracion"] {
            --sidebar-bg: #2d2d2d;
            --sidebar-text: #e6e6e6;
            --sidebar-hover: #3b3b3b;
            --sidebar-active: #64748b; /* gris azulado */
            --main-bg: #f8fafc;
        }

        /* Login / tarjetas */
        .login-container {
            min-height: 100vh;
            background: url("{{ asset('images/basecolegio.png') }}") no-repeat center center fixed;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
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
    </style>
    
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
    @stack('scripts')
</body>
</html>