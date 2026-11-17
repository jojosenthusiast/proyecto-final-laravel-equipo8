<?php

namespace App\Policies;

use App\Models\OrdenServicio;
use App\Models\User;
use Illuminate\Auth\Access\Response;

// [SEGURIDAD] Reglas de autorización de las órdenes. Se aplican en el servidor con
// Gate::authorize(), así que ocultar el botón en la vista no es la única barrera.
class OrdenServicioPolicy
{
    // [REGLA-NEGOCIO] Regla 5: una orden finalizada no puede editarse.
    public function update(?User $user, OrdenServicio $orden): Response
    {
        return $orden->puedeEditarse()
            ? Response::allow()
            : Response::deny('Esta orden está finalizada y no puede editarse.');
    }

    // [SEGURIDAD] Solo se elimina una orden pendiente: en proceso o finalizada ya es historial
    // trabajado del vehículo.
    public function delete(?User $user, OrdenServicio $orden): Response
    {
        return $orden->estado === 'pendiente'
            ? Response::allow()
            : Response::deny('Solo se pueden eliminar órdenes pendientes.');
    }
}
