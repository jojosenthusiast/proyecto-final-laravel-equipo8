<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CambiarContrasenaRequest;
use Illuminate\Support\Facades\Hash;

class ContrasenaController extends Controller
{
    public function edit()
    {
        return view('perfil.contrasena');
    }

    public function update(CambiarContrasenaRequest $request)
    {
        // [SEGURIDAD] Almacena la contraseña mediante hash.
        $request->user()->update([
            'password' => Hash::make(
                $request->validated('password')
            ),
        ]);

        // [SEGURIDAD] Regenera la sesión.
        $request->session()->regenerate();

        return redirect()
            ->route('perfil.contrasena.edit')
            ->with('exito', 'Contraseña actualizada.');
    }
}
