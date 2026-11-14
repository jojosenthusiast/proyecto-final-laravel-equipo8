@extends('layouts.app')

@section('titulo', 'Vehículos')

@section('contenido')
    <h1 class="titulo-pagina">Vehículos registrados</h1>

    <form class="filtro" method="get" action="{{ route('vehiculos.index') }}">
        <label for="buscar">Buscar</label>
        <input type="search" name="buscar" id="buscar" value="{{ $buscar }}" placeholder="Placa, propietario o modelo" maxlength="100">

        <label for="marca">Marca</label>
        <select name="marca" id="marca">
            <option value="">Todas</option>
            @foreach ($marcas as $marca)
                <option value="{{ $marca }}" @selected(request('marca') === $marca)>{{ $marca }}</option>
            @endforeach
        </select>

        <button class="boton" type="submit">Filtrar</button>

        @if ($filtrado)
            <a href="{{ route('vehiculos.index') }}">Quitar filtros</a>
        @endif
    </form>

    @if ($vehiculos->isEmpty())
        @if ($filtrado)
            <p class="vacio">Ningún vehículo coincide con la búsqueda.</p>
        @else
            <p class="vacio">Todavía no hay vehículos registrados.</p>
        @endif
    @else
        <table class="tabla">
            <thead>
                <tr>
                    <th>Foto</th>
                    <th>Placa</th>
                    <th>Marca y modelo</th>
                    <th>Año</th>
                    <th>Kilometraje</th>
                    <th>Propietario</th>
                    <th>Órdenes</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($vehiculos as $vehiculo)
                    <tr>
                        <td data-label="Foto">
                            <img class="miniatura" src="{{ $vehiculo->imagen_url }}" alt="Foto del vehículo {{ $vehiculo->placa }}">
                        </td>

                        <td data-label="Placa"><span class="placa">{{ $vehiculo->placa }}</span></td>

                        <td data-label="Marca y modelo">{{ $vehiculo->marca }} {{ $vehiculo->modelo }}</td>

                        <td data-label="Año">{{ $vehiculo->anio }}</td>

                        <td data-label="Kilometraje">{{ number_format($vehiculo->kilometraje) }} km</td>

                        <td data-label="Propietario">
                            {{ $vehiculo->propietario_nombre }}<br>
                            {{ $vehiculo->propietario_telefono }}
                        </td>

                        <td data-label="Órdenes">{{ $vehiculo->ordenes_count }}</td>

                        <td data-label="Acciones">
                            <a class="boton" href="{{ route('vehiculos.show', $vehiculo) }}">Ver ficha</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <p>
            Mostrando {{ $vehiculos->firstItem() }} a {{ $vehiculos->lastItem() }}
            de {{ $vehiculos->total() }} vehículos
        </p>

        {{ $vehiculos->links() }}
    @endif
@endsection
