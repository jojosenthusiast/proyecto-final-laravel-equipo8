@extends('layouts.app')

@section('titulo', 'Ficha del servicio')

@section('contenido')
    <h1 class="titulo-pagina">{{ $servicio->nombre }}</h1>

    <section class="ficha">
        <dl class="ficha-datos">
            <dt>Descripción</dt>
            <dd>{{ $servicio->descripcion ?: 'Sin descripción' }}</dd>

            <dt>Precio base</dt>
            <dd>${{ number_format((float) $servicio->precio_base, 2) }}</dd>

            <dt>Órdenes</dt>
            <dd>{{ $servicio->ordenes_count }}</dd>

            <dt>Ingresos por órdenes finalizadas</dt>
            <dd>${{ number_format((float) $servicio->ingresos, 2) }}</dd>
        </dl>
    </section>

    <h2>Órdenes recientes</h2>

    @if ($ordenes->isEmpty())
        <p class="vacio">Este servicio no tiene órdenes registradas.</p>
    @else
        <table class="tabla">
            <thead>
                <tr>
                    <th>Orden</th>
                    <th>Ingreso</th>
                    <th>Vehículo</th>
                    <th>Estado</th>
                    <th>Costo</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($ordenes as $orden)
                    <tr>
                        <td data-label="Orden">
                            <a href="{{ route('ordenes.show', $orden) }}">#{{ $orden->id }}</a>
                        </td>

                        <td data-label="Ingreso">{{ $orden->fecha_ingreso->format('d/m/Y') }}</td>

                        <td data-label="Vehículo">
                            <a href="{{ route('vehiculos.show', $orden->vehiculo) }}">
                                <span class="placa">{{ $orden->vehiculo->placa }}</span>
                            </a>
                        </td>

                        <td data-label="Estado">
                            <span class="etiqueta etiqueta-{{ str_replace('_', '-', $orden->estado) }}">
                                {{ ['pendiente' => 'Pendiente', 'en_proceso' => 'En proceso', 'finalizada' => 'Finalizada'][$orden->estado] }}
                            </span>
                        </td>

                        <td data-label="Costo">${{ number_format((float) $orden->costo_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <p>
        <a class="boton" href="{{ route('servicios.index') }}">Volver al catálogo</a>
    </p>
@endsection
