<?php

namespace App\Services\Telemetry;

class EwmaFilterService
{
    public const DEFAULT_ALPHA_WATER_LEVEL = 0.30;

    public const DEFAULT_ALPHA_FLOW = 0.20;

    public const DEFAULT_ALPHA_RAIN = 0.50;

    public const DEFAULT_ALPHA_WIND = 0.25;

    /**
     * Hitung nilai EWMA (PRD §8.2):
     * x_ewma(t) = α · x(t) + (1 - α) · x_ewma(t-1)
     */
    public function calculate(?float $currentVal, ?float $previousEwma, float $alpha = self::DEFAULT_ALPHA_WATER_LEVEL): ?float
    {
        if ($currentVal === null) {
            return $previousEwma;
        }

        if ($previousEwma === null) {
            return round($currentVal, 2);
        }

        return round(($alpha * $currentVal) + ((1.0 - $alpha) * $previousEwma), 2);
    }

    /**
     * Spike Rejection (PRD §8.1 step 5 & S-02):
     * Memeriksa lonjakan ekstrem sesaat yang tidak masuk akal secara fisis
     * (misalnya gelombang/pantulan sampah yang melonjak > 50 cm dalam 1 sampel).
     *
     * @param  float  $currentValue  Nilai saat ini
     * @param  float|null  $previousEwma  Nilai EWMA sebelumnya
     * @param  float  $maxDeltaJump  Batas lonjakan maksimum (default 50.0 cm)
     * @return bool True jika terdeteksi spike
     */
    public function isSpike(float $currentValue, ?float $previousEwma, float $maxDeltaJump = 50.0): bool
    {
        if ($previousEwma === null) {
            return false;
        }

        return abs($currentValue - $previousEwma) >= $maxDeltaJump;
    }
}
