<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

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
        // Configura la paginación para utilizar clases compatibles con Bootstrap 4.
        Paginator::useBootstrapFour();

        // [SEGURIDAD] Fuera de producción, cualquier consulta N+1 (relación sin with/load) lanza una excepción.
        Model::preventLazyLoading(! app()->isProduction());
    }
}