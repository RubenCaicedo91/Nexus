@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="h4">Crear Usuario</h1>

    <div class="card">
        <div class="card-body">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('usuarios.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Primer nombre</label>
                        <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('first_name') }}" required>
                        @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Segundo nombre (opcional)</label>
                        <input type="text" name="second_name" class="form-control @error('second_name') is-invalid @enderror" value="{{ old('second_name') }}">
                        @error('second_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Primer apellido</label>
                        <input type="text" name="first_last" class="form-control @error('first_last') is-invalid @enderror" value="{{ old('first_last') }}" required>
                        @error('first_last')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Segundo apellido (opcional)</label>
                        <input type="text" name="second_last" class="form-control @error('second_last') is-invalid @enderror" value="{{ old('second_last') }}">
                        @error('second_last')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Contraseña</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirmar contraseña</label>
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Rol</label>
                    <select name="roles_id" class="form-select">
                        <option value="">-- Ninguno --</option>
                        @foreach($roles as $rol)
                            <option value="{{ $rol->id }}">{{ $rol->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Acudiente (si el rol elegido es Estudiante)</label>
                    <select name="acudiente_id" class="form-select @error('acudiente_id') is-invalid @enderror">
                        <option value="">-- Seleccione un acudiente (opcional) --</option>
                        @foreach($acudientes as $a)
                            <option value="{{ $a->id }}" {{ old('acudiente_id') == $a->id ? 'selected' : '' }}>{{ $a->name }} ({{ $a->document_number ?? '—' }})</option>
                        @endforeach
                    </select>
                    @error('acudiente_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Tipo de documento</label>
                        <select name="document_type" class="form-select @error('document_type') is-invalid @enderror">
                            <option value="" disabled {{ old('document_type') ? '' : 'selected' }}>Seleccione...</option>
                            <option value="R.C" {{ old('document_type') == 'R.C' ? 'selected' : '' }}>R.C</option>
                            <option value="C.C" {{ old('document_type') == 'C.C' ? 'selected' : '' }}>C.C</option>
                            <option value="T.I" {{ old('document_type') == 'T.I' ? 'selected' : '' }}>T.I</option>
                        </select>
                        @error('document_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Número de documento</label>
                        <input type="text" name="document_number" class="form-control @error('document_number') is-invalid @enderror" value="{{ old('document_number') }}">
                        @error('document_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Celular</label>
                        <input type="text" name="celular" class="form-control @error('celular') is-invalid @enderror" value="{{ old('celular') }}">
                        @error('celular')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Crear usuario</button>
                <a href="{{ route('usuarios.index') }}" class="btn btn-secondary ms-2">Cancelar</a>
            </form>
        </div>
    </div>
</div>
@endsection
