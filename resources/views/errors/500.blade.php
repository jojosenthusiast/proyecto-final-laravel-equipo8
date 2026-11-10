
@extends('layouts.app')

@section('titulo', 'Algo falló en Biela')

@section('contenido')
    <h1 class="titulo-pagina">Algo falló en Biela</h1>

    <p class="vacio">
        Ocurrió un error inesperado.
        Intenta de nuevo en un momento.
    </p>

    <a class="boton" href="{{ url('/') }}">
        Ir al resumen
    </a>
@endsection
