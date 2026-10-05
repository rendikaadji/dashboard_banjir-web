<?php

namespace App\Services\Telemetry;

class DerivedMetricsService
{
    /**
     * Hitung laju kenaikan air (cm/jam).
     */
    public function calculateRiseRate(?float $currentLevel, ?float $previousLevel, ?\DateTimeInterface $currentTs, ?\DateTimeInterface $previousTs): ?float
    {
        if ($currentLevel === null || $previousLevel === null || $currentTs === null || $previousTs === null) {
            return 0.0;
        }

        $secondsDiff = abs($currentTs->getTimestamp() - $previousTs->getTimestamp());
        if ($secondsDiff <= 0) {
            return 0.0;
        }

        $deltaLevel = $currentLevel - $previousLevel;
        $hours = $secondsDiff / 3600.0;

        $rate = $deltaLevel / $hours;

        // Dibatasi rentang wajar -100 s/d +200 cm/jam
        return round(max(-100.0, min(200.0, $rate)), 2);
    }

    /**
     * Hitung Status Perahu Karet (PRD §10.4).
     *
     * @return array{code: string, label: string, color: string, badge_class: string, desc: string}
     */
    public function calculateBoatStatus(?float $waterLevelCm, ?float $flowVelocityMs = 0.0): array
    {
        $level = $waterLevelCm ?? 0.0;
        $velocity = $flowVelocityMs ?? 0.0;

        if ($velocity >= 1.50) {
            return [
                'code' => 'DANGEROUS_CURRENT',
                'label' => 'BAHAYA ARUS',
                'color' => 'red',
                'badge_class' => 'bg-red-500/10 text-red-500 border border-red-500/20',
                'desc' => 'Arus air terlalu kencang (≥ 1,5 m/s), perahu berisiko terbalik.',
            ];
        }

        if ($level < 40.0) {
            return [
                'code' => 'UNSAFE_SHALLOW',
                'label' => 'JANGAN TURUNKAN',
                'color' => 'red',
                'badge_class' => 'bg-red-500/10 text-red-500 border border-red-500/20',
                'desc' => 'Air terlalu dangkal (< 40 cm), risiko lambung & propeller perahu rusak.',
            ];
        }

        if ($level >= 40.0 && $level <= 60.0) {
            return [
                'code' => 'CAUTION',
                'label' => 'HATI-HATI',
                'color' => 'yellow',
                'badge_class' => 'bg-yellow-500/10 text-yellow-500 border border-yellow-500/20',
                'desc' => 'Kedalaman sedang (40–60 cm), cukup untuk perahu kosong atau berbeban ringan.',
            ];
        }

        return [
            'code' => 'SAFE_TO_LAUNCH',
            'label' => 'AMAN DITURUNKAN',
            'color' => 'green',
            'badge_class' => 'bg-emerald-500/10 text-emerald-500 border border-emerald-500/20',
            'desc' => 'Kedalaman (> 60 cm) dan kecepatan arus aman untuk evakuasi warga.',
        ];
    }

    /**
     * Hitung Flood Risk Index (FRI) skala 0–100 (PRD §8.3).
     */
    public function calculateFloodRiskIndex(
        float $waterLevelCm = 0.0,
        float $riseRateCmh = 0.0,
        float $rainRateMmh = 0.0,
        float $flowVelocityMs = 0.0,
        float $upstreamLevelCm = 0.0
    ): float {
        // Normalisasi
        $sLevel = max(0.0, min(1.0, $waterLevelCm / 150.0));
        $sRise = max(0.0, min(1.0, max(0.0, $riseRateCmh) / 40.0));
        $sRain = max(0.0, min(1.0, $rainRateMmh / 50.0));
        $sFlow = max(0.0, min(1.0, $flowVelocityMs / 2.0));
        $sUpstream = max(0.0, min(1.0, $upstreamLevelCm / 150.0));

        // Bobot PRD §8.3
        $fri = 100.0 * (
            (0.40 * $sLevel) +
            (0.25 * $sRise) +
            (0.15 * $sRain) +
            (0.10 * $sFlow) +
            (0.10 * $sUpstream)
        );

        return round(max(0.0, min(100.0, $fri)), 1);
    }
}
