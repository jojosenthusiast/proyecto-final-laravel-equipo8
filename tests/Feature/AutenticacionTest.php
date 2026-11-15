<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AutenticacionTest extends TestCase
{
    use RefreshDatabase;

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
}
