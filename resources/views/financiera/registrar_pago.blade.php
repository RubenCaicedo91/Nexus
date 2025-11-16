@extends('layouts.app')

@section('content')
@php
    $canEditValor = false;
    if (Auth::check()) {
        $userTmp = Auth::user();
        $roleNombreTmp = optional($userTmp->role)->nombre ?? '';
        $allowedRolesTmp = ['Tesorero', 'tesorero', 'Administrador_sistema', 'Administrador de sistema', 'Administrador'];
        foreach ($allowedRolesTmp as $arTmp) {
            if ($roleNombreTmp === $arTmp || stripos($roleNombreTmp, $arTmp) !== false) { $canEditValor = true; break; }
        }
    }
@endphp
<div class="container py-4">
    <div class="card shadow-sm rounded overflow-hidden">

        <!-- Encabezado oscuro -->
        <div class="bg-dark text-white p-3">
            <h2 class="fw-semibold mb-0">
                <i class="bi bi-cash-coin me-2 text-warning"></i> Registro de Pago Escolar
            </h2>
            <p class="small mb-0 text-light">Completa los datos para registrar un nuevo pago en el sistema.</p>
        </div>

        <!-- Separador -->
        <hr class="m-0">

        <!-- Contenido del formulario -->
        <div class="p-4 bg-light">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            @endif

            {{-- Lista de matrículas con pago pendiente de validación --}}
            @php
                $roleNombreTmp = optional(Auth::user()->role)->nombre ?? '';
                $isPrivilegedView = (stripos($roleNombreTmp, 'tesor') !== false) || (stripos($roleNombreTmp, 'administrador') !== false) || (stripos($roleNombreTmp, 'admin') !== false) || (isset($isPrivileged) && $isPrivileged);
            @endphp

            @if(isset($pendientes) && count($pendientes) > 0)
                <div class="mb-4">
                    <h5 class="mb-2">Estudiantes con pago pendiente de verificación</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Estudiante</th>
                                    <th>Curso</th>
                                    <th>Monto</th>
                                    <th>Fecha pago</th>
                                    <th>Comprobante</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pendientes as $mat)
                                    <tr>
                                        <td>{{ $mat->id }}</td>
                                        <td>{{ optional($mat->user)->name ?? 'N/A' }}</td>
                                        <td>{{ optional($mat->curso)->nombre ?? ($mat->curso_nombre ?? 'N/A') }}</td>
                                        <td>{{ $mat->monto_pago ?? '-' }}</td>
                                        <td>{{ optional($mat->fecha_pago) ? $mat->fecha_pago : '-' }}</td>
                                        <td>
                                            @if($isPrivilegedView)
                                                @if(!empty($mat->comprobantes) && $mat->comprobantes->count() > 0)
                                                    <ul class="mb-0 ps-3">
                                                        @foreach($mat->comprobantes as $c)
                                                            <li class="small">
                                                                <a href="{{ route('matriculas.comprobanteFile', ['matricula' => $mat->id, 'filename' => $c->filename]) }}" target="_blank">{{ $c->filename }}</a>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @elseif($mat->comprobante_pago)
                                                    <a href="{{ $mat->comprobante_pago_url ?? route('matriculas.archivo', ['matricula' => $mat->id, 'campo' => 'comprobante_pago']) }}" target="_blank">Ver</a>
                                                @else
                                                    -
                                                @endif
                                            @else
                                                @if($mat->comprobante_pago)
                                                    <a href="{{ $mat->comprobante_pago_url ?? route('matriculas.archivo', ['matricula' => $mat->id, 'campo' => 'comprobante_pago']) }}" target="_blank">Ver</a>
                                                @else
                                                    -
                                                @endif
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @php
                                                // Construir arreglo de comprobantes (filename + url) para pasar al botón
                                                $compList = [];
                                                if (!empty($isPrivilegedView) && !empty($mat->comprobantes) && $mat->comprobantes->count() > 0) {
                                                    foreach ($mat->comprobantes as $c) {
                                                        $compList[] = ['filename' => $c->filename, 'url' => route('matriculas.comprobanteFile', ['matricula' => $mat->id, 'filename' => $c->filename])];
                                                    }
                                                } elseif (!empty($mat->comprobante_pago)) {
                                                    $compList[] = ['filename' => basename($mat->comprobante_pago), 'url' => ($mat->comprobante_pago_url ?? route('matriculas.archivo', ['matricula' => $mat->id, 'campo' => 'comprobante_pago']))];
                                                }
                                            @endphp
                                            <button type="button" 
                                                class="btn btn-sm btn-outline-primary"
                                                data-id="{{ $mat->user_id ?? optional($mat->user)->id ?? $mat->id }}"
                                                data-monto="{{ $mat->monto_pago ?? '' }}"
                                                data-fecha="{{ $mat->fecha_pago ?? '' }}"
                                                data-comprobante="{{ $mat->comprobante_pago_url ?? '' }}"
                                                data-comprobantes='@json($compList)'
                                                onclick="selectEstudiante(this)">
                                                Seleccionar
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('financiera.registrarPago') }}">
                @csrf

                <div class="mb-4">
                    <span class="text-uppercase text-secondary small">Estudiante</span>
                    <input type="text" name="estudiante_id" id="estudiante_id" class="form-control mt-1" placeholder="Ej. 1023" required>
                </div>

                <div class="mb-3 row">
                    <div class="col-md-4 mb-3">
                        <span class="text-uppercase text-secondary small">Concepto</span>
                        <select name="concepto" id="concepto" class="form-select mt-1">
                            <option value="matricula">Matrícula</option>
                            <option value="pension">Pensión</option>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <span class="text-uppercase text-secondary small">Valor matrícula (sistema)</span>
                        <div class="input-group mt-1">
                            <input type="text" id="valor_matricula_display" class="form-control" value="{{ number_format($valorMatricula, 0, ',', '.') }}" readonly>
                            @if($canEditValor)
                                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalEditarValor">Editar</button>
                            @endif
                        </div>
                        <input type="hidden" id="valor_matricula" value="{{ $valorMatricula }}">
                    </div>

                    <div class="col-md-4 mb-3">
                        <span class="text-uppercase text-secondary small">Monto pagado</span>
                        <input type="number" step="0.01" name="monto" id="monto" class="form-control mt-1" placeholder="$" required>
                    </div>
                </div>

                <div class="mb-3 row">
                    <div class="col-md-6">
                        <span class="text-uppercase text-secondary small">Comprobante</span>
                        <div id="comprobante_area" class="mt-1">-</div>
                    </div>
                    <div class="col-md-6">
                        <span class="text-uppercase text-secondary small">Faltante</span>
                        <input type="text" id="faltante_display" class="form-control mt-1" value="-" readonly>
                        <input type="hidden" id="faltante" name="faltante" value="0">
                    </div>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i> Registrar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@auth
    @if(isset($canEditValor) && $canEditValor)
        <!-- Modal editar valor matrícula -->
        <div class="modal fade" id="modalEditarValor" tabindex="-1" aria-labelledby="modalEditarValorLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalEditarValorLabel">Editar Valor Matrícula</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <form method="POST" action="{{ route('financiera.valorMatricula') }}">
                        @csrf
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Valor matrícula (COP)</label>
                                <input type="number" name="valor_matricula" class="form-control" step="0.01" min="0" value="{{ $valorMatricula }}" required>
                                <div class="form-text">Este valor se aplica como referencia para el cálculo del faltante. Solo tesorero/administrador pueden editarlo.</div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endauth

@push('scripts')
<script>
function selectEstudiante(btn){
    var input = document.getElementById('estudiante_id');
    if(!input) return;

    var id = btn.getAttribute('data-id');
    var monto = btn.getAttribute('data-monto') || '';
    var fecha = btn.getAttribute('data-fecha') || '';
    var comprobante = btn.getAttribute('data-comprobante') || '';

    input.value = id;

    // Rellenar monto si existe
    var montoInput = document.getElementById('monto');
    if(montoInput){
        montoInput.value = monto;
    }

    // Mostrar comprobante
    var compArea = document.getElementById('comprobante_area');
    if(compArea){
        // Intentar leer lista completa de comprobantes desde data-comprobantes (JSON)
        var compsJson = btn.getAttribute('data-comprobantes') || null;
        var rendered = '';
        if (compsJson) {
            try {
                var comps = JSON.parse(compsJson);
                if (Array.isArray(comps) && comps.length > 0) {
                    rendered = '<ul class="mb-0 ps-3">';
                    for (var i=0;i<comps.length;i++) {
                        var it = comps[i];
                        var fname = it.filename || it.name || ('archivo_' + (i+1));
                        var url = it.url || it.path || '#';
                        rendered += '<li class="small"><a href="'+url+'" target="_blank">'+fname+'</a></li>';
                    }
                    rendered += '</ul>';
                }
            } catch(e) {
                // noop, fallback
            }
        }

        if (!rendered) {
            if(comprobante){
                rendered = '<a href="'+comprobante+'" target="_blank">Ver comprobante</a>';
            } else {
                rendered = '-';
            }
        }

        compArea.innerHTML = rendered;
    }

    // Calcular faltante inmediato
    calcularFaltante();

    input.focus();
    window.scrollTo({ top: input.getBoundingClientRect().top + window.scrollY - 120, behavior: 'smooth' });
}

function calcularFaltante(){
    var valor = parseFloat(document.getElementById('valor_matricula').value) || 0;
    var monto = parseFloat(document.getElementById('monto').value) || 0;
    var falt = 0;
    if(monto < valor){
        falt = valor - monto;
    }

    var display = document.getElementById('faltante_display');
    var hidden = document.getElementById('faltante');
    if(display) display.value = falt > 0 ? new Intl.NumberFormat('es-CO').format(falt) : '0';
    if(hidden) hidden.value = falt;
}

document.addEventListener('DOMContentLoaded', function(){
    var montoInput = document.getElementById('monto');
    if(montoInput){
        montoInput.addEventListener('input', calcularFaltante);
    }
});
</script>
@endpush
