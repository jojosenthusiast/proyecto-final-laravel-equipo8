@extends('layouts.app')

@section('titulo', 'Reporte')

@section('contenido')
    <h1 class="titulo-pagina">Reporte del taller</h1>

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
