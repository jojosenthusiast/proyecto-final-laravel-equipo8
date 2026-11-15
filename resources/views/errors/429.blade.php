@extends('layouts.app')

@section('titulo', 'Demasiados intentos')

@section('contenido')

    <h1 class="titulo-pagina">429 - Demasiados intentos</h1>

    {{-- [SEGURIDAD] Límite de solicitudes. --}}

    <p>
        Has realizado demasiadas solicitudes
        en un corto periodo de tiempo.
    </p>

    <p>
        Espera unos segundos antes de intentar nuevamente.
    </p>

    <a href="{{ route('inicio') }}" class="boton">
        Volver al inicio
    </a>

@endsection
