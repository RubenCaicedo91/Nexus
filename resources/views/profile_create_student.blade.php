@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <h4>Crear estudiante</h4>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('perfil.crear_estudiante.post') }}" method="POST">
                @csrf

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="first_name" class="form-label">Primer nombre</label>
                        <input type="text" name="first_name" id="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name') }}" required>
                        @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="second_name" class="form-label">Segundo nombre (opcional)</label>
                        <input type="text" name="second_name" id="second_name" class="form-control @error('second_name') is-invalid @enderror" value="{{ old('second_name') }}">
                        @error('second_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="first_last" class="form-label">Primer apellido</label>
                        <input type="text" name="first_last" id="first_last" class="form-control @error('first_last') is-invalid @enderror" value="{{ old('first_last') }}" required>
                        @error('first_last')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="second_last" class="form-label">Segundo apellido (opcional)</label>
                        <input type="text" name="second_last" id="second_last" class="form-control @error('second_last') is-invalid @enderror" value="{{ old('second_last') }}">
                        @error('second_last')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" required>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="password_confirmation" class="form-label">Confirmar contraseña</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control @error('password_confirmation') is-invalid @enderror" required>
                        @error('password_confirmation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="document_type" class="form-label">Tipo de documento</label>
                        <select id="document_type" name="document_type" class="form-select @error('document_type') is-invalid @enderror">
                            <option value="" disabled {{ old('document_type') ? '' : 'selected' }}>Seleccione...</option>
                            <option value="R.C" {{ old('document_type') == 'R.C' ? 'selected' : '' }}>R.C</option>
                            <option value="C.C" {{ old('document_type') == 'C.C' ? 'selected' : '' }}>C.C</option>
                            <option value="T.I" {{ old('document_type') == 'T.I' ? 'selected' : '' }}>T.I</option>
                        </select>
                        @error('document_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="document_number" class="form-label">Número de documento</label>
                        <input type="text" id="document_number" name="document_number" maxlength="30" class="form-control @error('document_number') is-invalid @enderror" value="{{ old('document_number') }}" placeholder="Número de documento">
                        @error('document_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="celular" class="form-label">Celular</label>
                        <input type="text" id="celular" name="celular" maxlength="20" class="form-control @error('celular') is-invalid @enderror" value="{{ old('celular') }}" placeholder="Teléfono celular">
                        @error('celular')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <button type="submit" class="btn btn-success">Crear estudiante</button>
                <a href="{{ route('perfil') }}" class="btn btn-secondary ms-2">Volver</a>
            </form>
        </div>
    </div>
</div>
@endsection
