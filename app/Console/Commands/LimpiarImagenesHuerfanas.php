<?php

namespace App\Console\Commands;

use App\Models\Vehiculo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

// [ENCAPSULAMIENTO] Revisa el disco público y avisa de las fotos de vehículos que ya no tienen
// fila en la base. Sin --eliminar solo las lista; con --eliminar las borra.
class LimpiarImagenesHuerfanas extends Command
{
    protected $signature = 'biela:imagenes-huerfanas {--eliminar : Borra los archivos huérfanos}';

    protected $description = 'Lista y opcionalmente elimina las fotos de vehículos que ya no existen';

    public function handle(): int
    {
        $archivos = collect(Storage::disk('public')->files('vehiculos'));
        $enUso = Vehiculo::query()->whereNotNull('imagen')->pluck('imagen');

        $huerfanos = $archivos->diff($enUso)->values();

        if ($huerfanos->isEmpty()) {
            $this->info('No hay archivos huérfanos.');

            return self::SUCCESS;
        }

        $this->table(
            ['Archivo huérfano'],
            $huerfanos->map(fn (string $ruta) => [$ruta])->all()
        );

        $cantidad = $huerfanos->count();

        if ($this->option('eliminar')) {
            Storage::disk('public')->delete($huerfanos->all());

            $this->info($cantidad === 1
                ? '1 archivo huérfano eliminado.'
                : "{$cantidad} archivos huérfanos eliminados.");

            return self::SUCCESS;
        }

        $this->info($cantidad === 1
            ? '1 archivo huérfano encontrado. Usa --eliminar para borrarlo.'
            : "{$cantidad} archivos huérfanos encontrados. Usa --eliminar para borrarlos.");

        return self::SUCCESS;
    }
}
