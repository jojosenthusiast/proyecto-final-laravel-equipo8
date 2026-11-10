<nav aria-label="Navegación principal">
    <a href="{{ route('inicio') }}" @if (request()->routeIs('inicio')) aria-current="page" @endif>Resumen</a>
    <a href="{{ route('vehiculos.index') }}"
        @if (request()->routeIs('vehiculos.*'))
            aria-current="{{ request()->routeIs('vehiculos.index') ? 'page' : 'location' }}"
        @endif
    >Vehículos</a>
    <a href="{{ route('servicios.index') }}" @if (request()->routeIs('servicios.index')) aria-current="page" @endif>Servicios</a>
</nav>
