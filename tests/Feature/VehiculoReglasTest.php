<?php

namespace Tests\Feature;

use App\Http\Requests\StoreVehiculoRequest;
use App\Models\Vehiculo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

// Reglas 1 (placa) y 2 (año) y el resto de la validación del vehículo, probadas directamente
// sobre las reglas del Form Request, sin depender todavía de las vistas ni de las rutas.
class VehiculoReglasTest extends TestCase
{
    use RefreshDatabase;

    private function datosValidos(array $cambios = []): array
    {
        return array_merge([
            'placa' => 'P123456',
            'marca' => 'Toyota',
            'modelo' => 'Corolla',
            'anio' => 2015,
            'color' => 'Gris',
            'kilometraje' => 85000,
            'propietario_nombre' => 'María López',
            'propietario_telefono' => '7000-1234',
        ], $cambios);
    }

    private function validar(array $datos): \Illuminate\Validation\Validator
    {
        $request = new StoreVehiculoRequest;

        return Validator::make($datos, $request->rules(), $request->messages(), $request->attributes());
    }

    public function test_un_vehiculo_con_datos_correctos_pasa_la_validacion(): void
    {
        $this->assertTrue($this->validar($this->datosValidos())->passes());
    }

    public static function datosInvalidos(): array
    {
        $siguiente = now()->year + 1;

        return [
            'placa con otra letra' => ['placa', 'A123456', 'La placa debe tener el formato P123456.'],
            'placa con 5 dígitos' => ['placa', 'P12345', 'La placa debe tener el formato P123456.'],
            'placa con letras' => ['placa', 'PABC123', 'La placa debe tener el formato P123456.'],
            'año 1979' => ['anio', 1979, "El año debe estar entre 1980 y {$siguiente}."],
            'año actual + 2' => ['anio', now()->year + 2, "El año debe estar entre 1980 y {$siguiente}."],
            'año con letras' => ['anio', 'dos mil', 'El campo año debe ser un número entero.'],
            'kilometraje negativo' => ['kilometraje', -1, 'El kilometraje no puede ser negativo.'],
            'kilometraje mayor al límite' => ['kilometraje', 1000000, 'El kilometraje no puede ser mayor que 999 999 km.'],
            'teléfono sin guion' => ['propietario_telefono', '70001234', 'El teléfono debe tener el formato 0000-0000.'],
            'marca demasiado larga' => ['marca', str_repeat('a', 51), 'La marca no puede tener más de 50 caracteres.'],
            'propietario demasiado largo' => ['propietario_nombre', str_repeat('a', 101), 'El nombre del propietario no puede tener más de 100 caracteres.'],
            'propietario vacío' => ['propietario_nombre', '', 'El campo nombre del propietario es obligatorio.'],
        ];
    }

    #[DataProvider('datosInvalidos')]
    public function test_un_dato_incorrecto_se_rechaza_con_mensaje_en_espanol(string $campo, mixed $valor, string $mensaje): void
    {
        $validador = $this->validar($this->datosValidos([$campo => $valor]));

        $this->assertTrue($validador->fails());
        $this->assertSame($mensaje, $validador->errors()->first($campo));
    }

    public function test_la_placa_repetida_se_rechaza(): void
    {
        Vehiculo::factory()->create(['placa' => 'P123456']);

        $validador = $this->validar($this->datosValidos());

        $this->assertSame('Ya hay un vehículo registrado con esa placa.', $validador->errors()->first('placa'));
    }

    public function test_los_limites_del_anio_son_validos(): void
    {
        $this->assertTrue($this->validar($this->datosValidos(['anio' => 1980]))->passes());
        $this->assertTrue($this->validar($this->datosValidos(['anio' => now()->year + 1]))->passes());
    }

    public function test_cada_campo_obligatorio_tiene_su_mensaje(): void
    {
        $errores = $this->validar([])->errors();

        foreach (['placa', 'marca', 'modelo', 'anio', 'color', 'kilometraje', 'propietario_nombre', 'propietario_telefono'] as $campo) {
            $this->assertStringStartsWith('El campo ', $errores->first($campo), "Falta el error de {$campo}");
            $this->assertStringEndsWith(' es obligatorio.', $errores->first($campo));
        }
        $this->assertFalse($errores->has('imagen'), 'La imagen es opcional.');
    }

    // prepareForValidation() corre cuando Laravel resuelve el request, no con Validator::make,
    // por eso esta prueba arma la petición completa.
    public function test_la_placa_se_normaliza_a_mayusculas_y_sin_espacios(): void
    {
        $request = StoreVehiculoRequest::create('/vehiculos', 'POST', $this->datosValidos(['placa' => ' p123456 ']));
        $request->setContainer($this->app)->setRedirector($this->app->make('redirect'));

        $request->validateResolved();

        $this->assertSame('P123456', $request->validated('placa'));
    }
}
