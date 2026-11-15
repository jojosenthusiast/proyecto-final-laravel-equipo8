@csrf

<div class="campo">
    <label for="nombre">Nombre</label>

    <input
        id="nombre"
        name="nombre"
        value="{{ old('nombre', $servicio->nombre ?? '') }}"
        maxlength="60"
        @error('nombre') aria-invalid="true" aria-describedby="nombre-error" @enderror
    >

    @error('nombre')
        <p class="error-campo" id="nombre-error">{{ $message }}</p>
    @enderror
</div>

<div class="campo">
    <label for="precio_base">Precio base</label>

    <input
        type="number"
        id="precio_base"
        name="precio_base"
        value="{{ old('precio_base', $servicio->precio_base ?? '') }}"
        min="0.01"
        max="99999.99"
        step="0.01"
        @error('precio_base') aria-invalid="true" aria-describedby="precio-base-error" @enderror
    >

    @error('precio_base')
        <p class="error-campo" id="precio-base-error">{{ $message }}</p>
    @enderror
</div>

<div class="campo">
    <label for="descripcion">Descripción</label>

    <textarea
        id="descripcion"
        name="descripcion"
        maxlength="255"
        @error('descripcion') aria-invalid="true" aria-describedby="descripcion-error" @enderror
    >{{ old('descripcion', $servicio->descripcion ?? '') }}</textarea>

    @error('descripcion')
        <p class="error-campo" id="descripcion-error">{{ $message }}</p>
    @enderror
</div>

<button type="submit" class="boton">
    {{ $boton ?? 'Guardar servicio' }}
</button>

<a class="boton" href="{{ route('servicios.index') }}">
    Volver al catálogo
</a>
