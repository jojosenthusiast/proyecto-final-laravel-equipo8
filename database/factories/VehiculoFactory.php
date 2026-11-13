<?php

namespace Database\Factories;

use App\Models\Vehiculo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehiculo>
 */
class VehiculoFactory extends Factory
{
    protected $model = Vehiculo::class;

    public function definition(): array
    {
        // Cada marca solo genera sus propios modelos, para que los datos de prueba sean creíbles.
        $modelos = [
            'Toyota' => ['Corolla', 'Hilux', 'Yaris', 'RAV4'],
            'Nissan' => ['Sentra', 'Frontier', 'Versa'],
            'Honda' => ['Civic', 'CR-V', 'Fit'],
            'Hyundai' => ['Accent', 'Tucson', 'Elantra'],
            'Kia' => ['Rio', 'Sportage', 'Picanto'],
            'Suzuki' => ['Swift', 'Vitara'],
            'Mazda' => ['Mazda3', 'CX-5'],
        ];
        $marca = fake()->randomElement(array_keys($modelos));

        return [
            // Formato de placa particular: P y seis dígitos (se valida en Fase 2).
            'placa' => 'P'.fake()->unique()->numerify('######'),
            'marca' => $marca,
            'modelo' => fake()->randomElement($modelos[$marca]),
            // El tope es el año actual, no un número fijo, para respetar la regla de Fase 2.
            'anio' => fake()->numberBetween(1995, (int) now()->year),
            'color' => fake()->randomElement(['Blanco', 'Negro', 'Gris', 'Rojo', 'Azul', 'Plateado']),
            'kilometraje' => fake()->numberBetween(5000, 250000),
            // Nombre y dos apellidos, como en El Salvador; name() de es_ES agrega "D." o "Segundo".
            'propietario_nombre' => fake()->firstName().' '.fake()->lastName().' '.fake()->lastName(),
            'propietario_telefono' => fake()->numerify('####-####'),
            'imagen' => null,
        ];
    }

    // Vehículo con un kilometraje exacto, para probar la regla 3 con valores conocidos.
    public function conKilometraje(int $km): static
    {
        return $this->state(fn (array $attributes) => [
            'kilometraje' => $km,
        ]);
    }
}
