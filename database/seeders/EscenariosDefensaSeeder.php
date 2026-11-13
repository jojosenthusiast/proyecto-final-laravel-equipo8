<?php

namespace Database\Seeders;

use App\Models\OrdenServicio;
use App\Models\TipoServicio;
use App\Models\Vehiculo;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

// Escenarios fijos para la defensa: cada placa permite probar en vivo una regla o el reporte.
// Es idempotente: se busca cada vehículo por placa y sus órdenes solo se crean si no tiene ninguna.
class EscenariosDefensaSeeder extends Seeder
{
    public function run(): void
    {
        $mesActual = now()->startOfMonth();

        // P000001 · Regla 5: su única orden está finalizada y no puede editarse.
        $this->escenario('P000001', [
            'marca' => 'Toyota', 'modelo' => 'Corolla', 'anio' => 2018, 'color' => 'Blanco',
            'kilometraje' => 60000, 'propietario_nombre' => 'Ana María López Rivas', 'propietario_telefono' => '7012-3456',
        ], [
            ['Cambio de aceite', 'finalizada', $mesActual, 62000, 5.00],
        ]);

        // P000002 · Regla 3: su último kilometraje es 85 000; una orden nueva con 80 000 se rechaza.
        $this->escenario('P000002', [
            'marca' => 'Nissan', 'modelo' => 'Sentra', 'anio' => 2016, 'color' => 'Gris',
            'kilometraje' => 70000, 'propietario_nombre' => 'José Antonio Martínez Cruz', 'propietario_telefono' => '7123-4567',
        ], [
            ['Diagnóstico general', 'pendiente', now()->subDay(), 85000, 0.00],
        ]);

        // P000003 · Sin órdenes: se puede eliminar.
        $this->escenario('P000003', [
            'marca' => 'Honda', 'modelo' => 'Civic', 'anio' => 2020, 'color' => 'Azul',
            'kilometraje' => 30000, 'propietario_nombre' => 'María José Hernández Pérez', 'propietario_telefono' => '7234-5678',
        ], []);

        // P000004 · Con órdenes: no se puede eliminar. Sus órdenes finalizadas de los dos meses
        // anteriores, junto con la de P000001, dan datos al filtro de fechas del reporte.
        $this->escenario('P000004', [
            'marca' => 'Hyundai', 'modelo' => 'Tucson', 'anio' => 2019, 'color' => 'Rojo',
            'kilometraje' => 40000, 'propietario_nombre' => 'Carlos Ernesto Flores Guzmán', 'propietario_telefono' => '7345-6789',
        ], [
            ['Frenos', 'finalizada', $mesActual->copy()->subMonths(2)->addDays(9), 42000, 15.00],
            ['Alineación y balanceo', 'finalizada', $mesActual->copy()->subMonth()->addDays(9), 45000, 10.00],
            ['Revisión de suspensión', 'en_proceso', now(), 48000, 20.00],
        ]);
    }

    /**
     * @param  array<string, mixed>  $datos
     * @param  array<int, array{0: string, 1: string, 2: Carbon, 3: int, 4: float}>  $ordenes
     */
    private function escenario(string $placa, array $datos, array $ordenes): void
    {
        $vehiculo = Vehiculo::firstOrCreate(['placa' => $placa], $datos);

        if ($vehiculo->ordenes()->exists()) {
            return;
        }

        foreach ($ordenes as [$servicio, $estado, $ingreso, $kilometraje, $adicional]) {
            $tipo = $this->tipo($servicio);

            OrdenServicio::create([
                'vehiculo_id' => $vehiculo->id,
                'tipo_servicio_id' => $tipo->id,
                'fecha_ingreso' => $ingreso->toDateString(),
                // [REGLA-NEGOCIO] Regla 4: la entrega nunca es anterior al ingreso ni posterior a hoy.
                'fecha_entrega' => $estado === 'finalizada'
                    ? $ingreso->copy()->addDays(2)->min(now())->max($ingreso)->toDateString()
                    : null,
                'kilometraje_ingreso' => $kilometraje,
                'descripcion' => "Escenario de la defensa: {$servicio}",
                'costo_adicional' => $adicional,
                // Fórmula acordada: costo_total = precio_base + costo_adicional.
                'costo_total' => round((float) $tipo->precio_base + $adicional, 2),
                'estado' => $estado,
            ]);
        }

        // El kilometraje del vehículo queda al día con su última orden.
        $vehiculo->update(['kilometraje' => $vehiculo->ultimoKilometraje()]);
    }

    private function tipo(string $nombre): TipoServicio
    {
        // Los precios coinciden con TipoServicioSeeder por si este seeder se ejecuta solo.
        $precios = [
            'Cambio de aceite' => 25.00,
            'Frenos' => 80.00,
            'Alineación y balanceo' => 35.00,
            'Diagnóstico general' => 20.00,
            'Revisión de suspensión' => 45.00,
        ];

        return TipoServicio::firstOrCreate(['nombre' => $nombre], ['precio_base' => $precios[$nombre]]);
    }
}
