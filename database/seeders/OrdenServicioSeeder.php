<?php

namespace Database\Seeders;

use App\Models\TipoServicio;
use App\Models\Vehiculo;
use Database\Factories\OrdenServicioFactory;
use Illuminate\Database\Seeder;

class OrdenServicioSeeder extends Seeder
{
    /**
     * Genera órdenes con kilometrajes y fechas coherentes.
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

            // Cada vehículo parte de un kilometraje anterior.
            $km = max(0, $vehiculo->kilometraje - 10000);
            $fecha = now()->subMonths(6);

            for ($i = 0; $i < $cantidad; $i++) {
                $tipo = $tipos->random();
                $estado = $estados[$n++ % 3];

                // El kilometraje aumenta con cada nueva orden.
                $km += fake()->numberBetween(1000, 2500);

                // Las fechas de ingreso avanzan cronológicamente.
                $fecha = $fecha->copy()->addDays(
                    fake()->numberBetween(10, 40)
                );

                $factory = OrdenServicioFactory::new();

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

                    'fecha_entrega' => $estado === 'finalizada'
                        ? $fecha->copy()
                            ->addDays(fake()->numberBetween(1, 5))
                            ->toDateString()
                        : null,

                    'kilometraje_ingreso' => $km,

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
