<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('titulo') · {{ config('app.name') }}</title>

    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="stylesheet" href="{{ asset('css/estilos.css') }}">
</head>
<body>
    <header>
        <a class="marca" href="{{ url('/') }}">
            {{ config('app.name') }}
        </a>

        <nav>
            <a href="{{ url('/') }}">Resumen</a>
            <a href="{{ url('/vehiculos') }}">Vehículos</a>
            <a href="{{ url('/servicios') }}">Servicios</a>
        </nav>
    </header>

    @if (session('exito'))
        <div class="alerta">
            {{ session('exito') }}
        </div>
    @endif

    <main class="contenedor">
        @yield('contenido')
    </main>

    <footer>
        {{ config('app.name') }} · Caso 8, Taller mecánico · UNICAES
    </footer>
</body>
</html>