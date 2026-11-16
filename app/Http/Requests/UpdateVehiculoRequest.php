<?php

namespace App\Http\Requests;

use App\Rules\KilometrajeNoMenorAlUltimo;
use Illuminate\Validation\Rules\Unique;

// [VALIDACION] Datos del vehículo al editarlo. Hereda las reglas, los mensajes y la limpieza de la
// placa del registro; cambia la regla de placa única y agrega la regla 3 al kilometraje.
class UpdateVehiculoRequest extends StoreVehiculoRequest
{
    public function rules(): array
    {
        $reglas = parent::rules();

        // [REGLA-NEGOCIO] Regla 3 en la edición: el kilometraje no puede bajar del último
        // registrado, que es el mayor entre el del vehículo y el de sus órdenes. Si una orden
        // registró 85 000 km, el vehículo no se puede editar a 80 000. Se suma a required,
        // integer, min:0 y max:999999 del registro; la regla es de Fernando (F2).
        $reglas['kilometraje'][] = new KilometrajeNoMenorAlUltimo($this->route('vehiculo')?->id);

        return $reglas;
    }

    // [REGLA-NEGOCIO] Regla 1 en la edición: la placa sigue siendo única, pero se ignora el propio
    // vehículo. Sin ignore(), guardar el vehículo sin cambiar su placa daría «placa repetida».
    protected function reglaPlacaUnica(): Unique
    {
        return parent::reglaPlacaUnica()->ignore($this->route('vehiculo'));
    }
}
