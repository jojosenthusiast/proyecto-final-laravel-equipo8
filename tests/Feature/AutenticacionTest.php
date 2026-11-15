<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AutenticacionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('admin@biela.test|127.0.0.1');
    }

    public function test_muestra_formulario_login(): void
    {
        $this->get('/login')
            ->assertStatus(200);
    }

    public function test_usuario_puede_iniciar_sesion(): void
    {
        $usuario = User::factory()->create([
            'email' => 'admin@biela.test',
            'password' => Hash::make('Prueba1234'),
        ]);

        $response = $this->post('/login', [
            'email' => $usuario->email,
            'password' => 'Prueba1234',
        ]);

        $response->assertRedirect('/');

        $this->assertAuthenticatedAs($usuario);
    }

    public function test_rechaza_credenciales_incorrectas(): void
    {
        $usuario = User::factory()->create([
            'password' => Hash::make('Prueba1234'),
        ]);

        $this->from('/login')->post('/login', [
            'email' => $usuario->email,
            'password' => 'incorrecta',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_usuario_puede_cerrar_sesion(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->post('/logout')
            ->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_bloquea_el_sexto_intento(): void
    {
        $usuario = User::factory()->create([
            'email' => 'admin@biela.test',
            'password' => Hash::make('Prueba1234'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->from('/login')->post('/login', [
                'email' => $usuario->email,
                'password' => 'incorrecta',
            ])->assertSessionHasErrors([
                'email' => 'Las credenciales ingresadas son incorrectas.',
            ]);
        }

        $response = $this->from('/login')->post('/login', [
            'email' => $usuario->email,
            'password' => 'Prueba1234',
        ]);

        $response->assertSessionHasErrors('email');

        $this->assertStringContainsString(
            'Demasiados intentos.',
            session('errors')->first('email')
        );

        $this->assertGuest();
    }

    public function test_permite_ingresar_despues_del_bloqueo(): void
    {
        $usuario = User::factory()->create([
            'email' => 'admin@biela.test',
            'password' => Hash::make('Prueba1234'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => $usuario->email,
                'password' => 'incorrecta',
            ])->assertSessionHasErrors('email');
        }

        $this->travel(61)->seconds();

        $this->post('/login', [
            'email' => $usuario->email,
            'password' => 'Prueba1234',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($usuario);

        $this->travelBack();
    }

    public function test_login_correcto_reinicia_los_intentos(): void
    {
        $usuario = User::factory()->create([
            'email' => 'admin@biela.test',
            'password' => Hash::make('Prueba1234'),
        ]);

        for ($i = 0; $i < 3; $i++) {
            $this->post('/login', [
                'email' => $usuario->email,
                'password' => 'incorrecta',
            ])->assertSessionHasErrors('email');
        }

        $this->post('/login', [
            'email' => $usuario->email,
            'password' => 'Prueba1234',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($usuario);

        $this->assertEquals(
            0,
            RateLimiter::attempts(
                'admin@biela.test|127.0.0.1'
            )
        );
    }
}
