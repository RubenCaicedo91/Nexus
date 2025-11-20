@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
                            <td>
                                @if(!empty($nota->observaciones))
                                    @php
                                        $trunc = Illuminate\Support\Str::limit(strip_tags($nota->observaciones), 150);
                                        $fullId = 'obs_full_' . $loop->index;
                                    @endphp
                                    <div>{{ $trunc }}</div>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-link p-0 view-observacion" data-target-id="{{ $fullId }}">Ver</button>
                                    </div>
                                    <div id="{{ $fullId }}" class="observacion-full d-none">{!! nl2br(e($nota->observaciones)) !!}</div>
                                @else
                                    --
                                @endif
                            </td>
            </div>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @php
                $roleNameBanner = optional(Auth::user()->role)->nombre ?? '';
                $roleNameBannerNorm = strtr(mb_strtolower($roleNameBanner), ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u']);
                $isCoordinatorBanner = (stripos($roleNameBannerNorm, 'coordinador') !== false || stripos($roleNameBannerNorm, 'cordinador') !== false);
            @endphp

            @if(!empty($isCoordinatorBanner) && $isCoordinatorBanner)
                <div class="alert alert-warning">Como <strong>Coordinador Académico</strong> puedes <strong>quitar</strong> el estado <em>definitiva</em> de una nota, pero no puedes crear nuevas notas.</div>
            @endif

            <table class="table table-striped">
                <thead>
                    <tr>
                            <th>Materia</th>
                            <th>Calificación (0-5)</th>
                            <th>Aprobada</th>
                            <th>Definitiva</th>
                            <th>Observaciones</th>
                            @if(! (isset($isStudentView) && $isStudentView))
                                <th class="text-end">Acciones</th>
                            @endif
                        </tr>
                </thead>
                <tbody>
                    @foreach($notas as $nota)
                        <tr>
                            <td>{{ $nota->materia->nombre ?? 'N/A' }}</td>
                            @php
                                // Determinar la calificación efectiva en escala 0-5
                                $calif_efectiva = null;
                                if (isset($nota->valor) && $nota->valor !== null) {
                                    $v = floatval($nota->valor);
                                    if ($v <= 5.0) {
                                        $calif_efectiva = round($v, 2);
                                    } else {
                                        $calif_efectiva = round(($v / 100.0) * 5.0, 2);
                                    }
                                } elseif (isset($nota->calificacion) && $nota->calificacion !== null) {
                                    $calif_efectiva = $nota->calificacion;
                                } elseif (isset($nota->actividades) && $nota->actividades->count() > 0) {
                                    $calif_efectiva = round($nota->actividades->avg('valor'), 2);
                                }

                                $aprobada_display = ($calif_efectiva !== null && $calif_efectiva >= 3.0) ? true : false;
                            @endphp
                            <td>
                                @if($calif_efectiva !== null)
                                    {{ number_format($calif_efectiva, 2) }}
                                @else
                                    --
                                @endif
                            </td>
                            <td>
                                @if($aprobada_display)
                                    <span class="badge bg-success">Sí</span>
                                @else
                                    <span class="badge bg-warning text-dark">No</span>
                                @endif
                            </td>
                            <td>
                                @if($nota->definitiva)
                                    <span class="badge bg-dark">Sí</span>
                                @else
                                    <span class="badge bg-light text-dark">No</span>
                                @endif
                            </td>
                            <td>
                                @if(!empty($nota->observaciones))
                                    {{ 
                                        
                                        Illuminate\Support\Str::limit(strip_tags($nota->observaciones), 150)
                                    }}
                                @else
                                    --
                                @endif
                            </td>
                            @if(! (isset($isStudentView) && $isStudentView))
                            <td class="text-end">
                                @php
                                    $roleName = optional(Auth::user()->role)->nombre ?? null;
                                    $isPrivileged = ($roleName === 'Rector' || stripos($roleName, 'cordinador') !== false || optional(Auth::user())->roles_id == 1);
                                    $isStudentView = false;
                                    if (optional(Auth::user()->role)->nombre) {
                                        $isStudentView = stripos(optional(Auth::user()->role)->nombre, 'estudiante') !== false;
                                    }
                                @endphp

                                @if(! $isStudentView && (! $nota->definitiva || $isPrivileged))
                                    <a href="{{ route('notas.edit', $nota) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                                @endif

                                @php
                                    $roleName2 = optional(Auth::user()->role)->nombre ?? null;
                                    $canMark = ($roleName2 === 'Docente' || $roleName2 === 'Administrador_sistema' || optional(Auth::user())->roles_id == 1);
                                @endphp

                                @if(! $nota->definitiva && $canMark)
                                    <form action="{{ route('notas.definitiva', $nota) }}" method="POST" class="d-inline" onsubmit="return confirm('Marcar nota como definitiva? Esta acción bloqueará la edición.')">
                                        @csrf
                                        <button class="btn btn-sm btn-success">Marcar definitiva</button>
                                    </form>
                                @endif
                                @php
                                    $canUnmark = ($roleName === 'Rector' || stripos($roleName, 'cordinador') !== false || $roleName === 'Administrador_sistema' || optional(Auth::user())->roles_id == 1);
                                @endphp
                                @if($nota->definitiva && $canUnmark)
                                    <form action="{{ route('notas.definitiva.quitar', $nota) }}" method="POST" class="d-inline" onsubmit="return confirm('Quitar estado de nota definitiva? Esta acción permitirá editar la nota nuevamente.')">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-danger">Quitar definitiva</button>
                                    </form>
                                @endif
                            </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
<!-- Modal para mostrar observaciones completas -->
<div class="modal fade" id="observacionModal" tabindex="-1" aria-labelledby="observacionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="observacionModalLabel">Observaciones</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="observacionModalBody">
                <!-- contenido será insertado dinámicamente -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
        var obsButtons = document.querySelectorAll('.view-observacion');
        obsButtons.forEach(function(btn){
                btn.addEventListener('click', function(e){
                        var targetId = btn.getAttribute('data-target-id');
                        if (!targetId) return;
                        var contentEl = document.getElementById(targetId);
                        var modalBody = document.getElementById('observacionModalBody');
                        if (contentEl && modalBody) {
                                modalBody.innerHTML = contentEl.innerHTML;
                                var modalEl = document.getElementById('observacionModal');
                                if (typeof bootstrap !== 'undefined' && modalEl) {
                                        var modal = new bootstrap.Modal(modalEl);
                                        modal.show();
                                } else {
                                        // Fallback: mostrar elemento si no está disponible bootstrap JS
                                        alert(contentEl.innerText || contentEl.textContent);
                                }
                        }
                });
        });
});
</script>
@endpush
@endsection
