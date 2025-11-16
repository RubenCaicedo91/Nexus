<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte Financiero</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size:12px; margin:40px 30px 60px 30px }
        header { text-align:left; margin-bottom:10px }
        .project-name { font-size:14px; font-weight:700 }
        .report-title { font-size:13px; margin:2px 0 8px 0 }
        table { width:100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding:6px; text-align:left }
        th { background:#eee }
        footer.report-footer { position: fixed; bottom: 15px; right: 30px; font-size:10px; color:#333 }
    </style>
</head>
<body>
    <header>
        <div class="project-name">{{ config('app.name', 'Nexus') }}</div>
        <div class="report-title">Reporte Financiero</div>
    </header>
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

    <footer class="report-footer">
        @php
            $exportedBy = (\Illuminate\Support\Facades\Auth::check()) ? \Illuminate\Support\Facades\Auth::user()->name : 'Sistema';
            $exportedAt = \Carbon\Carbon::now()->format('d/m/Y H:i');
        @endphp
        <div>Generado por: {{ $exportedBy }}</div>
        <div>{{ $exportedAt }}</div>
    </footer>
</body>
</html>