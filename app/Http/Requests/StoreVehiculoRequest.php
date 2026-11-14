<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

// [VALIDACION] Datos del vehículo al registrarlo. Las reglas viven aquí y no en el controlador,
// así el registro y la edición comparten la misma validación y las mismas respuestas en español.
class StoreVehiculoRequest extends FormRequest
{
    // La protección con sesión la hace la ruta (middleware auth); aquí no hay permisos extra.
    public function authorize(): bool
    {
        return true;
    }

    // La placa se guarda siempre en mayúsculas y sin espacios: «p123456 » queda como «P123456»,
    // y así la regla de placa única no deja pasar la misma placa escrita de otra forma.
    protected function prepareForValidation(): void
    {
        $this->merge(['placa' => strtoupper(trim((string) $this->placa))]);
    }

    public function rules(): array
    {
        return [
            // [REGLA-NEGOCIO] Regla 1: placa única con formato P seguido de 6 dígitos.
            'placa' => ['required', 'string', 'regex:/^P\d{6}$/', $this->reglaPlacaUnica()],
            'marca' => ['required', 'string', 'max:50'],
            'modelo' => ['required', 'string', 'max:50'],
            // [REGLA-NEGOCIO] Regla 2: año entre 1980 y el año siguiente al actual (modelos del
            // próximo año). El límite se calcula en cada petición, no queda escrito a mano.
            'anio' => ['required', 'integer', 'between:1980,'.(now()->year + 1)],
            'color' => ['required', 'string', 'max:30'],
            'kilometraje' => ['required', 'integer', 'min:0', 'max:999999'],
            'propietario_nombre' => ['required', 'string', 'max:100'],
            'propietario_telefono' => ['required', 'string', 'regex:/^\d{4}-\d{4}$/'],
            // La foto es opcional; si llega, solo JPG, PNG o WEBP de hasta 2 MB (max se mide en KB).
            'imagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    // Al registrar, la placa no puede existir en ningún otro vehículo. La edición
    // (UpdateVehiculoRequest) sobrescribe este método para ignorar al propio vehículo.
    protected function reglaPlacaUnica(): Unique
    {
        return Rule::unique('vehiculos', 'placa');
    }

    // La aplicación usa el idioma por defecto de Laravel (inglés), así que cada mensaje que puede
    // ver el usuario se escribe aquí en español.
    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser texto.',
            'integer' => 'El campo :attribute debe ser un número entero.',
            'marca.max' => 'La marca no puede tener más de :max caracteres.',
            'modelo.max' => 'El modelo no puede tener más de :max caracteres.',
            'color.max' => 'El color no puede tener más de :max caracteres.',
            'propietario_nombre.max' => 'El nombre del propietario no puede tener más de :max caracteres.',

            'placa.regex' => 'La placa debe tener el formato P123456.',
            'placa.unique' => 'Ya hay un vehículo registrado con esa placa.',
            'anio.between' => 'El año debe estar entre :min y :max.',
            'kilometraje.min' => 'El kilometraje no puede ser negativo.',
            'kilometraje.max' => 'El kilometraje no puede ser mayor que 999 999 km.',
            'propietario_telefono.regex' => 'El teléfono debe tener el formato 0000-0000.',
            'imagen.image' => 'El archivo debe ser una imagen.',
            'imagen.mimes' => 'La imagen debe ser JPG, PNG o WEBP.',
            'imagen.max' => 'La imagen no puede pesar más de 2 MB.',
            'imagen.uploaded' => 'La imagen no se pudo subir; revisa que no pese más de 2 MB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'placa' => 'placa',
            'marca' => 'marca',
            'modelo' => 'modelo',
            'anio' => 'año',
            'color' => 'color',
            'kilometraje' => 'kilometraje',
            'propietario_nombre' => 'nombre del propietario',
            'propietario_telefono' => 'teléfono del propietario',
            'imagen' => 'imagen',
        ];
    }
}
