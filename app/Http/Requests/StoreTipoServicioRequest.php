<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// [VALIDACION] Registro de un servicio del catálogo. El nombre es único, el precio debe ser
// positivo y la descripción es opcional.
class StoreTipoServicioRequest extends FormRequest
{
    // La ruta ya está protegida por el middleware auth; aquí solo se valida el contenido.
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:60', Rule::unique('tipos_servicio', 'nombre')],
            'precio_base' => ['required', 'numeric', 'gt:0', 'max:99999.99'],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del servicio es obligatorio.',
            'nombre.string' => 'El nombre del servicio debe ser texto.',
            'nombre.max' => 'El nombre no puede tener más de 60 caracteres.',
            'nombre.unique' => 'Ya existe un servicio con ese nombre.',
            'precio_base.required' => 'El precio base es obligatorio.',
            'precio_base.numeric' => 'El precio base debe ser un número.',
            'precio_base.gt' => 'El precio base debe ser mayor que cero.',
            'precio_base.max' => 'El precio base no puede superar los 99999.99.',
            'descripcion.string' => 'La descripción debe ser texto.',
            'descripcion.max' => 'La descripción no puede tener más de 255 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nombre' => 'nombre del servicio',
            'precio_base' => 'precio base',
            'descripcion' => 'descripción',
        ];
    }
}
