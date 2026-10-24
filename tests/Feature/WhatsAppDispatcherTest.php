<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\AlertDelivery;
use App\Models\Recipient;
use App\Services\Notification\WhatsAppDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppDispatcherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        config(['services.fonnte.token' => 'test-token', 'queue.default' => 'sync']);
        Recipient::query()->update(['is_active' => false]);
        Recipient::create(['full_name' => 'Warga A', 'phone_e164' => '+6281200000001', 'min_level' => 2, 'is_active' => true]);
    }

    public function test_sends_via_fonnte_and_marks_sent(): void
    {
        Http::fake(['api.fonnte.com/*' => Http::response(['status' => true])]);
        $alert = $this->makeAlert();

        app(WhatsAppDispatcher::class)->dispatch($alert);

        Http::assertSent(fn ($r) => $r->hasHeader('Authorization', 'test-token') && $r['target'] === '+6281200000001');
        $d = AlertDelivery::where('alert_id', $alert->id)->firstOrFail();
        $this->assertSame(AlertDelivery::STATUS_SENT, $d->status);
        $this->assertGreaterThan(0, $d->latency_ms);
    }

    public function test_provider_rejection_marks_failed(): void
    {
        Http::fake(['api.fonnte.com/*' => Http::response(['Status' => false, 'reason' => 'token invalid'])]);
        $alert = $this->makeAlert();

        try {
            app(WhatsAppDispatcher::class)->dispatch($alert);
        } catch (\Throwable) {
            // queue sync melempar setelah failed() dipanggil
        }

        $this->assertSame(AlertDelivery::STATUS_FAILED, AlertDelivery::where('alert_id', $alert->id)->firstOrFail()->status);
    }

    private function makeAlert(): Alert
    {
        return Alert::create([
            'zone_id' => null, 'level' => 3, 'status' => 'ACTIVE',
            'started_at' => now()->subSecond(), 'trigger_value' => 160,
        ]);
    }
}
