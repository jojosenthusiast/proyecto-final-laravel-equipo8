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
        <a class="marca" href="{{ route('inicio') }}">
            {{ config('app.name') }}
        </a>

        @include('partials.menu')
    </header>

    @include('partials.alertas')

    <main class="contenedor">
        @yield('contenido')
    </main>

    @include('partials.pie')
</body>
</html>
