<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte disciplinario</title>
    <style>
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 12px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #333; padding: 6px; text-align: left; }
        th { background: #eee; }
    </style>
</head>
<body>
    <h2 style="text-align:left; margin-bottom:6px; font-size:18px; font-weight:700;">{{ config('app.name') }}</h2>
    <h3 style="margin-top:0; margin-bottom:12px; font-size:14px; font-weight:600;">Reporte disciplinario</h3>
    <table>
        <thead>
            <tr>
                <th>Estudiante</th>
                <th>Documento</th>
                <th>Descripción</th>
                <th>Tipo</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $s)
                <tr>
                    <td>{{ optional($s->usuario)->name ?? 'ID_'.$s->usuario_id }}</td>
                    <td>{{ optional($s->usuario)->document_number ?? '' }}</td>
                    <td>{{ $s->descripcion }}</td>
                    <td>{{ $s->tipo }}</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($s->fecha)->format('Y/m/d') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="position: fixed; bottom: 20px; right: 20px; text-align: right; font-size: 11px; color: #333;">
        <div>Generado por: {{ optional($generatedBy)->name ?? 'Sistema' }}</div>
        <div style="font-size:10px; color:#666;">{{ \Illuminate\Support\Carbon::now()->format('Y/m/d H:i') }}</div>
    </div>
</body>
</html>