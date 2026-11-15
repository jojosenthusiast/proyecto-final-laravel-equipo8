@extends('layouts.app')

@section('titulo', 'Iniciar sesión')

@section('contenido')

    <h1 class="titulo-pagina">Iniciar sesión</h1>

    <form method="POST" action="{{ url('/login') }}">
        @csrf

        <div class="campo">
            <label for="email">Correo electrónico</label>

            <input
                type="email"
                id="email"
                name="email"
                value="{{ old('email') }}"
                required
            >

            @error('email')
                <p class="error-campo">{{ $message }}</p>
            @enderror
        </div>

        <div class="campo">
            <label for="password">Contraseña</label>

            <input
                type="password"
                id="password"
                name="password"
                required
            >

            @error('password')
                <p class="error-campo">{{ $message }}</p>
            @enderror
        </div>

        <div class="campo">
            <label>
                <input
                    type="checkbox"
                    name="recordar"
                    value="1"
                >
                Recordar sesión
            </label>
        </div>

        <button type="submit" class="boton">
            Iniciar sesión
        </button>
    </form>

@endsection
