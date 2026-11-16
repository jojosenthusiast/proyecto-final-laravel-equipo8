@extends('layouts.app')

@section('titulo', 'Vehículos')

@section('contenido')
    <h1 class="titulo-pagina">Vehículos registrados</h1>

    {{-- Registrar es una ruta de escritura: solo se ofrece con sesión iniciada. --}}
    @auth
        <a class="boton" href="{{ route('vehiculos.create') }}">Registrar vehículo</a>
    @endauth

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

        {{-- Al buscar o cambiar la marca se conserva el orden elegido en los encabezados. --}}
        @if ($orden !== 'placa' || $direccion !== 'asc')
            <input type="hidden" name="orden" value="{{ $orden }}">
            <input type="hidden" name="direccion" value="{{ $direccion }}">
        @endif

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
        @php
            // Cada encabezado ordenable enlaza al mismo listado con su columna. Si ya es la columna
            // activa, el enlace invierte la dirección. La búsqueda y la marca se conservan y la
            // paginación vuelve a la página 1.
            $enlaceOrden = fn (string $columna) => route('vehiculos.index', $filtros + [
                'orden' => $columna,
                'direccion' => $orden === $columna && $direccion === 'asc' ? 'desc' : 'asc',
            ]);
            $ariaSort = fn (string $columna) => $orden === $columna
                ? ($direccion === 'asc' ? 'ascending' : 'descending')
                : 'none';
            $flecha = fn (string $columna) => $orden === $columna ? ($direccion === 'asc' ? ' ▲' : ' ▼') : '';
        @endphp

        <table class="tabla">
            <thead>
                <tr>
                    <th>Foto</th>
                    <th aria-sort="{{ $ariaSort('placa') }}"><a href="{{ $enlaceOrden('placa') }}">Placa{{ $flecha('placa') }}</a></th>
                    <th>Marca y modelo</th>
                    <th aria-sort="{{ $ariaSort('anio') }}"><a href="{{ $enlaceOrden('anio') }}">Año{{ $flecha('anio') }}</a></th>
                    <th aria-sort="{{ $ariaSort('kilometraje') }}"><a href="{{ $enlaceOrden('kilometraje') }}">Kilometraje{{ $flecha('kilometraje') }}</a></th>
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
