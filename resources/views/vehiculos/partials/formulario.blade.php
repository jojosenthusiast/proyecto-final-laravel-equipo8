{{-- Formulario compartido por el registro (create) y la edición (edit). Sigue el contrato de
     marcado: cada campo en .campo, con aria-invalid y su mensaje en .error-campo cuando falla.
     old() devuelve lo que el usuario escribió si la validación falló; si no, el valor guardado
     del vehículo ($vehiculo solo existe en la edición). --}}
@csrf

<div class="campo">
    <label for="placa">Placa</label>
    <input id="placa" name="placa" value="{{ old('placa', $vehiculo->placa ?? '') }}"
           maxlength="7" placeholder="P123456" autocomplete="off" required
           @error('placa') aria-invalid="true" aria-describedby="placa-error" @enderror>
    @error('placa')
        <p class="error-campo" id="placa-error">{{ $message }}</p>
    @enderror
</div>

<div class="campo">
    <label for="marca">Marca</label>
    <input id="marca" name="marca" value="{{ old('marca', $vehiculo->marca ?? '') }}"
           maxlength="50" required
           @error('marca') aria-invalid="true" aria-describedby="marca-error" @enderror>
    @error('marca')
        <p class="error-campo" id="marca-error">{{ $message }}</p>
    @enderror
</div>

<div class="campo">
    <label for="modelo">Modelo</label>
    <input id="modelo" name="modelo" value="{{ old('modelo', $vehiculo->modelo ?? '') }}"
           maxlength="50" required
           @error('modelo') aria-invalid="true" aria-describedby="modelo-error" @enderror>
    @error('modelo')
        <p class="error-campo" id="modelo-error">{{ $message }}</p>
    @enderror
</div>

<div class="campo">
    <label for="anio">Año</label>
    <input type="number" id="anio" name="anio" value="{{ old('anio', $vehiculo->anio ?? '') }}"
           min="1980" max="{{ now()->year + 1 }}" required
           @error('anio') aria-invalid="true" aria-describedby="anio-error" @enderror>
    @error('anio')
        <p class="error-campo" id="anio-error">{{ $message }}</p>
    @enderror
</div>

<div class="campo">
    <label for="color">Color</label>
    <input id="color" name="color" value="{{ old('color', $vehiculo->color ?? '') }}"
           maxlength="30" required
           @error('color') aria-invalid="true" aria-describedby="color-error" @enderror>
    @error('color')
        <p class="error-campo" id="color-error">{{ $message }}</p>
    @enderror
</div>

<div class="campo">
    <label for="kilometraje">Kilometraje</label>
    <input type="number" id="kilometraje" name="kilometraje"
           value="{{ old('kilometraje', $vehiculo->kilometraje ?? '') }}"
           min="0" max="999999" required
           @error('kilometraje') aria-invalid="true" aria-describedby="kilometraje-error" @enderror>
    @error('kilometraje')
        <p class="error-campo" id="kilometraje-error">{{ $message }}</p>
    @enderror
</div>

<div class="campo">
    <label for="propietario_nombre">Nombre del propietario</label>
    <input id="propietario_nombre" name="propietario_nombre"
           value="{{ old('propietario_nombre', $vehiculo->propietario_nombre ?? '') }}"
           maxlength="100" autocomplete="name" required
           @error('propietario_nombre') aria-invalid="true" aria-describedby="propietario-nombre-error" @enderror>
    @error('propietario_nombre')
        <p class="error-campo" id="propietario-nombre-error">{{ $message }}</p>
    @enderror
</div>

<div class="campo">
    <label for="propietario_telefono">Teléfono del propietario</label>
    <input type="tel" id="propietario_telefono" name="propietario_telefono"
           value="{{ old('propietario_telefono', $vehiculo->propietario_telefono ?? '') }}"
           maxlength="9" placeholder="0000-0000" autocomplete="tel" required
           @error('propietario_telefono') aria-invalid="true" aria-describedby="propietario-telefono-error" @enderror>
    @error('propietario_telefono')
        <p class="error-campo" id="propietario-telefono-error">{{ $message }}</p>
    @enderror
</div>

{{-- La foto es opcional. data-vista-previa apunta al <img> donde Milton (M4) muestra la foto
     elegida antes de enviarla. Un archivo no se puede devolver con old(): si la validación
     falla, hay que volver a elegirlo. --}}
<div class="campo">
    <label for="imagen">Foto del vehículo (opcional)</label>
    <img id="vista-imagen" src="{{ $vehiculo->imagen_url ?? asset('img/vehiculo-default.svg') }}"
         alt="Foto del vehículo" width="160">
    <input type="file" id="imagen" name="imagen" accept=".jpg,.jpeg,.png,.webp"
           data-vista-previa="vista-imagen"
           @error('imagen') aria-invalid="true" aria-describedby="imagen-error" @enderror>
    @error('imagen')
        <p class="error-campo" id="imagen-error">{{ $message }}</p>
    @enderror
</div>

<button type="submit" class="boton">{{ $boton ?? 'Registrar vehículo' }}</button>

<a class="boton" href="{{ route('vehiculos.index') }}">Volver al listado</a>
