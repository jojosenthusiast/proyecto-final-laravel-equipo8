<?php

namespace App\Rules;

use App\Models\Vehiculo;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

// [REGLA-NEGOCIO] Regla 3 del caso: el kilometraje de ingreso no puede ser menor que el último
// registrado para el vehículo. Se usa al registrar o editar una orden y al editar un vehículo.
class KilometrajeNoMenorAlUltimo implements ValidationRule
{
    /**
     * @param  int|null  $vehiculoId  Vehículo al que pertenece el kilometraje; si no existe, la
     *                                regla no falla y deja el error a exists:vehiculos,id.
     * @param  int|null  $ignorarOrdenId  Orden que se está editando, para no compararla consigo misma.
     *
     * Uso al registrar una orden:
     * new KilometrajeNoMenorAlUltimo($this->input('vehiculo_id'))
     *
     * Uso al editar una orden:
     * new KilometrajeNoMenorAlUltimo($this->input('vehiculo_id'), $this->route('orden')->id)
     */
    public function __construct(
        private readonly ?int $vehiculoId,
        private readonly ?int $ignorarOrdenId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Los tipos y la existencia del vehículo los validan las reglas del Form Request.
        if (! $this->vehiculoId || ! is_numeric($value)) {
            return;
        }

        $vehiculo = Vehiculo::find($this->vehiculoId);

        if (! $vehiculo) {
            return;
        }

        $ultimo = $vehiculo->ultimoKilometraje($this->ignorarOrdenId);

        if ((int) $value < $ultimo) {
            $fail("El kilometraje no puede ser menor que el último registrado ({$ultimo} km).");
        }
    }
}
