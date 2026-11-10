<?php

namespace Database\Factories;

use App\Models\OrdenServicio;
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
            'costo_total' => fake()->randomFloat(2, 20, 300),
            'estado' => 'pendiente',
        ];
    }

    public function enProceso(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'en_proceso',
        ]);
    }

    public function finalizada(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'finalizada',
        ]);
    }
}
