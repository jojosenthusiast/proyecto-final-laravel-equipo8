@extends('layouts.app')

@section('titulo', 'Editar vehículo')

@section('contenido')
    <h1 class="titulo-pagina">
        Editar <span class="placa">{{ $vehiculo->placa }}</span>
    </h1>

    {{-- Los formularios HTML solo envían GET o POST: @method('PUT') agrega el campo _method para
         que Laravel lo trate como PUT y llegue a vehiculos.update. --}}
    <form method="POST" action="{{ route('vehiculos.update', $vehiculo) }}" enctype="multipart/form-data">
        @method('PUT')

        @include('vehiculos.partials.formulario', ['boton' => 'Guardar cambios'])
    </form>
@endsection
