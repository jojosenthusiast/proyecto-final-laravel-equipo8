@extends('layouts.app')

@section('titulo', 'Acceso denegado')

@section('contenido')

    <h1 class="titulo-pagina">403 - Acceso denegado</h1>

    <p>
        {{ $exception->getMessage() ?: 'No tienes permiso para realizar esta acción.' }}
    </p>

    <a href="{{ route('inicio') }}" class="boton">
        Volver al inicio
    </a>

@endsection
