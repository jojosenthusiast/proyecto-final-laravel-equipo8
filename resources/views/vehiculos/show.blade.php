@extends('layouts.app')

@section('titulo', 'Ficha')

@section('contenido')
    <h1 class="titulo-pagina">
        <span class="placa">{{ $vehiculo->placa }}</span>
        {{ $vehiculo->marca }} {{ $vehiculo->modelo }}
    </h1>

    {{-- Acciones del vehículo. Registrar una orden lleva el vehículo en ?vehiculo=ID para que
         el formulario de órdenes (Leo) lo traiga preseleccionado. --}}
    <div class="filtro">
        <a class="boton" href="{{ route('ordenes.create', ['vehiculo' => $vehiculo]) }}">Registrar orden</a>

        @auth
            <a class="boton" href="{{ route('vehiculos.edit', $vehiculo) }}">Editar</a>

            <form method="POST" action="{{ route('vehiculos.destroy', $vehiculo) }}"
                  data-confirmar="¿Eliminar el vehículo {{ $vehiculo->placa }}?"
                  onsubmit="return confirm(this.dataset.confirmar)">
                @csrf
                @method('DELETE')
                <button class="boton boton-peligro">Eliminar</button>
            </form>
        @endauth
    </div>

    <section class="ficha">
        <div class="ficha-imagen">
            <img src="{{ $vehiculo->imagen_url }}" alt="Foto del vehículo {{ $vehiculo->placa }}">
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

            <dt>Kilometraje registrado</dt>
            <dd>{{ number_format($vehiculo->kilometraje) }} km</dd>

            <dt>Propietario</dt>
            <dd>{{ $vehiculo->propietario_nombre }}</dd>

            <dt>Teléfono</dt>
            <dd>{{ $vehiculo->propietario_telefono }}</dd>

            <dt>Fecha de registro</dt>
            <dd>{{ $vehiculo->created_at->format('d/m/Y') }}</dd>
        </dl>
    </section>

    <div class="indicadores">
        <div class="indicador">
            <div class="indicador-valor">{{ $vehiculo->ordenes->count() }}</div>
            <div class="indicador-nombre">Órdenes de servicio</div>
        </div>

        <div class="indicador">
            <div class="indicador-valor">${{ number_format($totalGastado, 2) }}</div>
            <div class="indicador-nombre">Total gastado (órdenes finalizadas)</div>
        </div>

        <div class="indicador">
            <div class="indicador-valor">{{ number_format($ultimoKilometraje) }} km</div>
            <div class="indicador-nombre">Último kilometraje</div>
        </div>
    </div>

    <h2>Historial de servicio</h2>

    @if ($vehiculo->ordenes->isEmpty())
        <p class="vacio">Este vehículo no ha tenido órdenes de servicio.</p>
    @else
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
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($vehiculo->ordenes as $orden)
                    <tr>
                        <td data-label="Ingreso">{{ $orden->fecha_ingreso->format('d/m/Y') }}</td>

                        <td data-label="Servicio">{{ $orden->tipoServicio->nombre }}</td>

                        <td data-label="Kilometraje">{{ number_format($orden->kilometraje_ingreso) }} km</td>

                        <td data-label="Descripción">{{ $orden->descripcion }}</td>

                        <td data-label="Estado">
                            <span class="etiqueta etiqueta-{{ str_replace('_', '-', $orden->estado) }}">
                                {{ ucfirst(str_replace('_', ' ', $orden->estado)) }}
                            </span>
                        </td>

                        <td data-label="Entrega">
                            @if ($orden->fecha_entrega)
                                {{ $orden->fecha_entrega->format('d/m/Y') }}
                            @else
                                Sin entregar
                            @endif
                        </td>

                        <td data-label="Costo">${{ number_format($orden->costo_total, 2) }}</td>

                        <td data-label="Acciones">
                            <a class="boton" href="{{ route('ordenes.show', $orden) }}">Ver orden</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <a class="boton" href="{{ route('vehiculos.index') }}">Volver al listado</a>
@endsection
