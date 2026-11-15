<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

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

    /**
     * Ejecuta una operación que guarda la foto y su registro sin dejar archivos huérfanos.
     *
     * Si hay archivo, lo guarda y ejecuta $operacion($rutaNueva) dentro de una transacción.
     * Si la operación lanza una excepción, borra la foto nueva y relanza el error. Si termina
     * bien y había una foto anterior, la borra solo en ese momento.
     *
     * @template T
     *
     * @param  callable(?string): T  $operacion
     * @return T
     *
     * @throws Throwable si la operación falla; la foto nueva se elimina antes de relanzar.
     */
    public function conImagen(?UploadedFile $archivo, callable $operacion, ?string $anterior = null): mixed
    {
        if ($archivo === null) {
            return $operacion(null);
        }

        $rutaNueva = $this->guardar($archivo);

        try {
            $resultado = DB::transaction(fn () => $operacion($rutaNueva));
        } catch (Throwable $excepcion) {
            $this->eliminar($rutaNueva);

            throw $excepcion;
        }

        $this->eliminar($anterior);

        return $resultado;
    }
}
