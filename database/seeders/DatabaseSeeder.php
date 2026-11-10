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

            VehiculoSeeder::class,

            OrdenServicioSeeder::class,
        ]);
    }
}
