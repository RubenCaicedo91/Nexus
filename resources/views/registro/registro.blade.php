@extends('layouts.app')
@php($hideSidebar = true)
@section('sidebar')
    {{-- Sidebar deshabilitado en registro --}}
@endsection

@section('title', 'Iniciar Sesión - CuentasCobro')

@section('content')
<div class="login-container d-flex align-items-center justify-content-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="login-card p-4">
                    <div class="text-center mb-4">
                        <i class="fas fa-file-invoice-dollar fa-3x text-primary mb-3"></i>
                        <h3 class="fw-bold text-dark">Colegio Nexus Team Education</h3>
                        <p class="text-muted">Registrar usuario</p>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="document_type" class="form-label">Tipo de documento</label>
                                <select id="document_type" name="document_type" class="form-select @error('document_type') is-invalid @enderror" required>
                                    <option value="" disabled {{ old('document_type') ? '' : 'selected' }}>Seleccione...</option>
                                    <option value="R.C" {{ old('document_type') == 'R.C' ? 'selected' : '' }}>R.C</option>
                                    <option value="C.C" {{ old('document_type') == 'C.C' ? 'selected' : '' }}>C.C</option>
                                    <option value="T.I" {{ old('document_type') == 'T.I' ? 'selected' : '' }}>T.I</option>
                                </select>
                                @error('document_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="document_number" class="form-label">Número de documento</label>
                                <input type="text" id="document_number" name="document_number" maxlength="30"
                                       class="form-control @error('document_number') is-invalid @enderror"
                                       value="{{ old('document_number') }}"
                                       placeholder="Número de documento" required>
                                @error('document_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="celular" class="form-label">Celular</label>
                                <input type="text" id="celular" name="celular" maxlength="20"
                                       class="form-control @error('celular') is-invalid @enderror"
                                       value="{{ old('celular') }}"
                                       placeholder="Teléfono celular">
                                @error('celular')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('register') }}">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">
                                    <i class="fas fa-user me-1"></i>Primer nombre
                                </label>
                                <input type="text"
                                       class="form-control @error('first_name') is-invalid @enderror"
                                       id="first_name"
                                       name="first_name"
                                       value="{{ old('first_name') }}"
                                       required
                                       autocomplete="given-name"
                                       autofocus
                                       placeholder="Primer nombre">
                                @error('first_name')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="second_name" class="form-label">Segundo nombre (opcional)</label>
                                <input type="text"
                                       class="form-control @error('second_name') is-invalid @enderror"
                                       id="second_name"
                                       name="second_name"
                                       value="{{ old('second_name') }}"
                                       autocomplete="additional-name"
                                       placeholder="Segundo nombre">
                                @error('second_name')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="first_last" class="form-label">Primer apellido</label>
                                <input type="text"
                                       class="form-control @error('first_last') is-invalid @enderror"
                                       id="first_last"
                                       name="first_last"
                                       value="{{ old('first_last') }}"
                                       required
                                       autocomplete="family-name"
                                       placeholder="Primer apellido">
                                @error('first_last')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="second_last" class="form-label">Segundo apellido (opcional)</label>
                                <input type="text"
                                       class="form-control @error('second_last') is-invalid @enderror"
                                       id="second_last"
                                       name="second_last"
                                       value="{{ old('second_last') }}"
                                       autocomplete="family-name"
                                       placeholder="Segundo apellido">
                                @error('second_last')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>

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
                                   autocomplete="new-password"
                                   placeholder="Ingresa tu contraseña">
                            @error('password')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                            <label for="password_confirmation" class="form-label">
                                <i class="fas fa-lock me-1"></i>Repetir Contraseña
                            </label>
                            <input type="password" 
                                   class="form-control @error('password_confirmation') is-invalid @enderror" 
                                   id="password_confirmation" 
                                   name="password_confirmation" 
                                   required 
                                   autocomplete="new-password"
                                   placeholder="Repite tu contraseña">
                            @error('password_confirmation')
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
                                <i class="fas fa-user-plus me-1"></i>Registrarse
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-4">
                        <small class="text-muted">
                            ¿No tienes una cuenta? 
                            <a href="#" class="text-decoration-none">Regístrate aquí</a>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection