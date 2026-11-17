<?php

namespace App\Http\Requests;

use App\Rules\KilometrajeNoMenorAlUltimo;
use Illuminate\Support\Facades\Gate;

// [VALIDACION] Edición de una orden: mismas reglas y mensajes que el registro. La regla 3 ignora
// la propia orden para no compararla consigo misma.
class UpdateOrdenServicioRequest extends StoreOrdenServicioRequest
{
    // [SEGURIDAD] Regla 5 antes de validar: Laravel corre authorize() primero, así que un PUT a
    // mano sobre una orden finalizada responde 403 con el mensaje de la política, no 302 con
    // errores de validación.
    public function authorize(): bool
    {
        // Lanza AuthorizationException (403) con el mensaje de OrdenServicioPolicy::update().
        Gate::authorize('update', $this->route('orden'));

        return true;
    }

    public function rules(): array
    {
        $reglas = self::reglas();

        // [REGLA-NEGOCIO] Regla 3 al editar: se compara contra las demás órdenes del vehículo.
        $reglas['kilometraje_ingreso'][] = new KilometrajeNoMenorAlUltimo(
            $this->vehiculoId(),
            $this->route('orden')->id
        );

        return $reglas;
    }
}
