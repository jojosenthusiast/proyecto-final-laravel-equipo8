@extends('layouts.app')

@section('titulo', 'Registrar orden')

@section('contenido')
    <h1 class="titulo-pagina">Registrar orden de servicio</h1>

    <form method="POST" action="{{ route('ordenes.store') }}">
        @include('ordenes.partials.formulario', ['boton' => 'Registrar orden'])
    </form>
@endsection
