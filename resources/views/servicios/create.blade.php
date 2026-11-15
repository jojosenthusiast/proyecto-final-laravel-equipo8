@extends('layouts.app')

@section('titulo', 'Registrar servicio')

@section('contenido')
    <h1 class="titulo-pagina">Registrar servicio</h1>

    <form method="POST" action="{{ route('servicios.store') }}">
        @include('servicios.partials.formulario')
    </form>
@endsection
