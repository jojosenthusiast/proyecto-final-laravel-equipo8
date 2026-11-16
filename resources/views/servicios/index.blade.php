
@extends('layouts.app')

@section('titulo', 'Servicios')

@section('contenido')
    <h1 class="titulo-pagina">Catálogo de servicios</h1>

    <form class="filtro" method="get" action="{{ route('servicios.index') }}">
        <div class="campo">
            <label for="busqueda">Buscar</label>
            <input id="busqueda" name="busqueda" value="{{ request('busqueda') }}">
        </div>

        <div class="campo">
            <label for="orden">Ordenar por</label>
            <select id="orden" name="orden">
                <option value="nombre" @selected($orden === 'nombre')>Nombre</option>
                <option value="precio_base" @selected($orden === 'precio_base')>Precio base</option>
                <option value="ordenes_count" @selected($orden === 'ordenes_count')>Órdenes</option>
            </select>
        </div>

        <div class="campo">
            <label for="direccion">Dirección</label>
            <select id="direccion" name="direccion">
                <option value="asc" @selected($direccion === 'asc')>Ascendente</option>
                <option value="desc" @selected($direccion === 'desc')>Descendente</option>
            </select>
        </div>

        <button class="boton" type="submit">Buscar</button>
    </form>

    <p>
        <a class="boton" href="{{ route('servicios.create') }}">Registrar servicio</a>
    </p>

    @if ($tiposServicio->isEmpty())
        <p class="vacio">
            @if (request('busqueda'))
                No hay servicios que coincidan con «{{ request('busqueda') }}».
            @else
                El catálogo de servicios está vacío.
            @endif
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

        {{ $tiposServicio->links() }}
    @endif
@endsection
