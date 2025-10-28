@extends('layouts.app')

@section('content')
<div class="container-fluid py-5 d-flex justify-content-center">
  <div class="card shadow-lg border-0 text-center text-white"
       style="background: linear-gradient(135deg, #210e64ff, #220b4bff);
              border-radius: 20px;
              width: 95%;
              max-width: 1300px;
              padding: 25px 80px;">
    <div class="card-body p-0">
      <h1 class="fw-bold mb-2" style="font-size: 2.2rem;">🎓 Gestión Académica</h1>
      <p class="text-light mb-0" style="font-size: 1.1rem;">
        Accede rápidamente a los módulos de cursos, horarios y matrículas.
      </p>
    </div>
  </div>
</div>

<div class="row px-4">
    {{-- Tarjeta: Cursos --}}
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="text-primary mb-3">
                    <i class="fas fa-book fa-2x"></i>
                </div>
                <h5 class="card-title mb-3">Gestionar Cursos</h5>
                <a href="{{ route('cursos.panel') }}" class="btn btn-outline-primary w-100">
                    <i class="fas fa-edit me-2"></i>Ver y Crear Cursos
                </a>
            </div>
        </div>
    </div>

    {{-- Tarjeta: Horarios --}}
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="text-info mb-3">
                    <i class="fas fa-clock fa-2x"></i>
                </div>
                <h5 class="card-title mb-3">Horarios</h5>
                <a href="{{ route('gestion.horarios') }}" class="btn btn-info w-100">
                    <i class="fas fa-clock me-2"></i>Gestionar Horarios
                </a>
            </div>
        </div>
    </div>

    {{-- Tarjeta: Matrículas (opcional si tienes módulo activo) --}}
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="text-success mb-3">
                    <i class="fas fa-user-graduate fa-2x"></i>
                </div>
                <h5 class="card-title mb-3">Matrículas</h5>
                <a href="{{ route('matriculas.index') }}" class="btn btn-success w-100">
                    <i class="fas fa-user-plus me-2"></i>Gestionar Matrículas
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
