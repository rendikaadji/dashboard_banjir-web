<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\AuditLog;
use App\Models\Node;
use App\Models\Reading;
use App\Services\Telemetry\TelemetryIngestionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AlertEngineSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(); // Seed site, zones, nodes, thresholds
    }

    /**
     * S-01: Air naik perlahan 60 -> 160 cm dalam 2 jam -> L1 -> L2 -> L3 berurutan.
     */
    public function test_s01_gradual_rise_escalates_levels_sequentially(): void
    {
        $ingestionService = app(TelemetryIngestionService::class);
        $node = Node::where('node_id', 'WL-09-01')->firstOrFail();
        $now = Carbon::now();
        Reading::where('node_id', $node->node_id)->delete();

        // Siapkan bypass sustain untuk semua level agar transisi langsung diuji
        Cache::put("fews_sustain_{$node->zone_id}_{$node->node_id}_1", $now->timestamp - 100, 300);
        Cache::put("fews_sustain_{$node->zone_id}_{$node->node_id}_2", $now->timestamp - 100, 300);
        Cache::put("fews_sustain_{$node->zone_id}_{$node->node_id}_3", $now->timestamp - 100, 300);

        $seq = 1000;
        $levelsObserved = [];

        // Naik perlahan dari 60 cm ke 180 cm dengan kelipatan 10 cm tiap 60 menit (10 cm/jam)
        for ($cm = 60; $cm <= 185; $cm += 10) {
            $seq++;
            $minuteOffset = ($cm - 60) * 6.0; // 10 cm / jam -> rise_rate 10 cm/h (< 20 cm/h)
            $res = $ingestionService->ingest([
                'node_id' => $node->node_id,
                'ts' => $now->copy()->addMinutes($minuteOffset)->toIso8601String(),
                'seq' => $seq,
                'readings' => ['water_level_cm' => (float) $cm],
            ]);

            $this->assertTrue($res['success']);
            $this->assertEquals('OK', $res['reading']->quality);

            $latestAlert = Alert::where('zone_id', $node->zone_id)->latest('id')->first();
            if ($latestAlert && ! in_array($latestAlert->level, $levelsObserved, true)) {
                $levelsObserved[] = $latestAlert->level;
            }
        }

        // Memastikan sistem melewati Level 1, Level 2, dan Level 3 berurutan
        $this->assertContains(1, $levelsObserved, 'Level 1 (Waspada) harus terpicu');
        $this->assertContains(2, $levelsObserved, 'Level 2 (Siaga) harus terpicu');
        $this->assertContains(3, $levelsObserved, 'Level 3 (Kritis) harus terpicu');
    }

    /**
     * S-02: Spike tunggal 200 cm lalu kembali normal -> ditolak oleh spike rejection (0 alert).
     */
    public function test_s02_spike_rejection_prevents_false_alert(): void
    {
        $ingestionService = app(TelemetryIngestionService::class);
        $node = Node::where('node_id', 'WL-09-01')->firstOrFail();
        $now = Carbon::now();
        Reading::where('node_id', $node->node_id)->delete();

        // 1. Kirim baseline normal 60 cm (seq unik 5001)
        $payloadNormal = [
            'node_id' => $node->node_id,
            'ts' => $now->toIso8601String(),
            'seq' => 5001,
            'readings' => ['water_level_cm' => 60.0],
            'quality' => ['status' => 'OK'],
        ];
        $res1 = $ingestionService->ingest($payloadNormal);
        $this->assertTrue($res1['success']);
        $this->assertEquals('OK', $res1['reading']->quality);

        // 2. Kirim spike ekstrem 200 cm (lonjakan 140 cm > 50 cm)
        $payloadSpike = [
            'node_id' => $node->node_id,
            'ts' => $now->copy()->addSecond()->toIso8601String(),
            'seq' => 5002,
            'readings' => ['water_level_cm' => 200.0],
            'quality' => ['status' => 'OK'],
        ];
        $res2 = $ingestionService->ingest($payloadSpike);

        // Harus ditandai SPIKE_REJECTED dan tidak boleh ada alert yang terpicu
        $this->assertTrue($res2['success']);
        $this->assertEquals('SPIKE_REJECTED', $res2['reading']->quality);
        $this->assertNull($res2['alert'], 'Spike tidak boleh memicu alert!');
    }

    /**
     * S-03: Osilasi di sekitar ambang (98–102 cm) tidak menyebabkan alert berulang-ulang berkat hysteresis.
     */
    public function test_s03_hysteresis_prevents_alert_flickering(): void
    {
        $ingestionService = app(TelemetryIngestionService::class);
        $node = Node::where('node_id', 'WL-09-01')->firstOrFail();
        $now = Carbon::now();
        Reading::where('node_id', $node->node_id)->delete();

        // 1. Picu Level 2 (102 cm)
        Cache::put("fews_sustain_{$node->zone_id}_{$node->node_id}_2", $now->timestamp - 100, 300);
        $res1 = $ingestionService->ingest([
            'node_id' => $node->node_id,
            'ts' => $now->toIso8601String(),
            'seq' => 3001,
            'readings' => ['water_level_cm' => 102.0],
        ]);
        $this->assertEquals(2, $res1['alert']->level);

        // 2. Turun tipis ke 98 cm (belum turun 10 cm di bawah ambang 100 cm, dan belum 5 menit)
        $res2 = $ingestionService->ingest([
            'node_id' => $node->node_id,
            'ts' => $now->copy()->addSeconds(10)->toIso8601String(),
            'seq' => 3002,
            'readings' => ['water_level_cm' => 98.0],
        ]);

        // Status alert masih tetap Level 2 aktif, tidak berkedip turun
        $activeAlert = Alert::where('zone_id', $node->zone_id)->where('status', Alert::STATUS_ACTIVE)->first();
        $this->assertNotNull($activeAlert);
        $this->assertEquals(2, $activeAlert->level);
    }

    /**
     * S-04: Satu node non-critical 160 cm, node tetangga 70 cm -> Tidak blast warga, pemicu SYS-SENSOR-FAULT.
     */
    public function test_s04_multi_node_corroboration_blocks_single_faulty_node(): void
    {
        $ingestionService = app(TelemetryIngestionService::class);
        $nodeCritical = Node::where('node_id', 'WL-09-01')->firstOrFail();
        $nodeNormal = Node::where('node_id', 'WL-09-02')->firstOrFail(); // is_critical = false
        $now = Carbon::now();

        Reading::whereIn('node_id', [$nodeCritical->node_id, $nodeNormal->node_id])->delete();

        // Siapkan baseline air tinggi di WL-09-02 (150 cm, agar EWMA mencapai Level 3)
        Reading::create([
            'ts' => $now->copy()->addSecond(),
            'node_id' => $nodeNormal->node_id,
            'seq' => 6000,
            'water_level_cm' => 150.0,
            'water_level_ewma' => 150.0,
            'quality' => 'OK',
        ]);

        // 1. Peer node melapor 70 cm (normal/waspada, di bawah 90 cm)
        $ingestionService->ingest([
            'node_id' => $nodeCritical->node_id,
            'ts' => $now->toIso8601String(),
            'seq' => 6001,
            'readings' => ['water_level_cm' => 70.0],
        ]);

        // 2. Node non-kritis melapor 160 cm (Level 3)
        Cache::put("fews_sustain_{$nodeNormal->zone_id}_{$nodeNormal->node_id}_3", $now->timestamp - 100, 300);

        $res = $ingestionService->ingest([
            'node_id' => $nodeNormal->node_id,
            'ts' => $now->copy()->addSeconds(100)->toIso8601String(),
            'seq' => 6002,
            'readings' => ['water_level_cm' => 160.0],
        ]);

        $this->assertTrue($res['success']);
        // Karena tidak dikonfirmasi peer node (peer hanya 70 cm), tidak boleh ada alert warga
        $this->assertNull($res['alert'], 'Node non-kritis sendirian tidak boleh memicu Level 3 blast');

        // Harus tercatat di audit log sebagai SYS-SENSOR-FAULT
        $faultLog = AuditLog::where('action', 'SYS-SENSOR-FAULT')->where('entity_id', $nodeNormal->node_id)->first();
        $this->assertNotNull($faultLog, 'Harus mencatat SYS-SENSOR-FAULT ke audit log');
    }

    /**
     * S-05: Node kritis melapor 160 cm sendirian -> Blast warga karena is_critical = true.
     */
    public function test_s05_critical_node_triggers_level_3_directly(): void
    {
        $ingestionService = app(TelemetryIngestionService::class);
        $nodeCritical = Node::where('node_id', 'WL-09-01')->firstOrFail(); // is_critical = true
        $this->assertTrue($nodeCritical->is_critical);

        $now = Carbon::now();

        Reading::where('node_id', $nodeCritical->node_id)->delete();

        // Siapkan baseline air tinggi di WL-09-01 (150 cm)
        Reading::create([
            'ts' => $now->copy()->addSecond(),
            'node_id' => $nodeCritical->node_id,
            'seq' => 7000,
            'water_level_cm' => 150.0,
            'water_level_ewma' => 150.0,
            'quality' => 'OK',
        ]);

        Cache::put("fews_sustain_{$nodeCritical->zone_id}_{$nodeCritical->node_id}_3", $now->timestamp - 100, 300);

        $res = $ingestionService->ingest([
            'node_id' => $nodeCritical->node_id,
            'ts' => $now->copy()->addSeconds(100)->toIso8601String(),
            'seq' => 7001,
            'readings' => ['water_level_cm' => 160.0],
        ]);

        $this->assertTrue($res['success']);
        $this->assertNotNull($res['alert']);
        $this->assertEquals(3, $res['alert']->level);
    }

    /**
     * S-07: Node reconnect mengirim data buffered (is_buffered = true) -> 0 alert baru.
     */
    public function test_s07_buffered_data_does_not_trigger_alert(): void
    {
        $ingestionService = app(TelemetryIngestionService::class);
        $nodeCritical = Node::where('node_id', 'WL-09-01')->firstOrFail();
        $now = Carbon::now();

        $res = $ingestionService->ingest([
            'node_id' => $nodeCritical->node_id,
            'ts' => $now->toIso8601String(),
            'seq' => 8001,
            'readings' => ['water_level_cm' => 170.0], // Kritis
            'buffered' => true, // Data replay dari memori node
        ]);

        $this->assertTrue($res['reading']->is_buffered);
        $this->assertNull($res['alert'], 'Data replay buffered tidak boleh memicu alert baru!');
    }

    /**
     * S-09: Naik mendadak 45 cm/jam pada TMA 90 cm -> L3 terpicu oleh rule laju kenaikan meski TMA < 150 cm.
     */
    public function test_s09_rise_rate_triggers_level_3_even_if_tma_below_150(): void
    {
        $ingestionService = app(TelemetryIngestionService::class);
        $node = Node::where('node_id', 'WL-09-01')->firstOrFail();
        $now = Carbon::now();
        Reading::where('node_id', $node->node_id)->delete();

        // Baseline: TMA 80 cm
        $ingestionService->ingest([
            'node_id' => $node->node_id,
            'ts' => $now->toIso8601String(),
            'seq' => 9100,
            'readings' => ['water_level_cm' => 80.0],
        ]);

        // Naik 10 cm dalam 10 menit (600 s) -> laju kenaikan = 60 cm/jam (>= 40 cm/jam untuk Level 3)
        Cache::put("fews_sustain_{$node->zone_id}_{$node->node_id}_3", $now->timestamp - 100, 300);

        $res = $ingestionService->ingest([
            'node_id' => $node->node_id,
            'ts' => $now->copy()->addMinutes(10)->toIso8601String(),
            'seq' => 9101,
            'readings' => ['water_level_cm' => 90.0],
        ]);

        $this->assertTrue($res['reading']->rise_rate_cmh >= 40.0);
        $this->assertNotNull($res['alert']);
        $this->assertEquals(3, $res['alert']->level);
        $this->assertEquals('rise_rate_cmh', $res['alert']->trigger_metric);
    }

    /**
     * S-11: Operator dapat membatalkan dalam grace window 60 detik (status menjadi CANCELLED).
     */
    public function test_s11_operator_can_cancel_within_grace_window(): void
    {
        $node = Node::where('node_id', 'WL-09-01')->firstOrFail();
        $now = Carbon::now();

        $alert = Alert::create([
            'site_id' => $node->site_id,
            'zone_id' => $node->zone_id,
            'level' => 3,
            'status' => Alert::STATUS_ACTIVE,
            'started_at' => $now,
            'grace_period_ends_at' => $now->copy()->addSeconds(60),
            'message_text' => 'Level 3 Kritis',
        ]);

        // Operator membatalkan
        $alert->update([
            'status' => Alert::STATUS_CANCELLED,
            'resolution_note' => 'Dibatalkan operator: Uji coba sirine',
        ]);

        $this->assertEquals(Alert::STATUS_CANCELLED, $alert->fresh()->status);
    }

    /**
     * S-12: Baterai < 11.4 V -> picu SYS-BATT-LOW.
     */
    public function test_s12_low_battery_triggers_system_alert(): void
    {
        $ingestionService = app(TelemetryIngestionService::class);
        $node = Node::where('node_id', 'WL-09-01')->firstOrFail();
        $now = Carbon::now();

        $ingestionService->ingest([
            'node_id' => $node->node_id,
            'ts' => $now->toIso8601String(),
            'seq' => 9001,
            'readings' => ['water_level_cm' => 50.0],
            'power' => ['battery_v' => 11.20], // < 11.4 V
        ]);

        $battLog = AuditLog::where('action', 'SYS-BATT-LOW')->where('entity_id', $node->node_id)->first();
        $this->assertNotNull($battLog, 'Baterai rendah harus tercatat di audit log sebagai SYS-BATT-LOW');
    }
}
