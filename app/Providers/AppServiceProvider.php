<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Routing\Router;

use App\Http\Middleware\RestrictOrientador;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Registrar alias de middleware para la restricción de orientador
        // El router está disponible a través del contenedor
        if ($this->app->bound('router')) {
            $router = $this->app->make('router');
            if ($router instanceof Router) {
                // Alias más genérico para manejar restricciones de acceso por rol
                $router->aliasMiddleware('restrict.role', RestrictOrientador::class);
            }
        }
    }
}
