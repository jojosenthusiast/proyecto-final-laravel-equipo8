@extends('layouts.app')

@section('titulo', 'Historial por vehículo')

@section('contenido')
    <h1 class="titulo-pagina">Historial de servicios por vehículo</h1>

    <form class="filtro" method="GET" action="{{ route('reportes.historial') }}">
        <div class="campo">
            <label for="vehiculo_id">Vehículo</label>
            <select id="vehiculo_id" name="vehiculo_id"
                @error('vehiculo_id') aria-invalid="true" aria-describedby="vehiculo_id-error" @enderror>
                <option value="">Selecciona un vehículo</option>
                @foreach ($vehiculos as $opcion)
                    <option value="{{ $opcion->id }}" @selected(old('vehiculo_id', $vehiculo?->id) == $opcion->id)>
                        {{ $opcion->placa }} · {{ $opcion->propietario_nombre }}
                    </option>
                @endforeach
            </select>
            @error('vehiculo_id')
                <p class="error-campo" id="vehiculo_id-error">{{ $message }}</p>
            @enderror
        </div>
        <button class="boton" type="submit">Ver historial</button>
    </form>

    @if ($vehiculo)
        <section aria-labelledby="titulo-historial">
            <h2 id="titulo-historial">
                <span class="placa">{{ $vehiculo->placa }}</span>
                · {{ $vehiculo->marca }} {{ $vehiculo->modelo }} · {{ $vehiculo->propietario_nombre }}
            </h2>

            <p>
                Total gastado (órdenes finalizadas): <strong>${{ number_format($totalGastado, 2) }}</strong>
                · Último kilometraje: <strong>{{ number_format($ultimoKilometraje) }} km</strong>
            </p>

            @if ($vehiculo->ordenes->isEmpty())
                <p class="vacio">Este vehículo no tiene órdenes registradas.</p>
            @else
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Ingreso</th>
                            <th>Servicio</th>
                            <th>Kilometraje</th>
                            <th>Estado</th>
                            <th>Entrega</th>
                            <th>Costo</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($vehiculo->ordenes as $orden)
                            <tr>
                                <td data-label="Ingreso">{{ $orden->fecha_ingreso->format('d/m/Y') }}</td>
                                <td data-label="Servicio">{{ $orden->tipoServicio->nombre }}</td>
                                <td data-label="Kilometraje">{{ number_format($orden->kilometraje_ingreso) }} km</td>
                                <td data-label="Estado">
                                    <span class="etiqueta etiqueta-{{ str_replace('_', '-', $orden->estado) }}">
                                        {{ ['pendiente' => 'Pendiente', 'en_proceso' => 'En proceso', 'finalizada' => 'Finalizada'][$orden->estado] }}
                                    </span>
                                </td>
                                <td data-label="Entrega">{{ $orden->fecha_entrega?->format('d/m/Y') ?? 'Sin entregar' }}</td>
                                <td data-label="Costo">${{ number_format((float) $orden->costo_total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    @endif
@endsection
