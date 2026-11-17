@if (session('exito'))
    <div class="alerta alerta-exito">
        <span role="status">{{ session('exito') }}</span>
        <button class="cerrar-alerta" type="button" data-cerrar-alerta hidden>Cerrar aviso</button>
    </div>
@endif

@if (session('error'))
    <div class="alerta alerta-error">
        <span role="alert">{{ session('error') }}</span>
        <button class="cerrar-alerta" type="button" data-cerrar-alerta hidden>Cerrar aviso</button>
    </div>
@endif
