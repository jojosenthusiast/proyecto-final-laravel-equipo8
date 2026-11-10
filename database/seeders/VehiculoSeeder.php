<?php

namespace Database\Seeders;

use App\Models\Vehiculo;
use Illuminate\Database\Seeder;

class VehiculoSeeder extends Seeder
{
    /**
     * Registra los vehículos de prueba del taller.
     */
    public function run(): void
    {
        // Veinte vehículos llenan dos páginas del listado (10 por página).
        Vehiculo::factory()->count(20)->create();
    }
}
