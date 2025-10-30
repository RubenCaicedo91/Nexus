@extends('layouts.app') {{-- Usa tu layout base si lo tienes --}}

@section('content')
<div class="container">
    <h2>Registrar Pago</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('financiera.registrarPago') }}">
        @csrf

        <div class="mb-3">
            <label for="estudiante_id" class="form-label">ID del Estudiante</label>
            <input type="text" name="estudiante_id" id="estudiante_id" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="concepto" class="form-label">Concepto</label>
            <select name="concepto" id="concepto" class="form-select">
                <option value="matricula">Matrícula</option>
                <option value="pension">Pensión</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="monto" class="form-label">Monto</label>
            <input type="number" name="monto" id="monto" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary">Registrar</button>
    </form>
</div>
@endsection