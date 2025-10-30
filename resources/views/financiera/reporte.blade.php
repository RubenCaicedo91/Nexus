@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Reporte Financiero General</h2>

    @if($reporte->isEmpty())
        <p>No hay pagos registrados en el sistema.</p>
    @else
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID Estudiante</th>
                    <th>Concepto</th>
                    <th>Monto</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reporte as $pago)
                    <tr>
                        <td>{{ $pago->estudiante_id }}</td>
                        <td>{{ ucfirst($pago->concepto) }}</td>
                        <td>${{ number_format($pago->monto, 0, ',', '.') }}</td>
                        <td>{{ $pago->created_at->format('d/m/Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-3">
            <strong>Total General:</strong>
            ${{ number_format($reporte->sum('monto'), 0, ',', '.') }}
        </div>
    @endif
</div>
@endsection
