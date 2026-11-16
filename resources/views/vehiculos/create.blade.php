@extends('layouts.app')

@section('titulo', 'Registrar vehículo')

@section('contenido')
    <h1 class="titulo-pagina">Registrar vehículo</h1>

    {{-- multipart/form-data es obligatorio para que el navegador envíe el archivo de la foto. --}}
    <form method="POST" action="{{ route('vehiculos.store') }}" enctype="multipart/form-data">
        @include('vehiculos.partials.formulario')
    </form>
@endsection
