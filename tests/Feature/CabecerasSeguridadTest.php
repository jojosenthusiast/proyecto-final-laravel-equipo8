<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CabecerasSeguridadTest extends TestCase
{
    use RefreshDatabase;

    public function test_pagina_principal_contiene_cabeceras(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertHeader(
                'X-Content-Type-Options',
                'nosniff'
            )
            ->assertHeader(
                'X-Frame-Options',
                'SAMEORIGIN'
            )
            ->assertHeader(
                'Referrer-Policy',
                'same-origin'
            );
    }

    public function test_login_contiene_cabeceras(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertHeader(
                'X-Content-Type-Options',
                'nosniff'
            )
            ->assertHeader(
                'X-Frame-Options',
                'SAMEORIGIN'
            )
            ->assertHeader(
                'Referrer-Policy',
                'same-origin'
            );
    }

    public function test_vehiculos_contiene_cabeceras(): void
    {
        $this->get('/vehiculos')
            ->assertOk()
            ->assertHeader(
                'X-Content-Type-Options',
                'nosniff'
            )
            ->assertHeader(
                'X-Frame-Options',
                'SAMEORIGIN'
            )
            ->assertHeader(
                'Referrer-Policy',
                'same-origin'
            );
    }
}
