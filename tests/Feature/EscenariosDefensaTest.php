<?php

namespace Tests\Feature;

use App\Models\OrdenServicio;
use App\Models\Vehiculo;
use Database\Seeders\EscenariosDefensaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Datos fijos de la defensa: tras migrate:fresh --seed existen las placas P000001 a P000004
// con las órdenes que permiten probar en vivo cada regla y el reporte.
class EscenariosDefensaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function vehiculo(string $placa): Vehiculo
    {
        return Vehiculo::with('ordenes')->where('placa', $placa)->firstOrFail();
    }

    public function test_p000001_tiene_una_sola_orden_y_esta_finalizada(): void
    {
        $ordenes = $this->vehiculo('P000001')->ordenes;

        $this->assertCount(1, $ordenes);
        $this->assertTrue($ordenes->first()->estaFinalizada());
        $this->assertFalse($ordenes->first()->puedeEditarse());
    }

    public function test_p000002_tiene_una_orden_a_85000_km(): void
    {
        $vehiculo = $this->vehiculo('P000002');

        $this->assertSame(85000, $vehiculo->ordenes->max('kilometraje_ingreso'));
        $this->assertSame(85000, $vehiculo->ultimoKilometraje());
        $this->assertGreaterThan(80000, $vehiculo->ultimoKilometraje());
    }

    public function test_p000003_no_tiene_ordenes_y_p000004_si(): void
    {
        $this->assertCount(0, $this->vehiculo('P000003')->ordenes);
        $this->assertGreaterThan(0, $this->vehiculo('P000004')->ordenes->count());
    }

    public function test_hay_ordenes_finalizadas_en_cada_uno_de_los_ultimos_3_meses(): void
    {
        $meses = OrdenServicio::estado('finalizada')
            ->whereHas('vehiculo', fn ($q) => $q->whereIn('placa', ['P000001', 'P000004']))
            ->get()
            ->map(fn ($orden) => $orden->fecha_ingreso->format('Y-m'))
            ->unique();

        foreach ([0, 1, 2] as $atras) {
            $this->assertContains(now()->startOfMonth()->subMonths($atras)->format('Y-m'), $meses);
        }
    }

    public function test_las_ordenes_respetan_la_formula_y_las_fechas(): void
    {
        $ordenes = OrdenServicio::with('tipoServicio')
            ->whereHas('vehiculo', fn ($q) => $q->whereIn('placa', ['P000001', 'P000002', 'P000004']))
            ->get();

        foreach ($ordenes as $orden) {
            $this->assertSame(
                round((float) $orden->tipoServicio->precio_base + (float) $orden->costo_adicional, 2),
                (float) $orden->costo_total
            );
            if ($orden->fecha_entrega) {
                $this->assertTrue($orden->fecha_entrega->greaterThanOrEqualTo($orden->fecha_ingreso));
            }
        }
    }

    public function test_el_seeder_es_idempotente(): void
    {
        $vehiculos = Vehiculo::count();
        $ordenes = OrdenServicio::count();

        $this->seed(EscenariosDefensaSeeder::class);

        $this->assertSame($vehiculos, Vehiculo::count());
        $this->assertSame($ordenes, OrdenServicio::count());
    }
}
