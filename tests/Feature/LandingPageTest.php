<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\Reading;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_stale_data_is_never_shown_as_safe(): void
    {
        Reading::query()->update(['ts' => now()->subHour()]);

        $this->get('/')->assertOk()->assertSee('Sensor belum mengirim data terbaru')
            ->assertDontSee('masih di bawah batas waspada');
    }

    public function test_fresh_data_shows_safe_and_hides_sensor_ids_and_phones(): void
    {
        Reading::query()->update(['ts' => now()]);

        $this->get('/')->assertOk()->assertSee('masih di bawah batas waspada')
            ->assertDontSee('WL-09-01')->assertDontSee('+62812');
    }

    public function test_active_alert_overrides_to_its_level_even_without_fresh_data(): void
    {
        Reading::query()->update(['ts' => now()->subHour()]);
        Alert::create(['level' => 3, 'status' => Alert::STATUS_ACKNOWLEDGED, 'started_at' => now()]);

        $this->get('/')->assertOk()->assertSee('Kritis');
    }

    public function test_admin_moved_and_old_public_url_redirects(): void
    {
        $this->get('/admin/dashboard')->assertOk();
        $this->get('/public')->assertRedirect('/');
    }
}
