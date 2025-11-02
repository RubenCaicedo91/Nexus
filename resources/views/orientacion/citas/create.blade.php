@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Solicitar cita</h2>
    <p class="text-muted">Selecciona fecha y hora para la atención.</p>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('orientacion.citas.store') }}" method="POST" class="row g-3">
                @csrf

                <div class="col-md-6">
                    <label for="fecha" class="form-label">Fecha y hora</label>
                    <input type="datetime-local" id="fecha" name="fecha" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Estudiante</label>
                    <input type="text" class="form-control" value="{{ auth()->user()->id }}" disabled>
                    <input type="hidden" name="estudiante_id" value="{{ auth()->user()->id }}">
                </div>

                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-success">Guardar</button>
                    <a href="{{ route('orientacion.citas') }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>

            @if($errors->any())
                <div class="alert alert-danger mt-3">
                    <ul class="mb-0">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection