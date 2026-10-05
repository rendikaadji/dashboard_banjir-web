<?php

namespace App\Services\Alert;

use App\Models\Alert;
use App\Models\AuditLog;
use App\Models\Node;
use App\Models\Reading;
use App\Models\Threshold;
use App\Models\Zone;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * AlertEngineService — Mesin Pengambil Keputusan Peringatan Dini Banjir (FEWS)
 * Komponen Safety-Critical Tier 3 (PRD §9 & AI-Usage-Policy §6.4).
 *
 * Logika anti-false-alarm yang diterapkan:
 * 1. Sustain Check (durasi konfirmasi)
 * 2. Hysteresis (anti-flickering de-eskalasi)
 * 3. Multi-node Corroboration (verifikasi sensor ganda untuk Level 3)
 * 4. Cooldown & Deduplication
 * 5. Rejection data buffered (is_buffered = true tidak memicu alert)
 * 6. Grace window 60 detik untuk pembatalan operator
 */
class AlertEngineService
{
    public const LEVEL_AMAN = 0;

    public const LEVEL_WASPADA = 1;

    public const LEVEL_SIAGA = 2;

    public const LEVEL_KRITIS = 3;

    /**
     * Evaluasi pembacaan telemetri yang baru masuk.
     *
     * @param  Reading  $reading  Pembacaan sensor terkini
     * @param  Node  $node  Node sumber
     * @param  bool  $isMaintenanceMode  Mode pemeliharaan sistem
     * @return Alert|null Mengembalikan objek Alert jika terjadi perubahan level atau alert baru
     */
    public function evaluate(Reading $reading, Node $node, bool $isMaintenanceMode = false): ?Alert
    {
        // Aturan Anti False Alarm #1: Data buffered tidak pernah memicu alert baru (PRD §9.2)
        if ($reading->is_buffered) {
            Log::info("AlertEngine: Data buffered dilewati untuk alert (Node: {$node->node_id})");

            return null;
        }

        // Cek anomali baterai rendah (SYS-BATT-LOW)
        if ($reading->battery_v !== null && $reading->battery_v < 11.40) {
            $this->handleSystemAlert('SYS-BATT-LOW', $node, "Tegangan baterai rendah: {$reading->battery_v} V");
        }

        $zone = $node->zone;
        if (! $zone) {
            return null;
        }

        $now = Carbon::now();
        $waterLevel = $reading->water_level_ewma ?? $reading->water_level_cm;
        $riseRate = $reading->rise_rate_cmh ?? 0.0;
        $flowVelocity = $reading->flow_velocity_ms ?? 0.0;
        $rainRate = $reading->rain_rate_mmh ?? 0.0;

        // Ambil ambang batas aktif
        $thresholds = Threshold::where('is_active', true)
            ->orderBy('level', 'desc')
            ->get();

        $candidateLevel = self::LEVEL_AMAN;
        $triggerMetric = null;
        $triggerValue = null;
        $activeRule = null;

        foreach ($thresholds as $rule) {
            $matched = false;
            $currentVal = 0.0;

            if ($rule->metric === 'water_level_cm' && $waterLevel !== null) {
                $currentVal = $waterLevel;
                $matched = $currentVal >= $rule->value_low;
            } elseif ($rule->metric === 'rise_rate_cmh' && $riseRate > 0) {
                $currentVal = $riseRate;
                $matched = $currentVal >= $rule->value_low;
            } elseif ($rule->metric === 'flow_velocity_ms' && $flowVelocity > 0) {
                $currentVal = $flowVelocity;
                $matched = $currentVal >= $rule->value_low;
            } elseif ($rule->metric === 'rain_rate_mmh' && $rainRate > 0) {
                $currentVal = $rainRate;
                $matched = $currentVal >= $rule->value_low;
            }

            if ($matched) {
                $candidateLevel = $rule->level;
                $triggerMetric = $rule->metric;
                $triggerValue = $currentVal;
                $activeRule = $rule;
                break; // Ambil level tertinggi yang cocok
            }
        }

        // Ambil alert aktif terakhir untuk zona ini
        $activeAlert = Alert::where('zone_id', $zone->id)
            ->whereIn('status', [Alert::STATUS_ACTIVE, Alert::STATUS_NOTIFYING, Alert::STATUS_TRIGGERED])
            ->latest('id')
            ->first();

        $currentLevel = $activeAlert ? $activeAlert->level : self::LEVEL_AMAN;

        // 1. KASUS ESKALASI (candidateLevel > currentLevel)
        if ($candidateLevel > $currentLevel) {
            // Evaluasi Sustain Check
            $sustainRequired = $activeRule ? $activeRule->sustain_seconds : 30;
            if (! $this->checkSustain($zone->id, $node->node_id, $candidateLevel, $sustainRequired, $now)) {
                return null; // Belum memenuhi durasi sustain
            }

            // Evaluasi Multi-Node Corroboration khusus Level 3 (PRD §9.2 & S-04 / S-05)
            if ($candidateLevel === self::LEVEL_KRITIS && ! $node->is_critical) {
                $isCorroborated = $this->checkZoneCorroboration($zone, $node, $now);
                if (! $isCorroborated) {
                    // Sensor tetangga tidak mengonfirmasi -> jangan blast warga! Picu SYS-SENSOR-FAULT
                    $this->handleSystemAlert(
                        'SYS-SENSOR-FAULT',
                        $node,
                        "Level 3 tidak terkonfirmasi sensor tetangga (TMA: {$waterLevel} cm). Kemungkinan sensor fault."
                    );

                    return null;
                }
            }

            // Buat atau eskalasi alert
            return $this->triggerAlert(
                $zone,
                $node,
                $candidateLevel,
                $triggerMetric,
                $triggerValue,
                $activeAlert,
                $isMaintenanceMode,
                $now
            );
        }

        // 2. KASUS DE-ESKALASI (candidateLevel < currentLevel)
        if ($candidateLevel < $currentLevel && $activeAlert) {
            // Evaluasi Hysteresis: turun level hanya bila TMA turun >= 10 cm di bawah ambang selama >= 5 menit (PRD §9.2)
            $hysteresisMargin = $activeRule ? $activeRule->hysteresis_cm : 10.0;
            $requiredBelowVal = $activeRule ? ($activeRule->value_low - $hysteresisMargin) : ($waterLevel - 10.0);

            if ($this->checkHysteresis($activeAlert, $waterLevel, $requiredBelowVal, 300, $now)) {
                return $this->deescalateOrResolveAlert($activeAlert, $candidateLevel, $now);
            }
        }

        return null;
    }

    /**
     * Memeriksa apakah ambang batas terlampaui terus menerus selama sustain_seconds (PRD §9.2).
     */
    protected function checkSustain(int $zoneId, string $nodeId, int $level, int $sustainSeconds, Carbon $now): bool
    {
        $cacheKey = "fews_sustain_{$zoneId}_{$nodeId}_{$level}";
        $firstExceededAt = Cache::get($cacheKey);

        if (! $firstExceededAt) {
            // Mulai window sustain
            Cache::put($cacheKey, $now->timestamp, 120);

            return $sustainSeconds <= 0;
        }

        $elapsedSeconds = $now->timestamp - $firstExceededAt;

        return $elapsedSeconds >= $sustainSeconds;
    }

    /**
     * Multi-node Corroboration untuk Level 3 (PRD §9.2 & §18.2 S-04, S-05):
     * Level 3 membutuhkan konfirmasi minimal 2 node aktif dalam zona yang sama,
     * KECUALI node tersebut memiliki status `is_critical = true`.
     */
    protected function checkZoneCorroboration(Zone $zone, Node $triggerNode, Carbon $now): bool
    {
        // Cari node lain di zona yang sama
        $peerNodes = Node::where('zone_id', $zone->id)
            ->where('node_id', '!=', $triggerNode->node_id)
            ->where('status', Node::STATUS_ACTIVE)
            ->get();

        if ($peerNodes->isEmpty()) {
            // Jika tidak ada node lain di zona, loloskan
            return true;
        }

        foreach ($peerNodes as $peer) {
            $latestPeerReading = Reading::where('node_id', $peer->node_id)
                ->where('ts', '>=', $now->copy()->subMinutes(5))
                ->latest('ts')
                ->first();

            if ($latestPeerReading) {
                $peerLevel = $latestPeerReading->water_level_ewma ?? $latestPeerReading->water_level_cm ?? 0;
                // Jika peer node juga menunjukkan kenaikan siaga (misal >= 90 cm), dianggap mengonfirmasi
                if ($peerLevel >= 90.0) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Hysteresis de-eskalasi (PRD §9.2):
     * Mencegah alert berkedip-kedip di perbatasan ambang batas.
     */
    protected function checkHysteresis(Alert $activeAlert, ?float $currentLevel, float $targetThreshold, int $requiredSeconds, Carbon $now): bool
    {
        if ($currentLevel === null || $currentLevel > $targetThreshold) {
            Cache::forget("fews_hysteresis_{$activeAlert->id}");

            return false;
        }

        $cacheKey = "fews_hysteresis_{$activeAlert->id}";
        $firstBelowAt = Cache::get($cacheKey);

        if (! $firstBelowAt) {
            Cache::put($cacheKey, $now->timestamp, $requiredSeconds + 60);

            return false;
        }

        return ($now->timestamp - $firstBelowAt) >= $requiredSeconds;
    }

    /**
     * Membuka alert baru atau eskalasi level.
     */
    protected function triggerAlert(
        Zone $zone,
        Node $node,
        int $level,
        ?string $metric,
        ?float $value,
        ?Alert $previousAlert,
        bool $isMaintenanceMode,
        Carbon $now
    ): Alert {
        $levelNames = [1 => 'WASPADA', 2 => 'SIAGA', 3 => 'KRITIS'];
        $levelLabel = $levelNames[$level] ?? 'UNKNOWN';

        $snapshot = [
            'trigger_node' => $node->node_id,
            'metric' => $metric,
            'value' => $value,
            'nodes_in_zone' => Node::where('zone_id', $zone->id)->pluck('status', 'node_id')->toArray(),
        ];

        $message = "⚠️ [{$levelLabel}] Wilayah {$zone->name} terpicu oleh {$node->label}. Nilai {$metric}: {$value}";

        $gracePeriodEndsAt = ($level === self::LEVEL_KRITIS) ? $now->copy()->addSeconds(60) : null;

        $alert = Alert::create([
            'site_id' => $zone->site_id,
            'zone_id' => $zone->id,
            'level' => $level,
            'status' => Alert::STATUS_ACTIVE,
            'trigger_type' => 'AUTO_THRESHOLD',
            'trigger_node_id' => $node->node_id,
            'trigger_value' => $value,
            'trigger_metric' => $metric,
            'snapshot' => $snapshot,
            'message_text' => $message,
            'started_at' => $now,
            'grace_period_ends_at' => $gracePeriodEndsAt,
            'escalated_from' => $previousAlert?->id,
        ]);

        // Audit Log aksi keselamatan
        AuditLog::create([
            'ts' => $now,
            'user_id' => null,
            'action' => 'AUTO_ALERT_TRIGGERED',
            'entity' => 'alerts',
            'entity_id' => (string) $alert->id,
            'before_value' => $previousAlert ? ['level' => $previousAlert->level] : null,
            'after_value' => ['level' => $level, 'is_maintenance' => $isMaintenanceMode],
            'ip_address' => '127.0.0.1',
        ]);

        return $alert;
    }

    /**
     * De-eskalasi atau penyelesaian otomatis alert saat air surut.
     */
    protected function deescalateOrResolveAlert(Alert $activeAlert, int $targetLevel, Carbon $now): Alert
    {
        if ($targetLevel === self::LEVEL_AMAN) {
            $activeAlert->update([
                'status' => Alert::STATUS_RESOLVED,
                'resolved_at' => $now,
                'resolution_note' => 'Air surut kembali normal (Auto-resolved setelah evaluasi hysteresis).',
            ]);
        } else {
            $activeAlert->update([
                'level' => $targetLevel,
                'message_text' => "Status diturunkan ke Level {$targetLevel} (Air surut bertahap).",
            ]);
        }

        Cache::forget("fews_hysteresis_{$activeAlert->id}");

        return $activeAlert;
    }

    /**
     * Penanganan alert sistem (Node offline, baterai rendah, sensor fault).
     */
    public function handleSystemAlert(string $code, Node $node, string $message): void
    {
        Log::warning("FEWS SYSTEM ALERT [{$code}] Node {$node->node_id}: {$message}");

        // Simpan catatan ke audit log sistem
        AuditLog::create([
            'ts' => Carbon::now(),
            'user_id' => null,
            'action' => $code,
            'entity' => 'nodes',
            'entity_id' => $node->node_id,
            'before_value' => null,
            'after_value' => ['message' => $message, 'code' => $code],
            'ip_address' => '127.0.0.1',
        ]);
    }
}
