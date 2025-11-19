<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Exportar Asistencias</title>
    <style>
        body { font-family: DejaVu Sans, Helvetica, Arial, sans-serif; font-size: 12px; margin: 40px 30px 70px 30px; }
        header { text-align: left; margin-bottom: 8px; }
        .project-name { font-size: 16px; font-weight: bold; }
        .report-title { font-size: 14px; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #444; padding: 6px; }
        th { background: #eee; }
        .center { text-align: center; }
        footer.report-footer { position: fixed; bottom: 20px; right: 30px; text-align: right; font-size: 11px; }
        footer.report-footer .generated-by { font-weight: bold; }
    </style>
</head>
<body>
    <header>
        <div class="project-name">{{ config('app.name', 'Proyecto') }}</div>
        <div class="report-title">Asistencias</div>
    </header>

    @if(isset($groupStats) && is_array($groupStats) && count($groupStats) > 0)
        <div style="margin-top:10px;">
            @foreach($groupStats as $g)
                <div style="margin-bottom:6px; border:1px solid #ddd; padding:6px;">
                    <div><strong>Fecha:</strong> {{ $g['fecha'] ?? '-' }}</div>
                    <div><strong>Curso:</strong> {{ $g['curso_nombre'] ?? $g['curso_id'] }} @if($g['materia_nombre']) - <strong>Materia:</strong> {{ $g['materia_nombre'] }} @endif</div>
                    <div style="margin-top:4px;">
                        <strong>Total estudiantes:</strong> {{ $g['total'] }} &nbsp; | &nbsp;
                        <strong>Asistieron:</strong> {{ $g['present'] }} &nbsp; | &nbsp;
                        <strong>Faltaron:</strong> {{ $g['absent'] }} &nbsp; | &nbsp;
                        <strong>Con excusa:</strong> {{ $g['excuse'] }}
                    </div>
                </div>
            @endforeach
        </div>
    @elseif(isset($stats))
        <div style="margin-top:10px;">
            <strong>Total estudiantes:</strong> {{ $stats['total'] }} &nbsp; | &nbsp;
            <strong>Asistieron:</strong> {{ $stats['present'] }} &nbsp; | &nbsp;
            <strong>Faltaron:</strong> {{ $stats['absent'] }} &nbsp; | &nbsp;
            <strong>Con excusa:</strong> {{ $stats['excuse'] }}
        </div>
    @endif

    <p>Generado: {{ now()->format('Y-m-d H:i') }}</p>
    @php
        $requestedFecha = request()->query('fecha');
        $first = $asistencias->first();
        $inferredFecha = null;
        if (!$requestedFecha && $asistencias->count() == 1 && $first && $first->fecha) {
            try {
                $inferredFecha = \Carbon\Carbon::parse($first->fecha)->format('Y-m-d');
            } catch (\Exception $e) {
                $inferredFecha = null;
            }
        }
    @endphp
    @if($requestedFecha || $inferredFecha)
        <p>Fecha del reporte: {{ $requestedFecha ?? $inferredFecha }}</p>
    @endif

    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Curso</th>
                <th>Materia</th>
                <th>Estudiante</th>
                <th class="center">Presente</th>
                <th>Observación</th>
            </tr>
        </thead>
        <tbody>
            @foreach($asistencias as $a)
                <tr>
                    <td>
                        @if($a->fecha)
                            {{ \Carbon\Carbon::parse($a->fecha)->format('Y-m-d') }}
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ optional($a->curso)->nombre }}</td>
                    <td>{{ optional($a->materia)->nombre }}</td>
                    <td>{{ optional($a->estudiante)->name }}</td>
                    <td class="center">{{ $a->presente ? 'Sí' : 'No' }}</td>
                    <td>{{ $a->observacion }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <footer class="report-footer">
        <div class="generated-by">Generado por: {{ optional(Auth::user())->name ?? 'Sistema' }}</div>
        <div class="generated-at">{{ now()->format('Y-m-d H:i') }}</div>
    </footer>
</body>
</html>