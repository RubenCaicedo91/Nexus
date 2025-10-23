<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CuentasCobro')</title>
    
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
        /* 👆 Hasta aquí */

        .login-container {
            min-height: 100vh;
            background: url("{{ asset('images/basecolegio.png') }}") no-repeat center center fixed;
            /*background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);*/
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-brand {
            font-weight: bold;
            color: #ebeef1ff !important;
        }
        
        .sidebar {
            background: #2c3e50;
            min-height: calc(100vh - 56px);
        }
        
        .sidebar .nav-link {
            color: #ecf0f1;
            padding: 12px 20px;
            border-radius: 5px;
            margin: 2px 0;
        }
        
        /* Transición y efecto hover para los enlaces del sidebar */
        .sidebar .nav-link {
            color: #ecf0f1;
            padding: 12px 20px;
            border-radius: 5px;
            margin: 2px 0;
            transition: background-color 150ms ease, color 150ms ease, transform 120ms ease;
        }
        
        .sidebar .nav-link:hover {
            background: #34495e;
            color: #fff;
            transform: translateX(4px);
        }
        
        .sidebar .nav-link.active {
            background: #3498db;
            color: #fff;
        }
        
        .main-content {
            background: #f8f9fa;
            min-height: calc(100vh - 56px);
        }
        
        /* Efecto hover para botones (cards y botones rápidos) */
        .btn {
            transition: background-color 150ms ease, color 150ms ease, box-shadow 150ms ease, transform 120ms ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.08);
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
<body>
    @include('partials.navbar')
    <div class="container-fluid">
        <div class="row">
            @include('partials.sidebar')
            <div class="col-md-9 col-lg-10">
                <div class="main-content p-4">
                    @yield('content')
                </div>
            </div>
        </div>
    </div>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    @stack('scripts')
</body>
</html>