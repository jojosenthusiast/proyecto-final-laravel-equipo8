<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// [VALIDACION] Vehículo elegido para el historial del reporte. Es opcional: sin él se muestra
// solo el selector. Si no es válido, se vuelve al formulario con el error junto al campo.
class HistorialVehiculoRequest extends FormRequest
{
    // El reporte es público, igual que el resto de las páginas de lectura.
    public function authorize(): bool
    {
        return true;
    }

    // Al fallar se vuelve al historial sin el parámetro inválido en la URL.
    protected $redirectRoute = 'reportes.historial';

    public function rules(): array
    {
        return [
            'vehiculo_id' => ['nullable', 'integer', 'exists:vehiculos,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'vehiculo_id.integer' => 'Selecciona un vehículo de la lista.',
            'vehiculo_id.exists' => 'El vehículo seleccionado no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'vehiculo_id' => 'vehículo',
        ];
    }
}
