<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CambiarContrasenaTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_no_puede_cambiar_contrasena(): void
    {
        $this->get('/perfil/contrasena')
            ->assertRedirect(route('login'));

        $this->put('/perfil/contrasena', [
            'actual' => 'Anterior123',
            'password' => 'Nueva1234',
            'password_confirmation' => 'Nueva1234',
        ])->assertRedirect(route('login'));
    }

    public function test_usuario_puede_ver_formulario(): void
    {
        $this->iniciarSesion();

        $this->get('/perfil/contrasena')
            ->assertOk()
            ->assertSee('Cambiar contraseña');
    }

    public function test_rechaza_contrasena_actual_incorrecta(): void
    {
        $usuario = User::factory()->create([
            'password' => Hash::make('Anterior123'),
        ]);

        $this->actingAs($usuario)
            ->from('/perfil/contrasena')
            ->put('/perfil/contrasena', [
                'actual' => 'Incorrecta123',
                'password' => 'Nueva1234',
                'password_confirmation' => 'Nueva1234',
            ])
            ->assertSessionHasErrors('actual');

        $this->assertTrue(
            Hash::check(
                'Anterior123',
                $usuario->fresh()->password
            )
        );
    }

    public function test_rechaza_confirmacion_distinta(): void
    {
        $usuario = User::factory()->create([
            'password' => Hash::make('Anterior123'),
        ]);

        $this->actingAs($usuario)
            ->from('/perfil/contrasena')
            ->put('/perfil/contrasena', [
                'actual' => 'Anterior123',
                'password' => 'Nueva1234',
                'password_confirmation' => 'Diferente123',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue(
            Hash::check(
                'Anterior123',
                $usuario->fresh()->password
            )
        );
    }

    public function test_rechaza_contrasena_demasiado_corta(): void
    {
        $usuario = User::factory()->create([
            'password' => Hash::make('Anterior123'),
        ]);

        $this->actingAs($usuario)
            ->from('/perfil/contrasena')
            ->put('/perfil/contrasena', [
                'actual' => 'Anterior123',
                'password' => '123',
                'password_confirmation' => '123',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue(
            Hash::check(
                'Anterior123',
                $usuario->fresh()->password
            )
        );
    }

    public function test_usuario_puede_cambiar_contrasena(): void
    {
        $usuario = User::factory()->create([
            'password' => Hash::make('Anterior123'),
        ]);

        $this->actingAs($usuario)
            ->put('/perfil/contrasena', [
                'actual' => 'Anterior123',
                'password' => 'Nueva1234',
                'password_confirmation' => 'Nueva1234',
            ])
            ->assertRedirect('/perfil/contrasena')
            ->assertSessionHas(
                'exito',
                'Contraseña actualizada.'
            );

        $this->assertTrue(
            Hash::check(
                'Nueva1234',
                $usuario->fresh()->password
            )
        );

        $this->assertFalse(
            Hash::check(
                'Anterior123',
                $usuario->fresh()->password
            )
        );

        $this->post('/logout')
            ->assertRedirect('/');

        $this->assertGuest();

        $this->post('/login', [
            'email' => $usuario->email,
            'password' => 'Nueva1234',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($usuario);
    }
}
