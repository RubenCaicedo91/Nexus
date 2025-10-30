@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Estado de Cuenta del Estudiante</h2>

    @if($pagos->isEmpty())
        <p>No se han registrado pagos para este estudiante.</p>
    @else
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Concepto</th>
                    <th>Monto</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pagos as $pago)
                    <tr>
                        <td>{{ ucfirst($pago->concepto) }}</td>
                        <td>${{ number_format($pago->monto, 0, ',', '.') }}</td>
                        <td>{{ $pago->created_at->format('d/m/Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-3">
            <strong>Total Pagado:</strong>
            ${{ number_format($pagos->sum('monto'), 0, ',', '.') }}
        </div>
    @endif

    <a href="{{ route('financiera.formularioPago') }}" class="btn btn-secondary mt-3">Registrar otro pago</a>
</div>
@endsection
