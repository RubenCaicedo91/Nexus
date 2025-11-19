@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Registrar seguimiento</h2>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('orientacion.seguimientos.store') }}" method="POST" class="row g-3">
                @csrf

                <div class="col-md-6">
                    <label class="form-label">Estudiante</label>
                    <input type="text" class="form-control" value="{{ auth()->user()->id }}" disabled>
                    <input type="hidden" name="estudiante_id" value="{{ auth()->user()->id }}">
                </div>

                <div class="col-12">
                    <label for="observaciones" class="form-label">Observaciones</label>
                    <textarea id="observaciones" name="observaciones" class="form-control" rows="5" required></textarea>
                </div>

                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-info">Guardar</button>
                    <a href="{{ route('orientacion.seguimientos') }}" class="btn btn-secondary">Cancelar</a>
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