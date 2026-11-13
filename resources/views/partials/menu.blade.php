<nav aria-label="Navegación principal">
    <a href="{{ route('inicio') }}" @if (request()->routeIs('inicio')) aria-current="page" @endif>Resumen</a>
    <a href="{{ route('vehiculos.index') }}"
        @if (request()->routeIs('vehiculos.*'))
            aria-current="{{ request()->routeIs('vehiculos.index') ? 'page' : 'location' }}"
        @endif
    >Vehículos</a>
    @if (Route::has('ordenes.index'))
        <a href="{{ route('ordenes.index') }}"
            @if (request()->routeIs('ordenes.*'))
                aria-current="{{ request()->routeIs('ordenes.index') ? 'page' : 'location' }}"
            @endif
        >Órdenes</a>
    @endif
    @if (Route::has('servicios.index'))
        <a href="{{ route('servicios.index') }}"
            @if (request()->routeIs('servicios.*'))
                aria-current="{{ request()->routeIs('servicios.index') ? 'page' : 'location' }}"
            @endif
        >Servicios</a>
    @endif
    @if (Route::has('reportes.index'))
        <a href="{{ route('reportes.index') }}"
            @if (request()->routeIs('reportes.*'))
                aria-current="{{ request()->routeIs('reportes.index') ? 'page' : 'location' }}"
            @endif
        >Reportes</a>
    @endif
</nav>
