@extends('layouts.app')

@section('titulo', 'Orden #'.$orden->id)

@section('contenido')
    @php
        $nombresEstado = ['pendiente' => 'Pendiente', 'en_proceso' => 'En proceso', 'finalizada' => 'Finalizada'];
    @endphp

    <h1 class="titulo-pagina">Orden #{{ $orden->id }}</h1>

    <section class="ficha">
        <dl class="ficha-datos">
            <dt>Estado</dt>
            <dd>
                <span class="etiqueta etiqueta-{{ str_replace('_', '-', $orden->estado) }}">
                    {{ $nombresEstado[$orden->estado] }}
                </span>
            </dd>

            <dt>Vehículo</dt>
            <dd>
                <a href="{{ route('vehiculos.show', $orden->vehiculo) }}">
                    <span class="placa">{{ $orden->vehiculo->placa }}</span>
                </a>
                {{ $orden->vehiculo->marca }} {{ $orden->vehiculo->modelo }}
            </dd>

            <dt>Servicio</dt>
            <dd>
                <a href="{{ route('servicios.show', $orden->tipoServicio) }}">{{ $orden->tipoServicio->nombre }}</a>
            </dd>

            <dt>Fecha de ingreso</dt>
            <dd>{{ $orden->fecha_ingreso->format('d/m/Y') }}</dd>

            <dt>Fecha de entrega</dt>
            <dd>{{ $orden->fecha_entrega?->format('d/m/Y') ?? 'Sin entregar' }}</dd>

            <dt>Kilometraje de ingreso</dt>
            <dd>{{ number_format($orden->kilometraje_ingreso) }} km</dd>

            <dt>Descripción</dt>
            <dd>{{ $orden->descripcion }}</dd>
        </dl>
    </section>

    <h2>Desglose del costo</h2>

    {{-- [REGLA-NEGOCIO] costo_total = precio base del servicio + costo adicional; lo calcula el backend. --}}
    <table class="tabla">
        <tbody>
            <tr>
                <th scope="row">Precio base</th>
                <td data-label="Precio base">${{ number_format((float) $orden->tipoServicio->precio_base, 2) }}</td>
            </tr>
            <tr>
                <th scope="row">Costo adicional</th>
                <td data-label="Costo adicional">${{ number_format((float) $orden->costo_adicional, 2) }}</td>
            </tr>
            <tr>
                <th scope="row">Costo total</th>
                <td data-label="Costo total"><strong>${{ number_format((float) $orden->costo_total, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    {{-- El botón ofrece solo la siguiente transición; el request la vuelve a validar en el servidor. --}}
    @auth
        @if ($orden->siguienteEstado())
            <form method="POST" action="{{ route('ordenes.estado', $orden) }}">
                @csrf
                @method('PATCH')

                <input type="hidden" name="estado" value="{{ $orden->siguienteEstado() }}">

                <button class="boton" type="submit">Pasar a {{ $nombresEstado[$orden->siguienteEstado()] }}</button>

                @error('estado')
                    <p class="error-campo" id="estado-error">{{ $message }}</p>
                @enderror
            </form>
        @endif
    @endauth

    <p>
        {{-- La política decide en el servidor; el botón solo se oculta para no ofrecer algo prohibido. --}}
        @auth
            @can('update', $orden)
                <a class="boton" href="{{ route('ordenes.edit', $orden) }}">Editar</a>
            @endcan
        @endauth

        <a class="boton" href="{{ route('ordenes.index') }}">Volver al listado</a>
    </p>
@endsection
