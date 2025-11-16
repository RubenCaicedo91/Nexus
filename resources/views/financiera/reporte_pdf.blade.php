<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte Financiero</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size:12px }
        table { width:100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding:6px; text-align:left }
        th { background:#eee }
    </style>
</head>
<body>
    <h3 style="margin-top:0">Reporte Financiero</h3>
    <table>
        <thead>
            <tr>
                <th>Estudiante</th>
                <th>Concepto</th>
                <th>Monto</th>
                <th>Curso</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reporte as $p)
                @php
                    $mat = null;
                    if (optional($p->estudiante)->matriculas) {
                        $mat = $p->estudiante->matriculas->sortByDesc('fecha_matricula')->first();
                    }
                    $cursoNombre = $mat && $mat->curso ? $mat->curso->nombre : '-';
                    $estadoMat = $mat ? ($mat->estado ?? '-') : '-';
                @endphp
                <tr>
                    <td>{{ optional($p->estudiante)->name ?? $p->estudiante_id }}</td>
                    <td>{{ ucfirst($p->concepto) }}</td>
                    <td>{{ number_format($p->monto, 0, ',', '.') }}</td>
                    <td>{{ $cursoNombre }}</td>
                    <td>{{ $estadoMat }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p><strong>Total:</strong> ${{ number_format($reporte->sum('monto'), 0, ',', '.') }}</p>
</body>
</html>