<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class CambiarContrasenaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        // [VALIDACION] Reglas para cambiar la contraseña.
        return [
            'actual' => [
                'required',
                'current_password',
            ],

            'password' => [
                'required',
                'confirmed',
                Password::min(8),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'actual.required' =>
                'La contraseña actual es obligatoria.',

            'actual.current_password' =>
                'La contraseña actual es incorrecta.',

            'password.required' =>
                'La nueva contraseña es obligatoria.',

            'password.confirmed' =>
                'Las contraseñas no coinciden.',

            'password.min' =>
                'La contraseña debe tener al menos 8 caracteres.',
        ];
    }
}
