<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Ejecuta los seeders iniciales del taller.
     */
    public function run(): void
    {
        $this->call([
            TipoServicioSeeder::class,

            // Jorge habilitará este seeder cuando esté listo.
            // VehiculoSeeder::class,

            // Leonel habilitará este seeder cuando esté listo.
            // OrdenServicioSeeder::class,
        ]);
    }
}
