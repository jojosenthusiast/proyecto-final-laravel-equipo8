@extends('layouts.app')

@section('titulo', 'Vehículos')

@section('contenido')
    <h1 class="titulo-pagina">Vehículos registrados</h1>

    @if ($vehiculos->isEmpty())
        <p class="vacio">Todavía no hay vehículos registrados.</p>
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
                </tr>
            </thead>

            <tbody>
                @foreach ($vehiculos as $vehiculo)
                    <tr>
                        <td>
                            <img class="miniatura" src="{{ $vehiculo->imagen_url }}" alt="Foto del vehículo {{ $vehiculo->placa }}">
                        </td>

                        <td><span class="placa">{{ $vehiculo->placa }}</span></td>

                        <td>{{ $vehiculo->marca }} {{ $vehiculo->modelo }}</td>

                        <td>{{ $vehiculo->anio }}</td>

                        <td>{{ number_format($vehiculo->kilometraje) }} km</td>

                        <td>
                            {{ $vehiculo->propietario_nombre }}<br>
                            {{ $vehiculo->propietario_telefono }}
                        </td>

                        <td>{{ $vehiculo->ordenes_count }}</td>
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
