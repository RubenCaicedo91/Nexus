@extends('layouts.app')
@php($hideSidebar = true)
@section('sidebar')
    {{-- Sidebar deshabilitado en login --}}
@endsection

@section('title', 'Iniciar Sesión - CuentasCobro')

@section('content')

@push('styles')
<style>
    /* Fondo exclusivo para la página de login */
    body {
        background-image: url('{{ asset("images/basecolegio.png") }}');
        background-size: cover;
        background-position: center center;
        background-repeat: no-repeat;
        background-attachment: fixed;
    }

    /* Asegurar que el contenedor ocupe toda la altura y la tarjeta sea legible */
    .login-container {
        min-height: calc(100vh - 56px);
        padding: 3rem 0;
    }

    .login-card {
        background: rgba(255,255,255,0.95);
        border-radius: .75rem;
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    }
</style>
@endpush


<div class="login-container d-flex align-items-center justify-content-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="login-card p-4">
                    <div class="text-center mb-4">
                        <i class="fas fa-book fa-3x text-primary mb-3"></i>
                        <h3 class="fw-bold text-dark">Colegio Nexus Team Education</h3>
                        <p class="text-muted">Inicia sesión en tu cuenta</p>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope me-1"></i>Correo electrónico
                            </label>
                            <input type="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email') }}" 
                                   required 
                                   autocomplete="email" 
                                   autofocus
                                   placeholder="Ingresa tu correo">
                            @error('email')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock me-1"></i>Contraseña
                            </label>
                            <input type="password" 
                                   class="form-control @error('password') is-invalid @enderror" 
                                   id="password" 
                                   name="password" 
                                   required 
                                   autocomplete="current-password"
                                   placeholder="Ingresa tu contraseña">
                            @error('password')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">
                                Recordarme
                            </label>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-1"></i>Iniciar Sesión
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-4">
                        <small class="text-muted">
                            ¿No tienes una cuenta? 
                            <a href="{{ route('register')}}" class="text-decoration-none">Regístrate aquí</a>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection