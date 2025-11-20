<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Informes de Citas</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .header { text-align: left; margin-bottom: 10px; }
        .project-name { font-size: 18px; font-weight: 700; }
        .title { font-size: 15px; font-weight: bold; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #333; padding: 6px; text-align: left; }
        th { background: #eee; }
        .small { font-size: 11px; color: #444; }
        .footer { position: fixed; right: 20px; bottom: 10px; font-size: 11px; color: #444; }
    </style>
</head>
<body>
    <div class="header">
        <div class="project-name">{{ config('app.name', 'Nexus') }}</div>
        <div class="title">Informes de Citas</div>
        <div class="small">Generado: {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</div>

        @php
            $fSolicitanteName = 'Todos';
            if (request('solicitante_id')) {
                try { $u = \App\Models\User::find(request('solicitante_id')); $fSolicitanteName = $u ? $u->name : request('solicitante_id'); } catch (\Throwable $e) { $fSolicitanteName = request('solicitante_id'); }
            }
            $fRol = request('rol') ?: 'Todos';
            $fTipo = request('tipo') ? (isset($tipos[request('tipo')]) ? $tipos[request('tipo')] : request('tipo')) : 'Todos';
        @endphp
        <div class="small">Filtros — Solicitante: {{ $fSolicitanteName }} | Rol: {{ $fRol }} | Tipo: {{ $fTipo }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:50px">ID</th>
                <th style="width:180px">Solicitante</th>
                <th style="width:80px">Rol</th>
                <th style="width:120px">Tipo</th>
                <th style="width:110px">Fecha solicitud</th>
                <th style="width:180px">Atendido por</th>
                <th>Motivo / Resumen</th>
            </tr>
        </thead>
        <tbody>
            @foreach($citas as $cita)
                <tr>
                    <td>{{ $cita->id }}</td>
                    <td>{{ optional($cita->solicitante)->name ?? 'N/A' }}</td>
                    <td>{{ optional(optional($cita->solicitante)->role)->nombre ?? 'N/A' }}</td>
                    <td>{{ $tipos[$cita->tipo_cita] ?? $cita->tipo_cita }}</td>
                    <td>{{ $cita->fecha_solicitada ? \Carbon\Carbon::parse($cita->fecha_solicitada)->format('d/m/Y') . ' ' . ($cita->hora_solicitada ?? '') : 'Sin fecha' }}</td>
                    <td>{{ optional($cita->orientador)->name ?? 'Sin asignar' }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($cita->resumen_cita ?? $cita->motivo, 200) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="footer">Generado por: {{ auth()->user()->name ?? request()->user()->name ?? 'Sistema' }}</div>

    @if (isset($pdf))
        <script type="text/php">
            $font = $fontMetrics->getFont("DejaVu Sans", "normal");
            $size = 10;
            $y = $pdf->get_height() - 22;
            $x = ($pdf->get_width() / 2) - 40;
            $pdf->page_text($x, $y, "Página {PAGE_NUM} de {PAGE_COUNT}", $font, $size, array(0,0,0));
        </script>
    @endif
</body>
</html>