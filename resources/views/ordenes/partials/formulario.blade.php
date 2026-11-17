{{-- Formulario compartido por registrar y editar. El costo total y el estado no son campos:
     los decide el backend. --}}
@csrf

<div class="campo">
    <label for="vehiculo_id">Vehículo</label>

    <select
        id="vehiculo_id"
        name="vehiculo_id"
        @error('vehiculo_id') aria-invalid="true" aria-describedby="vehiculo-id-error" @enderror
    >
        <option value="">Selecciona un vehículo</option>
        @foreach ($vehiculos as $vehiculo)
            <option value="{{ $vehiculo->id }}" @selected(old('vehiculo_id', $orden->vehiculo_id) == $vehiculo->id)>{{ $vehiculo->placa }} · {{ $vehiculo->marca }} {{ $vehiculo->modelo }}</option>
        @endforeach
    </select>

    @error('vehiculo_id')
        <p class="error-campo" id="vehiculo-id-error">{{ $message }}</p>
    @enderror
</div>

<div class="campo">
    <label for="tipo_servicio_id">Tipo de servicio</label>

    <select
        id="tipo_servicio_id"
        name="tipo_servicio_id"
        @error('tipo_servicio_id') aria-invalid="true" aria-describedby="tipo-servicio-id-error" @enderror
    >
        <option value="">Selecciona un tipo de servicio</option>
        @foreach ($tiposServicio as $tipoServicio)
            <option value="{{ $tipoServicio->id }}" @selected(old('tipo_servicio_id', $orden->tipo_servicio_id) == $tipoServicio->id)>{{ $tipoServicio->nombre }} (${{ number_format((float) $tipoServicio->precio_base, 2) }})</option>
        @endforeach
    </select>

    @error('tipo_servicio_id')
        <p class="error-campo" id="tipo-servicio-id-error">{{ $message }}</p>
    @enderror
</div>

<div class="campo">
    <label for="fecha_ingreso">Fecha de ingreso</label>

    <input
        type="date"
        id="fecha_ingreso"
        name="fecha_ingreso"
        value="{{ old('fecha_ingreso', $orden->fecha_ingreso?->toDateString()) }}"
        max="{{ today()->toDateString() }}"
        @error('fecha_ingreso') aria-invalid="true" aria-describedby="fecha-ingreso-error" @enderror
    >

    @error('fecha_ingreso')
        <p class="error-campo" id="fecha-ingreso-error">{{ $message }}</p>
    @enderror
</div>

<div class="campo">
    <label for="fecha_entrega">Fecha de entrega (opcional)</label>

    <input
        type="date"
        id="fecha_entrega"
        name="fecha_entrega"
        value="{{ old('fecha_entrega', $orden->fecha_entrega?->toDateString()) }}"
        @error('fecha_entrega') aria-invalid="true" aria-describedby="fecha-entrega-error" @enderror
    >

    @error('fecha_entrega')
        <p class="error-campo" id="fecha-entrega-error">{{ $message }}</p>
    @enderror
</div>

<div class="campo">
    <label for="kilometraje_ingreso">Kilometraje de ingreso</label>

    <input
        type="number"
        id="kilometraje_ingreso"
        name="kilometraje_ingreso"
        value="{{ old('kilometraje_ingreso', $orden->kilometraje_ingreso) }}"
        min="0"
        max="999999"
        step="1"
        @error('kilometraje_ingreso') aria-invalid="true" aria-describedby="kilometraje-ingreso-error" @enderror
    >

    @error('kilometraje_ingreso')
        <p class="error-campo" id="kilometraje-ingreso-error">{{ $message }}</p>
    @enderror
</div>

<div class="campo">
    <label for="costo_adicional">Costo adicional (opcional)</label>

    <input
        type="number"
        id="costo_adicional"
        name="costo_adicional"
        value="{{ old('costo_adicional', $orden->costo_adicional) }}"
        min="0"
        max="99999.99"
        step="0.01"
        @error('costo_adicional') aria-invalid="true" aria-describedby="costo-adicional-error" @enderror
    >

    @error('costo_adicional')
        <p class="error-campo" id="costo-adicional-error">{{ $message }}</p>
    @enderror
</div>

<div class="campo">
    <label for="descripcion">Descripción del trabajo</label>

    <textarea
        id="descripcion"
        name="descripcion"
        maxlength="1000"
        @error('descripcion') aria-invalid="true" aria-describedby="descripcion-error" @enderror
    >{{ old('descripcion', $orden->descripcion) }}</textarea>

    @error('descripcion')
        <p class="error-campo" id="descripcion-error">{{ $message }}</p>
    @enderror
</div>

<p>El costo total lo calcula el sistema: precio base del servicio más el costo adicional.</p>

<button type="submit" class="boton">{{ $boton ?? 'Guardar orden' }}</button>

<a class="boton" href="{{ route('ordenes.index') }}">Volver al listado</a>
