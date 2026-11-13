@extends('layouts.app')

@section('titulo', 'Reporte')

@section('contenido')
    <h1 class="titulo-pagina">Reporte del taller</h1>

    <p><a class="boton" href="{{ route('reportes.historial') }}">Historial por vehículo</a></p>

    <form class="filtro" method="GET" action="{{ route('reportes.index') }}">
        <div class="campo">
            <label for="desde">Desde</label>
            <input type="date" id="desde" name="desde" value="{{ old('desde', $desde) }}"
                @error('desde') aria-invalid="true" aria-describedby="desde-error" @enderror>
            @error('desde')
                <p class="error-campo" id="desde-error">{{ $message }}</p>
            @enderror
        </div>
        <div class="campo">
            <label for="hasta">Hasta</label>
            <input type="date" id="hasta" name="hasta" value="{{ old('hasta', $hasta) }}"
                @error('hasta') aria-invalid="true" aria-describedby="hasta-error" @enderror>
            @error('hasta')
                <p class="error-campo" id="hasta-error">{{ $message }}</p>
            @enderror
        </div>
        <button class="boton" type="submit">Filtrar</button>
        <a class="boton" href="{{ route('reportes.exportar', array_filter(['desde' => $desde, 'hasta' => $hasta])) }}">Exportar ingresos a CSV</a>
    </form>

    <section aria-labelledby="titulo-pendientes">
        <h2 id="titulo-pendientes">Órdenes pendientes</h2>

        @if ($pendientes->isEmpty())
            <p class="vacio">No hay órdenes pendientes.</p>
        @else
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Placa</th>
                        <th>Servicio</th>
                        <th>Ingreso</th>
                        <th>Días en el taller</th>
                        <th>Estado</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($pendientes as $orden)
                        <tr>
                            <td data-label="Placa"><span class="placa">{{ $orden->vehiculo->placa }}</span></td>
                            <td data-label="Servicio">{{ $orden->tipoServicio->nombre }}</td>
                            <td data-label="Ingreso">{{ $orden->fecha_ingreso->format('d/m/Y') }}</td>
                            <td data-label="Días en el taller">{{ $orden->dias_en_taller }}</td>
                            <td data-label="Estado">
                                <span class="etiqueta etiqueta-{{ str_replace('_', '-', $orden->estado) }}">
                                    {{ $orden->estado === 'en_proceso' ? 'En proceso' : 'Pendiente' }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>

    <section aria-labelledby="titulo-ingresos">
        <h2 id="titulo-ingresos">Ingresos por tipo de servicio</h2>

        @if ($ingresos->isEmpty())
            <p class="vacio">El catálogo de servicios está vacío.</p>
        @else
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Servicio</th>
                        <th>Órdenes finalizadas</th>
                        <th>Ingresos</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($ingresos as $tipo)
                        <tr>
                            <td data-label="Servicio">{{ $tipo->nombre }}</td>
                            <td data-label="Órdenes finalizadas">{{ $tipo->ordenes_finalizadas }}</td>
                            <td data-label="Ingresos">${{ number_format((float) $tipo->ingresos, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>

                <tfoot>
                    <tr>
                        <th scope="row" colspan="2">Total</th>
                        <td data-label="Total">${{ number_format($totalGeneral, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        @endif
    </section>
@endsection
