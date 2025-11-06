@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Generar informe psicosocial</h2>
    <p class="text-muted">Solo se pueden generar informes para citas atendidas.</p>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('orientacion.informes.store') }}" method="POST" class="row g-3">
                @csrf

                <div class="col-12">
                    <label for="cita_id" class="form-label">Cita atendida</label>
                    <select id="cita_id" name="cita_id" class="form-select" required>
                        @forelse($citas as $cita)
                            <option value="{{ $cita->id }}">Cita #{{ $cita->id }} — {{ \Carbon\Carbon::parse($cita->fecha)->format('d/m/Y H:i') }}</option>
                        @empty
                            <option disabled>No hay citas atendidas disponibles</option>
                        @endforelse
                    </select>
                </div>

                <div class="col-12">
                    <label for="descripcion" class="form-label">Descripción</label>
                    <textarea id="descripcion" name="descripcion" class="form-control" rows="5" required></textarea>
                </div>

                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-success">Guardar</button>
                    <a href="{{ route('orientacion.informes') }}" class="btn btn-secondary">Cancelar</a>
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