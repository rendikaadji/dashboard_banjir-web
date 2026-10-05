<?php

namespace App\Services\Telemetry;

use App\Models\Alert;
use App\Models\Node;
use App\Models\Reading;
use App\Services\Alert\AlertEngineService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TelemetryIngestionService
{
    public function __construct(
        protected UnitConversionService $conversionService,
        protected EwmaFilterService $ewmaService,
        protected DerivedMetricsService $derivedService,
        protected AlertEngineService $alertEngine,
    ) {}

    /**
     * Proses payload telemetri masuk sesuai pipeline PRD §8.1.
     *
     * @param  array<string, mixed>  $payload  Payload JSON dari node atau simulator
     * @return array{success: bool, message: string, reading?: Reading, alert?: ?Alert}
     */
    public function ingest(array $payload): array
    {
        // 1. Validasi Schema Dasar
        $nodeId = $payload['node_id'] ?? null;
        if (! $nodeId) {
            return ['success' => false, 'message' => 'Schema error: node_id wajib diisi'];
        }

        // 2. Auth & Identity
        $node = Node::where('node_id', $nodeId)->first();
        if (! $node) {
            return ['success' => false, 'message' => "Node [{$nodeId}] tidak terdaftar"];
        }

        if ($node->status === Node::STATUS_RETIRED) {
            return ['success' => false, 'message' => "Node [{$nodeId}] berstatus RETIRED"];
        }

        $now = isset($payload['ts']) ? Carbon::parse($payload['ts']) : Carbon::now();
        $seq = $payload['seq'] ?? null;

        // 3. Deduplikasi (node_id, seq)
        if ($seq !== null) {
            $exists = Reading::where('node_id', $nodeId)->where('seq', $seq)->exists();
            if ($exists) {
                return ['success' => false, 'message' => "Duplikat sequence [{$seq}] diabaikan"];
            }
        }

        // Ambil pembacaan terakhir dari node ini
        $lastReading = Reading::where('node_id', $nodeId)->latest('ts')->first();
        $previousEwma = $lastReading?->water_level_ewma;
        $previousLevel = $lastReading?->water_level_cm;
        $previousTs = $lastReading?->ts;

        $readings = $payload['readings'] ?? [];
        $distanceCm = isset($readings['distance_cm']) ? (float) $readings['distance_cm'] : null;
        $waterLevelRaw = isset($readings['water_level_cm']) ? (float) $readings['water_level_cm'] : null;
        $flowLpm = isset($readings['flow_lpm']) ? (float) $readings['flow_lpm'] : null;
        $flowVelocityMs = isset($readings['flow_velocity_ms']) ? (float) $readings['flow_velocity_ms'] : null;
        $rainfallMm = isset($readings['rainfall_mm']) ? (float) $readings['rainfall_mm'] : null;
        $rainRateMmh = isset($readings['rain_rate_mmh']) ? (float) $readings['rain_rate_mmh'] : null;
        $windSpeedMs = isset($readings['wind_speed_ms']) ? (float) $readings['wind_speed_ms'] : null;

        $quality = $payload['quality']['status'] ?? 'OK';
        $isBuffered = (bool) ($payload['buffered'] ?? false);

        // 6. Unit Conversion jika distance_cm diberikan
        if ($distanceCm !== null && $waterLevelRaw === null) {
            $conv = $this->conversionService->convertWaterLevel($node, $distanceCm);
            $waterLevelRaw = $conv['water_level_cm'];
            if ($conv['quality'] !== 'OK') {
                $quality = $conv['quality'];
            }
        }

        // 5. Spike Rejection (PRD §8.1 step 5 & S-02)
        if ($waterLevelRaw !== null && $this->ewmaService->isSpike($waterLevelRaw, $previousEwma, 50.0)) {
            $quality = 'SPIKE_REJECTED';
            Log::warning("Spike terdeteksi pada node {$nodeId}: nilai {$waterLevelRaw} cm (EWMA sebelumnya: {$previousEwma} cm)");
        }

        // 7. EWMA Smoothing (hanya jika bukan spike dan bukan out of range)
        $waterLevelEwma = null;
        if ($waterLevelRaw !== null && $quality !== 'SPIKE_REJECTED' && $quality !== 'OUT_OF_RANGE') {
            $waterLevelEwma = $this->ewmaService->calculate($waterLevelRaw, $previousEwma);
        } else {
            $waterLevelEwma = $previousEwma;
        }

        // 8. Derived Metrics (Laju Kenaikan)
        $riseRateCmh = 0.0;
        if ($waterLevelRaw !== null && $quality === 'OK') {
            $riseRateCmh = $this->derivedService->calculateRiseRate($waterLevelRaw, $previousLevel, $now, $previousTs);
        }

        $power = $payload['power'] ?? [];
        $batteryV = isset($power['battery_v']) ? (float) $power['battery_v'] : null;
        $solarV = isset($power['solar_v']) ? (float) $power['solar_v'] : null;
        $rssi = isset($payload['net']['rssi']) ? (int) $payload['net']['rssi'] : null;

        // 9. Simpan ke database
        $reading = Reading::create([
            'ts' => $now,
            'node_id' => $nodeId,
            'seq' => $seq,
            'distance_cm' => $distanceCm,
            'water_level_cm' => $waterLevelRaw,
            'water_level_ewma' => $waterLevelEwma,
            'rise_rate_cmh' => $riseRateCmh,
            'flow_lpm' => $flowLpm,
            'flow_velocity_ms' => $flowVelocityMs,
            'rainfall_mm' => $rainfallMm,
            'rain_rate_mmh' => $rainRateMmh,
            'wind_speed_ms' => $windSpeedMs,
            'battery_v' => $batteryV,
            'solar_v' => $solarV,
            'rssi' => $rssi,
            'quality' => $quality,
            'is_buffered' => $isBuffered,
        ]);

        // Perbarui status node dan last_seen_at
        $node->update([
            'last_seen_at' => $now,
            'status' => Node::STATUS_ACTIVE,
        ]);

        // 10. Evaluasi Alert Decision Engine
        $alert = null;
        if ($quality === 'OK' || $quality === 'NOISY') {
            $alert = $this->alertEngine->evaluate($reading, $node);
        }

        // 11. Simpan payload terkini di cache untuk broadcast stream realtime super cepat
        $this->pushRealtimeEvent($node, $reading, $alert);

        return [
            'success' => true,
            'message' => 'Telemetri berhasil diproses',
            'reading' => $reading,
            'alert' => $alert,
        ];
    }

    /**
     * Simpan event realtime di cache untuk Server-Sent Events (SSE).
     */
    protected function pushRealtimeEvent(Node $node, Reading $reading, ?Alert $alert): void
    {
        $eventData = [
            'type' => 'reading.new',
            'node_id' => $node->node_id,
            'label' => $node->label,
            'zone' => $node->zone?->code,
            'ts' => $reading->ts->toIso8601String(),
            'water_level_cm' => $reading->water_level_cm,
            'water_level_ewma' => $reading->water_level_ewma,
            'rise_rate_cmh' => $reading->rise_rate_cmh,
            'battery_v' => $reading->battery_v,
            'quality' => $reading->quality,
            'alert_triggered' => $alert !== null,
            'alert_level' => $alert?->level,
        ];

        Cache::put('fews_latest_stream_event', $eventData, 30);
        Cache::put("fews_latest_node_{$node->node_id}", $eventData, 120);
    }
}
