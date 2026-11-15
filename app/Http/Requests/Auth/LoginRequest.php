<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'recordar' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'Ingrese un correo válido.',
            'password.required' => 'La contraseña es obligatoria.',
        ];
    }

    public function autenticar(): void
    {
        $this->verificarLimite();

        // [SEGURIDAD] Validación de credenciales.
        if (!Auth::attempt(
            $this->only('email', 'password'),
            $this->boolean('recordar')
        )) {
            RateLimiter::hit($this->throttleKey(), 60);

            throw ValidationException::withMessages([
                'email' => 'Las credenciales ingresadas son incorrectas.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    public function verificarLimite(): void
    {
        $clave = $this->throttleKey();

        // [SEGURIDAD] Prevención de ataques de fuerza bruta.
        if (!RateLimiter::tooManyAttempts($clave, 5)) {
            return;
        }

        $segundos = RateLimiter::availableIn($clave);

        throw ValidationException::withMessages([
            'email' => "Demasiados intentos. Intenta de nuevo en {$segundos} segundos.",
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(
            Str::lower($this->input('email'))
            . '|' . $this->ip()
        );
    }
}
