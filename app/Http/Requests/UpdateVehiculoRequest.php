<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rules\Unique;

// [VALIDACION] Datos del vehículo al editarlo. Hereda las reglas, los mensajes y la limpieza de la
// placa del registro; solo cambia la regla de placa única.
class UpdateVehiculoRequest extends StoreVehiculoRequest
{
    // [REGLA-NEGOCIO] Regla 1 en la edición: la placa sigue siendo única, pero se ignora el propio
    // vehículo. Sin ignore(), guardar el vehículo sin cambiar su placa daría «placa repetida».
    protected function reglaPlacaUnica(): Unique
    {
        return parent::reglaPlacaUnica()->ignore($this->route('vehiculo'));
    }
}
