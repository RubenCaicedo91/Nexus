<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Controlador con nombre incorrecto (RolControler) — este archivo existe por compatibilidad.
 * Redirige a `RolController` para evitar errores si hay referencias antiguas.
 */
class RolControler extends Controller
{
    public function __call($name, $arguments)
    {
        // Intentar delegar a RolController si existe
        if (class_exists(RolController::class)) {
            $controller = new RolController();
            if (method_exists($controller, $name)) {
                return call_user_func_array([$controller, $name], $arguments);
            }
        }

        abort(500, 'Controlador deprecado: use RolController en su lugar. Método solicitado: ' . $name);
    }
}
