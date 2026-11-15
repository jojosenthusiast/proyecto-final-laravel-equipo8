@extends('layouts.app')

@section('titulo', 'Editar servicio')

@section('contenido')
    <h1 class="titulo-pagina">Editar servicio</h1>

    <form method="POST" action="{{ route('servicios.update', $servicio) }}">
        @method('PUT')

        @include('servicios.partials.formulario', ['boton' => 'Guardar cambios'])
    </form>
@endsection
