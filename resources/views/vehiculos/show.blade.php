@extends('layouts.app')

@section('titulo', 'Ficha')

@section('contenido')
    <h1 class="titulo-pagina">
        <span class="placa">{{ $vehiculo->placa }}</span>
    </h1>

    <dl class="ficha-datos">
        <dt>Marca</dt>
        <dd>{{ $vehiculo->marca }}</dd>

        <dt>Modelo</dt>
        <dd>{{ $vehiculo->modelo }}</dd>

        <dt>Año</dt>
        <dd>{{ $vehiculo->anio }}</dd>

        <dt>Color</dt>
        <dd>{{ $vehiculo->color }}</dd>

        <dt>Kilometraje</dt>
        <dd>{{ number_format($vehiculo->kilometraje) }} km</dd>

        <dt>Propietario</dt>
        <dd>{{ $vehiculo->propietario_nombre }}</dd>

        <dt>Teléfono</dt>
        <dd>{{ $vehiculo->propietario_telefono }}</dd>

        <dt>Fecha de registro</dt>
        <dd>{{ $vehiculo->created_at->format('d/m/Y') }}</dd>
    </dl>
@endsection
