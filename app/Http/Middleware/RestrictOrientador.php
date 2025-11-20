<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RestrictOrientador
{
    /**
     * Maneja la restricción para el rol 'orientador'.
     * Permite solo rutas de orientación, comunicaciones y perfil.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user || ! optional($user->role)->nombre) {
            return $next($request);
        }

        $roleName = strtolower(optional($user->role)->nombre);

        // Reglas para orientador (permitir todo el módulo de orientación, comunicación y perfil)
        if ($roleName === 'orientador') {
            $routeName = $request->route() ? $request->route()->getName() : null;
            $allowedPrefixes = ['orientacion.', 'comunicacion.'];
            $allowedExact = ['perfil', 'perfil.editar', 'perfil.update', 'perfil.crear_estudiante', 'perfil.crear_estudiante.post'];
            $allowed = false;

            if ($routeName) {
                foreach ($allowedPrefixes as $p) {
                    if (str_starts_with($routeName, $p)) { $allowed = true; break; }
                }
                if (in_array($routeName, $allowedExact)) $allowed = true;
            } else {
                $path = $request->path();
                if (preg_match('#^gestion-orientacion#', $path) || preg_match('#^comunicacion#', $path) || preg_match('#^perfil#', $path)) {
                    $allowed = true;
                }
            }

            if (! $allowed) {
                abort(403, 'Acceso restringido para orientador');
            }
        }

        // Reglas para tesorero: solo permitir gestión financiera, comunicaciones y en orientación SOLO citas
        if ($roleName === 'tesorero') {
            $routeName = $request->route() ? $request->route()->getName() : null;
            $allowedPrefixes = ['financiera.', 'comunicacion.'];
            // En orientacion permitimos únicamente la ruta de citas
            // Allow common app entry points like dashboard and perfil so login redirect doesn't 403
            $allowedExact = ['orientacion.citas', 'orientacion.citas.create', 'orientacion.citas.store', 'dashboard', 'perfil', 'perfil.editar', 'perfil.update'];
            $allowed = false;

            if ($routeName) {
                foreach ($allowedPrefixes as $p) {
                    if (str_starts_with($routeName, $p)) { $allowed = true; break; }
                }
                if (in_array($routeName, $allowedExact)) $allowed = true;
            } else {
                $path = $request->path();
                if (preg_match('#^gestion-financiera#', $path) || preg_match('#^comunicacion#', $path) || preg_match('#^gestion-orientacion/citas#', $path) || preg_match('#^dashboard#', $path) || preg_match('#^perfil#', $path)) {
                    $allowed = true;
                }
            }

            if (! $allowed) {
                abort(403, 'Acceso restringido para tesorero');
            }
        }

        return $next($request);
    }
}
