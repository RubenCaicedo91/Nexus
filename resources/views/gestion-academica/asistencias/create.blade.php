@extends('layouts.app')

@section('title', 'Registrar asistencia')

@section('content')
    <div class="mb-3">
        <h3>Registrar asistencia</h3>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif


    {{-- Selector de curso y fecha (siempre visible). La tabla de alumnos se carga vía AJAX al elegir curso. --}}
    <div class="row mb-3">
        <div class="col-md-3">
            <label>Fecha</label>
            <input type="date" id="fecha-input" class="form-control" value="{{ old('fecha', date('Y-m-d')) }}" required>
        </div>
        <div class="col-md-4">
            <label>Curso</label>
            <select name="curso_id" id="curso-select" class="form-control">
                <option value="">-- Seleccione --</option>
                @foreach($cursos as $curso)
                    <option value="{{ $curso->id }}" {{ (isset($selectedCursoId) && $selectedCursoId == $curso->id) ? 'selected' : '' }}>{{ $curso->nombre }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div id="mass-form-container">
        <div class="alert alert-info">Seleccione un curso para cargar la lista de estudiantes y registrar asistencias.</div>
    </div>
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function(){
    // cambiar curso en el formulario básico recarga la vista con ?curso_id=...
    const cursoSelect = document.querySelector('select[name="curso_id"]');
    // NOTA: No auto-seleccionamos curso al cargar. El usuario debe elegir el curso.
    if(cursoSelect){
        cursoSelect.addEventListener('change', function(){
            const val = this.value;
            if(!val) return;
            const container = document.getElementById('mass-form-container');
            // Cargar formulario masivo por AJAX y reemplazar el contenedor
            fetch(`/gestion-academica/asistencias/curso/${val}/partial`, {
                headers: { 'Accept': 'text/html' }
            }).then(r => {
                if (!r.ok) throw r;
                return r.text();
            }).then(html => {
                container.innerHTML = html;
                // Copiar la fecha del formulario principal al partial (hidden input)
                const mainDate = document.getElementById('fecha-input');
                const partialHidden = container.querySelector('input#partial-fecha');
                if(partialHidden && mainDate) partialHidden.value = mainDate.value;
                // Ejecutar scripts inyectados (reemplazar etiquetas <script> para que se ejecuten)
                Array.from(container.querySelectorAll('script')).forEach(oldScript => {
                    const newScript = document.createElement('script');
                    if (oldScript.src) newScript.src = oldScript.src;
                    newScript.text = oldScript.innerHTML;
                    oldScript.parentNode.replaceChild(newScript, oldScript);
                });
                // Si el usuario cambia la fecha en el formulario principal, actualizar el hidden del partial
                if(mainDate){
                    mainDate.addEventListener('change', function(){
                        const ph = container.querySelector('input#partial-fecha');
                        if(ph) ph.value = this.value;
                    });
                }
            }).catch(err => {
                console.error('Error cargando alumnos del curso:', err);
                alert('No se pudieron cargar los alumnos del curso. Intente nuevamente.');
            });
        });
    }

});
</script>
@endsection
@endsection
