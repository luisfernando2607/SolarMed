<?php

namespace Tests\Feature\Clinica;

use Tests\TestCase;

class CabecerasSeguridadTest extends TestCase
{
    public function test_las_paginas_envian_cabeceras_de_seguridad(): void
    {
        $r = $this->get('/login')->assertOk();

        $r->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $r->assertHeader('X-Content-Type-Options', 'nosniff');
        $r->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertStringContainsString("frame-ancestors 'self'", $r->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString("object-src 'none'", $r->headers->get('Content-Security-Policy'));
        $this->assertFalse($r->headers->has('Strict-Transport-Security'), 'HSTS solo con HTTPS');
    }
}
