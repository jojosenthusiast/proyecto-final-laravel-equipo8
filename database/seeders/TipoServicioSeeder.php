<?php 
namespace Database\Seeders;

use App\Models\TipoServicio;
use Illuminate\Database\Seeder;

class TipoServicioSeeder extends Seeder
{
    /**
     * Registra los servicios iniciales del taller.
     */
    public function run(): void
    {
        $tipos = [
            [
                'nombre' => 'Cambio de aceite',
                'descripcion' => 'Aceite y filtro de motor',
                'precio_base' => 25.00,
            ],
            [
                'nombre' => 'Frenos',
                'descripcion' => 'Revisión y cambio de pastillas',
                'precio_base' => 80.00,
            ],
            [
                'nombre' => 'Alineación y balanceo',
                'descripcion' => 'Alineación de las cuatro ruedas',
                'precio_base' => 35.00,
            ],
            [
                'nombre' => 'Diagnóstico general',
                'descripcion' => 'Revisión con escáner',
                'precio_base' => 20.00,
            ],
            [
                'nombre' => 'Revisión de suspensión',
                'descripcion' => 'Amortiguadores y rótulas',
                'precio_base' => 45.00,
            ],
        ];

        foreach ($tipos as $tipo) {
            TipoServicio::updateOrCreate(
                ['nombre' => $tipo['nombre']],
                $tipo
            );
        }
    }
}
