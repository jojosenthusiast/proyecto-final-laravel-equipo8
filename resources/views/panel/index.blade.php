
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

    <a class="boton" href="{{ url('/vehiculos') }}">
        Ver vehículos
    </a>
@endsection
