<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// [VALIDACION] Registro de una orden de servicio. costo_total y estado NO están en las reglas:
// validated() los descarta aunque alguien los envíe a mano, porque los decide el backend.
class StoreOrdenServicioRequest extends FormRequest
{
    // La ruta ya está protegida por el middleware auth; aquí solo se valida el contenido.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return self::reglas();
    }

    /**
     * Reglas compartidas entre el registro y la edición de una orden.
     */
    public static function reglas(): array
    {
        return [
            'vehiculo_id' => ['required', 'integer', 'exists:vehiculos,id'],
            'tipo_servicio_id' => ['required', 'integer', 'exists:tipos_servicio,id'],
            'fecha_ingreso' => ['required', 'date', 'before_or_equal:today'],
            // [REGLA-NEGOCIO] Regla 4: la entrega no puede ser anterior al ingreso.
            'fecha_entrega' => ['nullable', 'date', 'after_or_equal:fecha_ingreso'],
            'kilometraje_ingreso' => ['required', 'integer', 'min:0', 'max:999999'],
            'descripcion' => ['required', 'string', 'max:1000'],
            'costo_adicional' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
        ];
    }

    public function messages(): array
    {
        return self::mensajes();
    }

    /**
     * Mensajes en español compartidos entre el registro y la edición.
     */
    public static function mensajes(): array
    {
        return [
            'vehiculo_id.required' => 'Selecciona un vehículo.',
            'vehiculo_id.integer' => 'El vehículo seleccionado no es válido.',
            'vehiculo_id.exists' => 'El vehículo seleccionado no existe.',
            'tipo_servicio_id.required' => 'Selecciona un tipo de servicio.',
            'tipo_servicio_id.integer' => 'El tipo de servicio seleccionado no es válido.',
            'tipo_servicio_id.exists' => 'El tipo de servicio seleccionado no existe.',
            'fecha_ingreso.required' => 'La fecha de ingreso es obligatoria.',
            'fecha_ingreso.date' => 'La fecha de ingreso no es una fecha válida.',
            'fecha_ingreso.before_or_equal' => 'La fecha de ingreso no puede ser posterior a hoy.',
            'fecha_entrega.date' => 'La fecha de entrega no es una fecha válida.',
            'fecha_entrega.after_or_equal' => 'La fecha de entrega no puede ser anterior a la fecha de ingreso.',
            'kilometraje_ingreso.required' => 'El kilometraje de ingreso es obligatorio.',
            'kilometraje_ingreso.integer' => 'El kilometraje debe ser un número entero.',
            'kilometraje_ingreso.min' => 'El kilometraje no puede ser negativo.',
            'kilometraje_ingreso.max' => 'El kilometraje no puede superar 999999 km.',
            'descripcion.required' => 'La descripción del trabajo es obligatoria.',
            'descripcion.string' => 'La descripción debe ser texto.',
            'descripcion.max' => 'La descripción no puede tener más de 1000 caracteres.',
            'costo_adicional.numeric' => 'El costo adicional debe ser un número.',
            'costo_adicional.min' => 'El costo adicional no puede ser negativo.',
            'costo_adicional.max' => 'El costo adicional no puede superar los 99999.99.',
        ];
    }

    public function attributes(): array
    {
        return [
            'vehiculo_id' => 'vehículo',
            'tipo_servicio_id' => 'tipo de servicio',
            'fecha_ingreso' => 'fecha de ingreso',
            'fecha_entrega' => 'fecha de entrega',
            'kilometraje_ingreso' => 'kilometraje de ingreso',
            'descripcion' => 'descripción',
            'costo_adicional' => 'costo adicional',
        ];
    }
}
