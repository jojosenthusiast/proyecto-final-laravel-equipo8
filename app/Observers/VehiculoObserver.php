<?php

namespace App\Observers;

use App\Models\Vehiculo;
use App\Services\ImagenVehiculoService;

// [ENCAPSULAMIENTO] Centraliza el borrado de la foto cuando desaparece el vehículo, para que
// ningún controlador tenga que recordarlo y no queden archivos huérfanos.
class VehiculoObserver
{
    // [INYECCION-DEPENDENCIAS] Laravel resuelve el servicio de imágenes al crear el observer.
    public function __construct(
        private readonly ImagenVehiculoService $imagenes,
    ) {}

    public function deleted(Vehiculo $vehiculo): void
    {
        $this->imagenes->eliminar($vehiculo->imagen);
    }
}
