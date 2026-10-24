<?php

namespace App\Jobs;

use App\Models\AlertDelivery;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Throwable;

class SendWhatsAppMessage implements ShouldQueue
{
    use Queueable;

    // PRD §10.3: retry 3x dengan backoff 2 s / 10 s / 60 s
    public int $tries = 4;

    public function __construct(
        public int $deliveryId,
        public string $message,
    ) {}

    public function backoff(): array
    {
        return [2, 10, 60];
    }

    public function handle(): void
    {
        $delivery = AlertDelivery::with('alert')->findOrFail($this->deliveryId);

        if ($delivery->status !== AlertDelivery::STATUS_QUEUED) {
            return;
        }

        $response = Http::withHeaders(['Authorization' => config('services.fonnte.token')])
            ->timeout(10)
            ->asForm()
            ->post(config('services.fonnte.url'), [
                'target' => $delivery->target,
                'message' => $this->message,
                'countryCode' => '62',
            ]);

        // Fonnte membalas HTTP 200 meski gagal; status sebenarnya ada di body (kunci bisa "status"/"Status")
        $body = array_change_key_case($response->json() ?? [], CASE_LOWER);

        if (! $response->successful() || ($body['status'] ?? false) !== true) {
            $this->recordRetry($delivery);
            throw new \RuntimeException('Fonnte: '.($body['reason'] ?? "HTTP {$response->status()}"));
        }

        $now = now();
        $delivery->update([
            'status' => AlertDelivery::STATUS_SENT,
            'sent_at' => $now,
            // PRD §7: latency = trigger_at -> sent_at
            'latency_ms' => (int) max(0, ($delivery->alert->started_at ?? $delivery->queued_at)->diffInMilliseconds($now, true)),
            'error_code' => null,
        ]);
    }

    public function failed(Throwable $e): void
    {
        AlertDelivery::whereKey($this->deliveryId)->update([
            'status' => AlertDelivery::STATUS_FAILED,
            'error_code' => mb_substr($e->getMessage(), 0, 100),
        ]);
    }

    private function recordRetry(AlertDelivery $delivery): void
    {
        $delivery->update(['retry_count' => $this->attempts()]);
    }
}
