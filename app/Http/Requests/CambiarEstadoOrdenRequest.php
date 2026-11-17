<?php

namespace App\Http\Requests;

use App\Models\OrdenServicio;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

// [VALIDACION] Cambio de estado de una orden. Se valida contra TRANSICIONES, no contra ESTADOS:
// el único valor aceptado es el siguiente estado de la orden, así no hay saltos ni retrocesos.
class CambiarEstadoOrdenRequest extends FormRequest
{
    public const NOMBRES = ['pendiente' => 'Pendiente', 'en_proceso' => 'En proceso', 'finalizada' => 'Finalizada'];

    // La ruta ya está protegida por el middleware auth; aquí solo se valida la transición.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estado' => ['required', 'string', $this->transicionValida(...)],
        ];
    }

    public function messages(): array
    {
        return [
            'estado.required' => 'Indica el nuevo estado de la orden.',
            'estado.string' => 'El estado no es válido.',
        ];
    }

    // [REGLA-NEGOCIO] Solo se acepta exactamente $orden->siguienteEstado(). Al finalizar se
    // comprueba además la regla 4 sobre la fecha de entrega ya guardada.
    private function transicionValida(string $atributo, mixed $valor, Closure $fallar): void
    {
        /** @var OrdenServicio $orden */
        $orden = $this->route('orden');
        $siguiente = $orden->siguienteEstado();

        if ($siguiente === null) {
            $fallar('Esta orden está finalizada y ya no cambia de estado.');

            return;
        }

        if ($valor !== $siguiente) {
            $fallar('La orden solo puede pasar a '.self::NOMBRES[$siguiente].'.');

            return;
        }

        if ($siguiente === 'finalizada'
            && $orden->fecha_entrega !== null
            && $orden->fecha_entrega->lt($orden->fecha_ingreso)) {
            $fallar('La fecha de entrega no puede ser anterior a la fecha de ingreso.');
        }
    }
}
