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
     * Uji pemuatan landing publik, dashboard admin, dan kiosk.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);

        $this->get('/admin/dashboard')->assertStatus(200);
        $this->get('/admin/kiosk')->assertStatus(200);
        $this->get('/public')->assertRedirect('/');
    }
}
