@if($cita->resumen_cita)
    <h6><strong>Resumen / Observaciones</strong></h6>
    <p>{{ $cita->resumen_cita }}</p>
@endif

@if($cita->recomendaciones)
    <h6><strong>Recomendaciones</strong></h6>
    <p>{{ $cita->recomendaciones }}</p>
@endif

@if($cita->plan_seguimiento)
    <h6><strong>Plan de Seguimiento</strong></h6>
    <p>{{ $cita->plan_seguimiento }}</p>
@endif

@if($cita->requiere_seguimiento && $cita->fecha_seguimiento)
    <div class="alert alert-info mt-3">
        <strong>Seguimiento programado para:</strong>
        {{ optional($cita->fecha_seguimiento)->format('d/m/Y') }}
        @if(!empty($cita->hora_seguimiento)) a las {{ $cita->hora_seguimiento }}@endif
    </div>
@endif

@if(isset($cita->children) && $cita->children->count() > 0)
    <h6 class="mt-3"><strong>Seguimientos asociados</strong></h6>
    <ul>
        @foreach($cita->children as $child)
            <li><a href="{{ route('citas.show', $child) }}">Cita #{{ $child->id }}</a> - {{ $child->estado_formateado }} @if($child->fecha_asignada) ({{ $child->fecha_asignada->format('d/m/Y') }} @if($child->hora_asignada) a las {{ $child->hora_asignada }} @endif)@endif</li>
        @endforeach
    </ul>
@endif
