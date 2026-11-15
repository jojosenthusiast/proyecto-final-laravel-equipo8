@extends('layouts.app')

@section('titulo', 'Sesión expirada')

@section('contenido')

    <h1 class="titulo-pagina">419 - Sesión expirada</h1>

    {{-- [SEGURIDAD] Protección contra solicitudes CSRF inválidas. --}}

    <p>
        Tu sesión o formulario ha expirado.
        Por seguridad, la solicitud no pudo procesarse.
    </p>

    <p>
        Regresa al formulario, recarga la página
        e intenta nuevamente.
    </p>

    <a href="{{ route('inicio') }}" class="boton">
        Volver al inicio
    </a>

@endsection
