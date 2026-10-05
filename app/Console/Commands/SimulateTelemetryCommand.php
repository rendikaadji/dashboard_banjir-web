<?php

namespace App\Console\Commands;

use App\Models\Node;
use App\Models\Reading;
use App\Services\Alert\AlertEngineService;
use App\Services\Telemetry\TelemetryIngestionService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SimulateTelemetryCommand extends Command
{
    protected $signature = 'fews:simulate 
                            {--stream : Jalankan streaming data sensor kontinu}
                            {--scenario= : Jalankan skenario uji keselamatan tertentu (S-01 s/d S-12)}
                            {--interval=2 : Interval detik antar pengiriman}';

    protected $description = 'Simulasi node telemetri IoT dan pengujian 12 skenario keselamatan TCE FloodGuard';

    public function handle(TelemetryIngestionService $ingestionService, AlertEngineService $alertEngine): int
    {
        $scenario = $this->option('scenario');
        $isStream = $this->option('stream');
        $interval = (int) $this->option('interval');

        if ($scenario) {
            return $this->runSafetyScenario($scenario, $ingestionService, $alertEngine);
        }

        if ($isStream) {
            $this->info("🚀 Memulai streaming telemetri multi-node (interval: {$interval}s)... Tekan Ctrl+C untuk berhenti.");
            $seq = 1000;

            while (true) {
                $nodes = Node::where('status', Node::STATUS_ACTIVE)->get();
                $now = Carbon::now();

                foreach ($nodes as $node) {
                    $payload = $this->generateRealisticPayload($node, $seq++, $now);
                    $result = $ingestionService->ingest($payload);

                    $waterLevel = $result['reading']->water_level_cm ?? $result['reading']->flow_velocity_ms ?? $result['reading']->rainfall_mm ?? 0;
                    $statusIcon = $result['alert'] ? '🚨 [ALERT L'.$result['alert']->level.']' : '✅ [OK]';

                    $this->line(sprintf(
                        '[%s] Node: %-10s | Nilai: %6.1f | Seq: %5d | %s',
                        $now->format('H:i:s'),
                        $node->node_id,
                        $waterLevel,
                        $seq,
                        $statusIcon
                    ));
                }

                sleep($interval);
            }
        }

        $this->info('Gunakan opsi --stream untuk streaming live atau --scenario=S-01 untuk uji skenario.');

        return self::SUCCESS;
    }

    /**
     * Jalankan skenario uji keselamatan PRD §18.2.
     */
    protected function runSafetyScenario(string $scenarioCode, TelemetryIngestionService $ingestionService, AlertEngineService $alertEngine): int
    {
        $code = strtoupper(trim($scenarioCode));
        $this->info("🧪 Menjalankan Skenario Keselamatan: {$code}...");

        $nodeCritical = Node::where('node_id', 'WL-09-01')->first();
        $nodeNormal = Node::where('node_id', 'WL-09-02')->first();

        if (! $nodeCritical || ! $nodeNormal) {
            $this->error('Node simulasi WL-09-01 atau WL-09-02 tidak ditemukan di database. Jalankan seeder dahulu.');

            return self::FAILURE;
        }

        $now = Carbon::now();

        switch ($code) {
            case 'S-02':
                // Spike tunggal 200 cm (harus ditolak oleh spike rejection)
                $this->line('1. Mengirim baseline 60 cm...');
                $ingestionService->ingest($this->buildPayload($nodeCritical, 1, 60.0, false, $now));

                $this->line('2. Mengirim spike mendadak 200 cm (lonjakan > 50 cm)...');
                $result = $ingestionService->ingest($this->buildPayload($nodeCritical, 2, 200.0, false, $now->copy()->addSecond()));

                if ($result['reading']->quality === 'SPIKE_REJECTED' && $result['alert'] === null) {
                    $this->info('✅ PASSED: Spike berhasil ditolak (quality: SPIKE_REJECTED, 0 alert).');
                } else {
                    $this->error('❌ FAILED: Spike gagal ditolak.');
                }
                break;

            case 'S-04':
                // Satu node non-critical 160 cm, peer node 70 cm -> Tidak blast warga, pemicu SYS-SENSOR-FAULT
                $this->line('1. Mengirim data normal pada peer node (70 cm)...');
                $ingestionService->ingest($this->buildPayload($nodeCritical, 10, 70.0, false, $now));

                $this->line('2. Mengirim data kritis 160 cm pada node NON-CRITICAL (WL-09-02)...');
                // Reset cache sustain untuk simulasi instan
                Cache::put("fews_sustain_{$nodeNormal->zone_id}_{$nodeNormal->node_id}_3", $now->timestamp - 35, 120);

                $result = $ingestionService->ingest($this->buildPayload($nodeNormal, 11, 160.0, false, $now->copy()->addSeconds(35)));

                if ($result['alert'] === null) {
                    $this->info('✅ PASSED: Multi-node corroboration berhasil menahan blast warga untuk node non-kritis.');
                } else {
                    $this->error('❌ FAILED: Alert Level 3 terpicu tanpa konfirmasi sensor tetangga.');
                }
                break;

            case 'S-05':
                // Node kritis 160 cm sendirian -> Blast warga karena is_critical = true
                $this->line('1. Mengirim data 160 cm pada node KRITIS (WL-09-01)...');
                Cache::put("fews_sustain_{$nodeCritical->zone_id}_{$nodeCritical->node_id}_3", $now->timestamp - 35, 120);

                $result = $ingestionService->ingest($this->buildPayload($nodeCritical, 20, 160.0, false, $now->copy()->addSeconds(35)));

                if ($result['alert'] && $result['alert']->level === 3) {
                    $this->info('✅ PASSED: Node kritis berhasil memicu Level 3 langsung (is_critical = true).');
                } else {
                    $this->error('❌ FAILED: Node kritis gagal memicu alert Level 3.');
                }
                break;

            case 'S-07':
                // Reconnect mengirim 300 data buffered lama (is_buffered = true) -> 0 alert
                $this->line('1. Mengirim data buffered 180 cm dengan is_buffered = true...');
                $result = $ingestionService->ingest($this->buildPayload($nodeCritical, 30, 180.0, true, $now));

                if ($result['alert'] === null && $result['reading']->is_buffered === true) {
                    $this->info('✅ PASSED: Data buffered diarsipkan tanpa memicu alert (0 alert baru).');
                } else {
                    $this->error('❌ FAILED: Data buffered memicu alert.');
                }
                break;

            default:
                $this->line("Skenario {$code} siap diuji melalui test suite PHPUnit (php artisan test).");
                break;
        }

        return self::SUCCESS;
    }

    protected function generateRealisticPayload(Node $node, int $seq, Carbon $now): array
    {
        $lastReading = Reading::where('node_id', $node->node_id)->latest('ts')->first();
        $lastLevel = $lastReading?->water_level_cm ?? 50.0;

        // Simulasi fluktuasi air tenang ±1 cm
        $delta = (mt_rand(-10, 10) / 10.0);
        $newLevel = max(10.0, min(180.0, $lastLevel + $delta));

        return [
            'node_id' => $node->node_id,
            'ts' => $now->toIso8601String(),
            'seq' => $seq,
            'fw' => '1.4.2',
            'readings' => [
                'water_level_cm' => ($node->node_type === Node::TYPE_WATER_LEVEL) ? $newLevel : null,
                'flow_velocity_ms' => ($node->node_type === Node::TYPE_FLOW) ? 0.35 : null,
                'rain_rate_mmh' => ($node->node_type === Node::TYPE_RAINFALL) ? 0.0 : null,
            ],
            'quality' => [
                'status' => 'OK',
            ],
            'power' => [
                'battery_v' => 12.65,
                'solar_v' => 18.2,
                'charging' => true,
            ],
            'net' => [
                'rssi' => -68,
            ],
            'buffered' => false,
        ];
    }

    protected function buildPayload(Node $node, int $seq, float $waterLevel, bool $isBuffered, Carbon $ts): array
    {
        return [
            'node_id' => $node->node_id,
            'ts' => $ts->toIso8601String(),
            'seq' => $seq,
            'readings' => [
                'water_level_cm' => $waterLevel,
            ],
            'quality' => ['status' => 'OK'],
            'power' => ['battery_v' => 12.60],
            'net' => ['rssi' => -65],
            'buffered' => $isBuffered,
        ];
    }
}
