<?php

namespace Database\Factories;

use App\Models\TipoServicio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TipoServicio>
 */
class TipoServicioFactory extends Factory
{
    protected $model = TipoServicio::class;

    public function definition(): array
    {
        return [
            // El nombre es único en la tabla; el sufijo numérico evita choques entre pruebas.
            'nombre' => fake()->randomElement([
                'Cambio de aceite', 'Frenos', 'Alineación', 'Balanceo', 'Diagnóstico',
                'Suspensión', 'Embrague', 'Batería', 'Aire acondicionado', 'Afinado',
            ]).' '.fake()->unique()->numberBetween(1, 99999),
            'descripcion' => fake()->sentence(4),
            // Siempre mayor que 0: un servicio gratis no tiene sentido en el reporte de ingresos.
            'precio_base' => fake()->randomFloat(2, 15, 150),
        ];
    }
}
