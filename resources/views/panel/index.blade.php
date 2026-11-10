
@extends('layouts.app')

@section('titulo', 'Resumen')

@section('contenido')
    <h1 class="titulo-pagina">Resumen del taller</h1>

    <p>
        Consulta los vehículos registrados y las órdenes
        de servicio del taller.
    </p>

    <a class="boton" href="{{ url('/vehiculos') }}">
        Ver vehículos
    </a>
@endsection
