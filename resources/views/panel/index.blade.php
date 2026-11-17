@extends('layouts.app')

@section('titulo', 'Resumen')

@section('contenido')
    <h1 class="titulo-pagina">Resumen del taller</h1>

    <div class="acciones-panel">
        @auth
            @if (Route::has('vehiculos.create'))
                <a class="boton" href="{{ route('vehiculos.create') }}">Registrar vehículo</a>
            @endif

            @if (Route::has('ordenes.create'))
                <a class="boton" href="{{ route('ordenes.create') }}">Registrar orden</a>
            @endif
        @endauth

        @if (Route::has('reportes.index'))
            <a class="boton boton-secundario" href="{{ route('reportes.index') }}">Ver reporte</a>
        @endif
    </div>

    <div class="indicadores indicadores-panel">
        <div class="indicador">
            <span class="indicador-valor">{{ $totalVehiculos }}</span>
            <span class="indicador-nombre">Vehículos registrados</span>
        </div>
        <div class="indicador">
            <span class="indicador-valor">{{ $totalOrdenes }}</span>
            <span class="indicador-nombre">Órdenes de servicio</span>
        </div>
        <div class="indicador">
            <span class="indicador-valor">${{ number_format($ingresosMes, 2) }}</span>
            <span class="indicador-nombre">Ingresos del mes</span>
        </div>
    </div>

    <h2>Órdenes por estado</h2>

    <div class="taller-estados">
        @foreach (['pendiente' => 'Pendientes', 'en_proceso' => 'En proceso', 'finalizada' => 'Finalizadas'] as $estado => $nombre)
            <div class="indicador">
                <p><strong>{{ $ordenesPorEstado[$estado] }}</strong> {{ $nombre }} · {{ $porcentajes[$estado] }} %</p>
                <progress aria-label="{{ $nombre }}" max="100" value="{{ $porcentajes[$estado] }}"></progress>
            </div>
        @endforeach
    </div>

    @if ($ordenAntigua)
        <p class="orden-antigua">
            <strong>Orden abierta más antigua</strong>
            <a href="{{ route('ordenes.show', $ordenAntigua) }}">#{{ $ordenAntigua->id }} · {{ $ordenAntigua->vehiculo->placa }}</a>
            · Ingreso {{ $ordenAntigua->fecha_ingreso->format('d/m/Y') }}
        </p>
    @else
        <p class="vacio">No hay órdenes abiertas.</p>
    @endif

    <h2>Servicios más solicitados</h2>

    @if ($tiposServicio->isEmpty())
        <p class="vacio">El catálogo de servicios está vacío.</p>
    @else
        <table class="tabla">
            <thead><tr><th scope="col">Servicio</th><th scope="col">Órdenes</th></tr></thead>
            <tbody>
                @foreach ($tiposServicio as $tipo)
                    <tr>
                        <td data-label="Servicio">{{ $tipo->nombre }}</td>
                        <td data-label="Órdenes">{{ $tipo->ordenes_count }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>Últimas órdenes</h2>

    @if ($ultimasOrdenes->isEmpty())
        <p class="vacio">Todavía no hay órdenes de servicio registradas.</p>
    @else
        <table class="tabla">
            <thead><tr><th scope="col">Placa</th><th scope="col">Servicio</th><th scope="col">Ingreso</th><th scope="col">Estado</th></tr></thead>
            <tbody>
                @foreach ($ultimasOrdenes as $orden)
                    <tr>
                        <td data-label="Placa">
                            <a href="{{ route('vehiculos.show', $orden->vehiculo_id) }}"><span class="placa">{{ $orden->placa }}</span></a>
                        </td>
                        <td data-label="Servicio">{{ $orden->servicio }}</td>
                        <td data-label="Ingreso">{{ \Illuminate\Support\Carbon::parse($orden->fecha_ingreso)->format('d/m/Y') }}</td>
                        <td data-label="Estado">
                            <span class="etiqueta etiqueta-{{ str_replace('_', '-', $orden->estado) }}">
                                {{ ['pendiente' => 'Pendiente', 'en_proceso' => 'En proceso', 'finalizada' => 'Finalizada'][$orden->estado] }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <a class="boton" href="{{ route('vehiculos.index') }}">Ver vehículos</a>
@endsection
