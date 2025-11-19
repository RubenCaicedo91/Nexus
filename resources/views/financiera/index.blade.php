@extends('layouts.app')

@section('title', 'Gestión Financiera')

@section('content')
@php
    $canRegister = Auth::check() && method_exists(Auth::user(), 'hasPermission') && Auth::user()->hasPermission('registrar_pagos');
@endphp
<div class="container">

    @if(!empty($isCoordinator) && $isCoordinator)
        <div class="alert alert-warning mt-2">Como <strong>Coordinador Académico</strong> sólo puedes <strong>consultar el Estado de Cuenta</strong>. No puedes registrar pagos, actualizar el valor de matrícula ni generar reportes financieros desde este perfil.</div>
    @endif

    <!-- Banner superior -->
    <div class="p-4 mb-4 text-center text-white rounded" 
         style="background: linear-gradient(90deg, #0c3a5f, #1a73e8);">
        <h1 class="fw-bold">💰 <strong>Módulo de Gestión Financiera</strong> 📊</h1>
        <p class="mb-0">Administra los pagos, estados de cuenta y reportes financieros</p>
    </div>

    <div class="row g-3">

        <!-- Registrar Pago -->
        <div class="col-md-4">
            <div class="card border-primary h-100 shadow-lg">
                <div class="card-header text-white text-center" style="background-color: #00264d;">
                    <i class="fas fa-money-check-alt me-2"></i> Registrar Pago
                </div>
                <div class="card-body">
                    <p class="mb-3">💵 Permite registrar los pagos realizados por los estudiantes o usuarios.</p>
                    @if($canRegister)
                        <a href="{{ route('financiera.formularioPago') }}" class="btn text-center w-100 text-white" style="background-color: #00264d;">💰 Registrar Pago</a>
                    @else
                        <button type="button" class="btn w-100 btn-secondary" disabled title="No tienes permiso para registrar pagos." aria-disabled="true">💰 Registrar Pago</button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Estado de Cuenta -->
        <div class="col-md-4">
            <div class="card border-info h-100 shadow-lg">
                <div class="card-header text-white text-center" style="background-color: #0077b6;">
                    <i class="fas fa-file-invoice-dollar me-2"></i> Estado de Cuenta
                </div>
                <div class="card-body">
                    <p class="mb-3">📋 Consulta el estado de cuenta actual de cada usuario o estudiante.</p>
                    @php $isEstudiante = auth()->check() && optional(auth()->user()->role)->nombre && stripos(optional(auth()->user()->role)->nombre, 'estudiante') !== false; @endphp
                    @if(isset($isEstudiante) && $isEstudiante)
                        <a href="{{ route('financiera.estadoCuenta', auth()->id()) }}" class="btn text-center w-100 text-white" style="background-color: #0077b6;">📄 Consultar mi cuenta</a>
                    @else
                        <a href="{{ route('financiera.estadoCuenta.search') }}" 
                           class="btn text-center w-100 text-white" 
                           style="background-color: #0077b6;">
                            📄 Consultar Estado
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- Reporte Financiero -->
        <div class="col-md-4">
            <div class="card border-success h-100 shadow-lg">
                <div class="card-header text-white text-center" style="background-color: #0b6623;">
                    <i class="fas fa-chart-pie me-2"></i> Reporte Financiero
                </div>
                <div class="card-body">
                    <p class="mb-3">📊 Genera reportes financieros detallados para análisis institucional.</p>
                    @php
                        $canReport = false;
                        if (Auth::check()) {
                            $user = Auth::user();
                            if (method_exists($user, 'hasPermission') && $user->hasPermission('generar_reportes_financieros')) {
                                $canReport = true;
                            }
                            $roleName = optional($user->role)->nombre ?? '';
                            if (!$canReport && $roleName) {
                                $rn = mb_strtolower($roleName);
                                $rn = strtr($rn, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u']);
                                if (mb_stripos($rn, 'rector') !== false) {
                                    $canReport = true;
                                }
                            }
                        }
                    @endphp
                    @if($canReport)
                        <a href="{{ route('financiera.reporte') }}" class="btn text-center w-100 text-white" style="background-color: #0b6623;">📈 Generar Reporte</a>
                    @else
                        <button type="button" class="btn w-100 btn-secondary" disabled title="No tienes permiso para generar reportes." aria-disabled="true">📈 Generar Reporte</button>
                    @endif
                </div>
            </div>
        </div>

    </div>
</div>
@endsection