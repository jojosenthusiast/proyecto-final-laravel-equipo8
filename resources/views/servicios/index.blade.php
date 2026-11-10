
@extends('layouts.app')

@section('titulo', 'Servicios')

@section('contenido')
    <h1 class="titulo-pagina">Catálogo de servicios</h1>

    @if ($tiposServicio->isEmpty())
        <p class="vacio">
            El catálogo de servicios está vacío.
        </p>
    @else
        <table class="tabla">
            <thead>
                <tr>
                    <th>Servicio</th>
                    <th>Descripción</th>
                    <th>Precio base</th>
                    <th>Órdenes</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($tiposServicio as $tipo)
                    <tr>
                        <td>{{ $tipo->nombre }}</td>

                        <td>{{ $tipo->descripcion }}</td>

                        <td>
                            ${{ number_format($tipo->precio_base, 2) }}
                        </td>

                        <td>{{ $tipo->ordenes_count }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
