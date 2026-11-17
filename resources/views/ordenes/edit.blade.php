@extends('layouts.app')

@section('titulo', 'Editar orden #'.$orden->id)

@section('contenido')
    <h1 class="titulo-pagina">Editar orden #{{ $orden->id }}</h1>

    <form method="POST" action="{{ route('ordenes.update', $orden) }}">
        @method('PUT')

        @include('ordenes.partials.formulario', ['boton' => 'Guardar cambios'])
    </form>
@endsection
