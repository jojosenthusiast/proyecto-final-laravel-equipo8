
@extends('layouts.app')

@section('titulo', 'Página no encontrada')

@section('contenido')
    <h1 class="titulo-pagina">No encontramos esa página</h1>

    <p class="vacio">
        La dirección no existe o el vehículo ya no está registrado.
    </p>

    <a class="boton" href="{{ url('/') }}">
        Ir al resumen
    </a>
@endsection
