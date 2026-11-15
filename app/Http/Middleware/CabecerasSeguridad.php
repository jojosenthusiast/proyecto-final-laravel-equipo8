<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CabecerasSeguridad
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $response = $next($request);

        // [SEGURIDAD] Evita la interpretación incorrecta
        // de los tipos de archivos.
        $response->headers->set(
            'X-Content-Type-Options',
            'nosniff'
        );

        // [SEGURIDAD] Protección contra clickjacking.
        $response->headers->set(
            'X-Frame-Options',
            'SAMEORIGIN'
        );

        // [SEGURIDAD] Protege la información de navegación.
        $response->headers->set(
            'Referrer-Policy',
            'same-origin'
        );

        return $response;
    }
}
