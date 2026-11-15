
@extends('layouts.app')

@section('titulo', 'Servicios')

@section('contenido')
    <h1 class="titulo-pagina">Catálogo de servicios</h1>

    <p>
        <a class="boton" href="{{ route('servicios.create') }}">Registrar servicio</a>
    </p>

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
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($tiposServicio as $tipo)
                    <tr>
                        <td data-label="Servicio">{{ $tipo->nombre }}</td>

                        <td data-label="Descripción">{{ $tipo->descripcion }}</td>

                        <td data-label="Precio base">
                            ${{ number_format($tipo->precio_base, 2) }}
                        </td>

                        <td data-label="Órdenes">{{ $tipo->ordenes_count }}</td>

                        <td data-label="Acciones">
                            <a class="boton" href="{{ route('servicios.edit', $tipo) }}">Editar</a>

                            {{-- [SEGURIDAD] La eliminación va por DELETE y pide confirmación. --}}
                            <form
                                method="POST"
                                action="{{ route('servicios.destroy', $tipo) }}"
                                data-confirmar="¿Eliminar el servicio {{ $tipo->nombre }}?"
                                onsubmit="return confirm(this.dataset.confirmar)"
                            >
                                @csrf
                                @method('DELETE')

                                <button class="boton boton-peligro" type="submit">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
