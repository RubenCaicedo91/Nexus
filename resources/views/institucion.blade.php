@extends('layouts.app')

@section('title', 'Gestión Institucional')

@section('content')
<div class="container mt-5 mb-5">
    <div class="card shadow-lg border-0 rounded-4">
        <div class="card-header bg-success text-white text-center rounded-top-4 py-3">
            <h4 class="mb-0"><i class="bi bi-building"></i> Gestión Institucional</h4>
        </div>

        <div class="card-body p-4">
            <form id="formInstitucion" method="POST" action="{{ route('institucion.update', $institucion->id ?? 1) }}">
                @csrf
                @method('PUT')

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Nombre de la Institución</label>
                        <input type="text" name="nombre" class="form-control" 
                               value="{{ $institucion->nombre ?? '' }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">NIT</label>
                        <input type="text" name="nit" class="form-control"
                               value="{{ $institucion->nit ?? '' }}">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Dirección</label>
                        <input type="text" name="direccion" class="form-control"
                               value="{{ $institucion->direccion ?? '' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Teléfono</label>
                        <input type="text" name="telefono" class="form-control"
                               value="{{ $institucion->telefono ?? '' }}">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Correo institucional</label>
                        <input type="email" name="correo" class="form-control"
                               value="{{ $institucion->correo ?? '' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Rector</label>
                        <input type="text" name="rector" class="form-control"
                               value="{{ $institucion->rector ?? '' }}">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Misión</label>
                    <textarea name="mision" class="form-control" rows="2">{{ $institucion->mision ?? '' }}</textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Visión</label>
                    <textarea name="vision" class="form-control" rows="2">{{ $institucion->vision ?? '' }}</textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">Valores institucionales</label>
                    <textarea name="valores" class="form-control" rows="2">{{ $institucion->valores ?? '' }}</textarea>
                </div>

                <div class="text-center">
                    <button type="submit" class="btn btn-success px-5">
                        <i class="bi bi-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
