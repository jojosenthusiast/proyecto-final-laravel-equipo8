<?php

namespace Database\Seeders;

use App\Models\OrdenServicio;
use App\Models\TipoServicio;
use App\Models\Vehiculo;
use Illuminate\Database\Seeder;

class OrdenServicioSeeder extends Seeder
{
    /**
     * Genera órdenes de servicio para los vehículos.
     */
    public function run(): void
    {
        $tipos = TipoServicio::all();

        if ($tipos->isEmpty()) {
            return;
        }

        $estados = [
            'pendiente',
            'en_proceso',
            'finalizada',
        ];

        $n = 0;

        foreach (Vehiculo::orderBy('id')->take(12)->get() as $vehiculo) {
            $cantidad = fake()->numberBetween(1, 3);
            $fecha = now()->subMonths(6);

            for ($i = 0; $i < $cantidad; $i++) {
                $tipo = $tipos->random();
                $estado = $estados[$n++ % 3];

                $fecha = $fecha->copy()->addDays(
                    fake()->numberBetween(10, 40)
                );

                $factory = \Database\Factories\OrdenServicioFactory::new();

                if ($estado === 'finalizada') {
                    $factory = $factory->finalizada();
                }

                if ($estado === 'en_proceso') {
                    $factory = $factory->enProceso();
                }

                $factory->create([
                    'vehiculo_id' => $vehiculo->id,
                    'tipo_servicio_id' => $tipo->id,
                    'fecha_ingreso' => $fecha->toDateString(),
                    'fecha_entrega' => null,
                    'kilometraje_ingreso' => $vehiculo->kilometraje,
                    'costo_total' => round(
                        (float) $tipo->precio_base
                        + fake()->randomFloat(2, 0, 60),
                        2
                    ),
                ]);
            }
        }
    }
}
