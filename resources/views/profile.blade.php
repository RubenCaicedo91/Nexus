@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="card">
        <div class="card-header">
            <h4>Perfil de usuario</h4>
        </div>
        <div class="card-body">
            <p><strong>Nombre:</strong> {{ $user->name }}</p>
            <p><strong>Email:</strong> {{ $user->email }}</p>
            <p><strong>Rol:</strong> {{ optional($user->role)->nombre ?? ($user->roles_id ?? '—') }}</p>

            @if(optional($user->role)->nombre === 'Acudiente')
                <a href="{{ route('perfil.crear_estudiante') }}" class="btn btn-success mb-3">Crear estudiante</a>

                @if($user->acudientes && $user->acudientes->count())
                    <h5 class="mt-3">Estudiantes asociados</h5>
                    <ul class="list-group mb-3">
                        @foreach($user->acudientes as $est)
                            <li class="list-group-item">
                                <strong>{{ $est->name }}</strong> — {{ $est->email }}
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted">No tienes estudiantes asociados aún.</p>
                @endif
            @endif

            <a href="{{ route('perfil.editar') }}" class="btn btn-primary">Editar datos</a>

            {{-- Si el usuario es estudiante, mostrar su acudiente --}}
            @if(optional($user->role)->nombre === 'Estudiante' && $user->acudiente)
                <div class="mt-4">
                    <h5>Acudiente</h5>
                    <p><strong>Nombre:</strong> {{ $user->acudiente->name }}</p>
                    <p><strong>Email:</strong> {{ $user->acudiente->email }}</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
