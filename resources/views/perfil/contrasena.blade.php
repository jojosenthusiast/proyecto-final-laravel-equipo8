@extends('layouts.app')

@section('titulo', 'Cambiar contraseña')

@section('contenido')

    <h1 class="titulo-pagina">Cambiar contraseña</h1>

    <p>
        Introduce tu contraseña actual y establece
        una nueva contraseña para tu cuenta.
    </p>

    <form
        method="POST"
        action="{{ route('perfil.contrasena.update') }}"
    >
        {{-- [SEGURIDAD] Protección CSRF. --}}
        @csrf
        @method('PUT')

        <div class="campo">
            <label for="actual">
                Contraseña actual
            </label>

            <input
                type="password"
                id="actual"
                name="actual"
                autocomplete="current-password"
                required
                @error('actual')
                    aria-invalid="true"
                    aria-describedby="actual-error"
                @enderror
            >

            @error('actual')
                <p class="error-campo" id="actual-error">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div class="campo">
            <label for="password">
                Nueva contraseña
            </label>

            <input
                type="password"
                id="password"
                name="password"
                autocomplete="new-password"
                minlength="8"
                required
                @error('password')
                    aria-invalid="true"
                    aria-describedby="password-error"
                @enderror
            >

            @error('password')
                <p class="error-campo" id="password-error">
                    {{ $message }}
                </p>
            @enderror
        </div>

        <div class="campo">
            <label for="password_confirmation">
                Confirmar nueva contraseña
            </label>

            <input
                type="password"
                id="password_confirmation"
                name="password_confirmation"
                autocomplete="new-password"
                required
            >
        </div>

        <button type="submit" class="boton">
            Actualizar contraseña
        </button>

        <a href="{{ route('inicio') }}" class="boton">
            Cancelar
        </a>
    </form>

@endsection
