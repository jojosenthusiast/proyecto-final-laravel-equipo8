
@extends('layouts.app')

@section('titulo', 'Resumen')

@section('contenido')
    <h1 class="titulo-pagina">Resumen del taller</h1>

    <p>
        Consulta los vehículos registrados y las órdenes
        de servicio del taller.
    </p>

    <div class="indicadores">

        <div class="indicador">
            <div class="indicador-valor">
                {{ $totalVehiculos }}
            </div>
            <div class="indicador-nombre">
                Vehículos registrados
            </div>
        </div>

        <div class="indicador">
            <div class="indicador-valor">
                {{ $totalOrdenes }}
            </div>
            <div class="indicador-nombre">
                Órdenes de servicio
            </div>
        </div>

        <div class="indicador">
            <div class="indicador-valor">
                {{ $ordenesPorEstado['pendiente'] }}
            </div>
            <div class="indicador-nombre">
                Pendientes
            </div>
        </div>

        <div class="indicador">
            <div class="indicador-valor">
                {{ $ordenesPorEstado['en_proceso'] }}
            </div>
            <div class="indicador-nombre">
                En proceso
            </div>
        </div>

        <div class="indicador">
            <div class="indicador-valor">
                {{ $ordenesPorEstado['finalizada'] }}
            </div>
            <div class="indicador-nombre">
                Finalizadas
            </div>
        </div>

    </div>

    {{-- Servicios más solicitados --}}
    <h2>Servicios más solicitados</h2>

    @if ($tiposServicio->isEmpty())
        <p class="vacio">
            El catálogo de servicios está vacío.
        </p>
    @else
        <table class="tabla">
            <thead>
                <tr>
                    <th>Servicio</th>
                    <th>Órdenes</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($tiposServicio as $tipo)
                    <tr>
                        <td>{{ $tipo->nombre }}</td>
                        <td>{{ $tipo->ordenes_count }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif


    {{-- Últimas órdenes registradas en el taller --}}
    <h2>Últimas órdenes</h2>

    @if ($ultimasOrdenes->isEmpty())
        <p class="vacio">
            Todavía no hay órdenes de servicio registradas.
        </p>
    @else
        <table class="tabla">
            <thead>
                <tr>
                    <th>Placa</th>
                    <th>Servicio</th>
                    <th>Ingreso</th>
                    <th>Estado</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($ultimasOrdenes as $orden)
                    <tr>
                        <td>
                            <a href="{{ url('/vehiculos/' . $orden->vehiculo_id) }}">
                                <span class="placa">
                                    {{ $orden->vehiculo->placa }}
                                </span>
                            </a>
                        </td>

                        <td>
                            {{ $orden->tipoServicio->nombre }}
                        </td>

                        <td>
                            {{ $orden->fecha_ingreso->format('d/m/Y') }}
                        </td>

                        <td>
                            @if ($orden->estado === 'pendiente')
                                <span class="etiqueta etiqueta-pendiente">
                                    Pendiente
                                </span>
                            @elseif ($orden->estado === 'en_proceso')
                                <span class="etiqueta etiqueta-en-proceso">
                                    En proceso
                                </span>
                            @else
                                <span class="etiqueta etiqueta-finalizada">
                                    Finalizada
                                </span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif


    <a class="boton" href="{{ url('/vehiculos') }}">
        Ver vehículos
    </a>
@endsection
