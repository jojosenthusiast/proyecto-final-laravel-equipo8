<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

// [ENCAPSULAMIENTO] Única pieza que escribe las fotos de los vehículos en el disco público.
// [INYECCION-DEPENDENCIAS] Laravel resuelve esta clase automáticamente en los controladores y
// en el observer, sin registrarla a mano en un proveedor de servicios.
class ImagenVehiculoService
{
    /**
     * Guarda una foto de vehículo y devuelve su ruta relativa dentro del disco público.
     *
     * La base de datos guarda solo la ruta (por ejemplo "vehiculos/abc123.jpg"), nunca el
     * contenido del archivo.
     *
     * @throws RuntimeException si el archivo no se puede guardar.
     */
    public function guardar(UploadedFile $archivo): string
    {
        $ruta = $archivo->store('vehiculos', 'public');

        if ($ruta === false) {
            throw new RuntimeException('No se pudo guardar la imagen del vehículo.');
        }

        return $ruta;
    }

    /**
     * Guarda la foto nueva y, cuando ya está a salvo, elimina la anterior.
     *
     * @throws RuntimeException si la foto nueva no se puede guardar.
     */
    public function reemplazar(?string $actual, UploadedFile $nuevo): string
    {
        $rutaNueva = $this->guardar($nuevo);

        $this->eliminar($actual);

        return $rutaNueva;
    }

    /**
     * Elimina una foto del disco público.
     *
     * No lanza error si la ruta es null o si el archivo ya no existe: el observer y el
     * servicio pueden intentar borrar la misma foto sin romper la aplicación.
     */
    public function eliminar(?string $ruta): void
    {
        if ($ruta === null || $ruta === '') {
            return;
        }

        Storage::disk('public')->delete($ruta);
    }
}
