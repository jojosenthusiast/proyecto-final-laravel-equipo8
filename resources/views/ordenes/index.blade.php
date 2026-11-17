@extends('layouts.app')

@section('titulo', 'Órdenes')

@section('contenido')
    @php
        $nombresEstado = ['pendiente' => 'Pendiente', 'en_proceso' => 'En proceso', 'finalizada' => 'Finalizada'];
        $filtrado = $estado !== null || $tipo !== null;
    @endphp

    <h1 class="titulo-pagina">Órdenes de servicio</h1>

    {{-- Registrar es una ruta de escritura: solo se ofrece con sesión iniciada. --}}
    @auth
        <a class="boton" href="{{ route('ordenes.create') }}">Registrar orden</a>
    @endauth

    <form class="filtro" method="get" action="{{ route('ordenes.index') }}">
        <div class="campo">
            <label for="estado">Estado</label>
            <select id="estado" name="estado">
                <option value="">Todos</option>
                @foreach ($nombresEstado as $valor => $nombre)
                    <option value="{{ $valor }}" @selected($estado === $valor)>{{ $nombre }}</option>
                @endforeach
            </select>
        </div>

        <div class="campo">
            <label for="tipo">Servicio</label>
            <select id="tipo" name="tipo">
                <option value="">Todos</option>
                @foreach ($tiposServicio as $tipoServicio)
                    <option value="{{ $tipoServicio->id }}" @selected($tipo === $tipoServicio->id)>{{ $tipoServicio->nombre }}</option>
                @endforeach
            </select>
        </div>

        <button class="boton" type="submit">Filtrar</button>

        @if ($filtrado)
            <a href="{{ route('ordenes.index') }}">Quitar filtros</a>
        @endif
    </form>

    @if ($ordenes->isEmpty())
        @if ($filtrado)
            <p class="vacio">No hay órdenes que coincidan con los filtros.</p>
        @else
            <p class="vacio">No hay órdenes registradas.</p>
        @endif
    @else
        <table class="tabla">
            <thead>
                <tr>
                    <th>N.º</th>
                    <th>Vehículo</th>
                    <th>Servicio</th>
                    <th>Ingreso</th>
                    <th>Entrega</th>
                    <th>Estado</th>
                    <th>Costo total</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($ordenes as $orden)
                    <tr>
                        <td data-label="N.º">{{ $orden->id }}</td>

                        <td data-label="Vehículo">
                            <span class="placa">{{ $orden->vehiculo->placa }}</span><br>
                            {{ $orden->vehiculo->marca }} {{ $orden->vehiculo->modelo }}
                        </td>

                        <td data-label="Servicio">{{ $orden->tipoServicio->nombre }}</td>

                        <td data-label="Ingreso">{{ $orden->fecha_ingreso->format('d/m/Y') }}</td>

                        <td data-label="Entrega">{{ $orden->fecha_entrega?->format('d/m/Y') ?? 'Sin entregar' }}</td>

                        <td data-label="Estado">
                            <span class="etiqueta etiqueta-{{ str_replace('_', '-', $orden->estado) }}">
                                {{ $nombresEstado[$orden->estado] }}
                            </span>
                        </td>

                        <td data-label="Costo total">${{ number_format($orden->costo_total, 2) }}</td>

                        <td data-label="Acciones">
                            <a class="boton" href="{{ route('ordenes.show', $orden) }}">Ver orden</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <p>
            Mostrando {{ $ordenes->firstItem() }} a {{ $ordenes->lastItem() }}
            de {{ $ordenes->total() }} órdenes
        </p>

        {{ $ordenes->links() }}
    @endif
@endsection
