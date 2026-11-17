<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('titulo') · {{ config('app.name') }}</title>

    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="stylesheet" href="{{ asset('css/estilos.css') }}">
    @stack('estilos')
    <script src="{{ asset('js/biela.js') }}" defer></script>
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
        @if ($errors->any())
            <section class="resumen-errores" aria-labelledby="titulo-errores" role="alert">
                <h2 id="titulo-errores">Revisa los campos del formulario</h2>
                <ul>
                    @foreach ($errors->getMessages() as $campo => $mensajes)
                        @foreach ($mensajes as $mensaje)
                            <li><a href="#{{ $campo }}">{{ $mensaje }}</a></li>
                        @endforeach
                    @endforeach
                </ul>
            </section>
        @endif

        @yield('contenido')
    </main>

    @include('partials.pie')

    <dialog id="confirmar-eliminacion" aria-labelledby="titulo-confirmacion" aria-describedby="mensaje-confirmacion">
        <form method="dialog">
            <h2 id="titulo-confirmacion">Confirmar eliminación</h2>
            <p id="mensaje-confirmacion"></p>
            <div class="acciones-dialogo">
                <button class="boton boton-secundario" value="cancelar" autofocus>Cancelar</button>
                <button class="boton boton-peligro" value="eliminar">Eliminar</button>
            </div>
        </form>
    </dialog>
</body>
</html>
