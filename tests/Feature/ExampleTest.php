<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Uji pemuatan antarmuka dashboard utama, kiosk, dan publik warga.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);

        $kiosk = $this->get('/kiosk');
        $kiosk->assertStatus(200);

        $public = $this->get('/public');
        $public->assertStatus(200);
    }
}
