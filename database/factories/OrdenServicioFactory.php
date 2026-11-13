<?php

namespace Database\Factories;

use App\Models\OrdenServicio;
use App\Models\TipoServicio;
use App\Models\Vehiculo;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrdenServicio>
 */
class OrdenServicioFactory extends Factory
{
    protected $model = OrdenServicio::class;

    public function definition(): array
    {
        return [
            // Si la prueba no indica vehículo ni servicio, la fábrica crea uno de cada uno.
            'vehiculo_id' => Vehiculo::factory(),
            'tipo_servicio_id' => TipoServicio::factory(),
            'descripcion' => fake()->randomElement([
                'Revisión programada',
                'Ruido al frenar',
                'Mantenimiento preventivo',
                'Vibración a alta velocidad',
                'Luz de motor encendida',
            ]),
            'fecha_ingreso' => fake()->dateTimeBetween('-6 months', 'now')
                ->format('Y-m-d'),
            'fecha_entrega' => null,
            'kilometraje_ingreso' => fake()->numberBetween(10000, 150000),
            'costo_adicional' => fake()->randomFloat(2, 0, 60),
            // Fórmula acordada (la implementa Leo en L4): costo_total = precio_base + costo_adicional.
            'costo_total' => fn (array $atributos) => round(
                (float) TipoServicio::whereKey($atributos['tipo_servicio_id'])->value('precio_base')
                    + (float) $atributos['costo_adicional'],
                2
            ),
            'estado' => 'pendiente',
        ];
    }

    public function pendiente(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'pendiente',
            'fecha_entrega' => null,
        ]);
    }

    public function enProceso(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'en_proceso',
            'fecha_entrega' => null,
        ]);
    }

    // [REGLA-NEGOCIO] Regla 4: la fecha de entrega nunca es anterior a la de ingreso.
    public function finalizada(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'finalizada',
            'fecha_entrega' => Carbon::parse($attributes['fecha_ingreso'])
                ->addDays(fake()->numberBetween(0, 5))
                ->format('Y-m-d'),
        ]);
    }
}
