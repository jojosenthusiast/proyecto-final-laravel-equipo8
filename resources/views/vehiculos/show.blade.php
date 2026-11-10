@extends('layouts.app')

@section('titulo', 'Ficha')

@section('contenido')
    <h1 class="titulo-pagina">
        <span class="placa">{{ $vehiculo->placa }}</span>
    </h1>

    <section class="ficha">
        <div class="ficha-imagen">
            <img src="{{ $vehiculo->imagen_url }}" alt="Foto de {{ $vehiculo->placa }}">
        </div>

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
    </section>

    <p>Órdenes de servicio: <strong>{{ $vehiculo->ordenes->count() }}</strong></p>
    <p>
        Total facturado:
        <strong>${{ number_format($vehiculo->ordenes->where('estado', 'finalizada')->sum('costo_total'), 2) }}</strong>
    </p>

    <h2>Historial de servicio</h2>

    <table class="tabla">
        <thead>
            <tr>
                <th>Ingreso</th>
                <th>Servicio</th>
                <th>Kilometraje</th>
                <th>Descripción</th>
                <th>Estado</th>
                <th>Entrega</th>
                <th>Costo</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($vehiculo->ordenes as $orden)
                <tr>
                    <td>{{ $orden->fecha_ingreso->format('d/m/Y') }}</td>

                    <td>{{ $orden->tipoServicio->nombre }}</td>

                    <td>{{ number_format($orden->kilometraje_ingreso) }} km</td>

                    <td>{{ $orden->descripcion }}</td>

                    <td>
                        <span class="etiqueta etiqueta-{{ str_replace('_', '-', $orden->estado) }}">
                            {{ ucfirst(str_replace('_', ' ', $orden->estado)) }}
                        </span>
                    </td>

                    <td>
                        @if ($orden->fecha_entrega)
                            {{ $orden->fecha_entrega->format('d/m/Y') }}
                        @else
                            Sin entregar
                        @endif
                    </td>

                    <td>${{ number_format($orden->costo_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
