<?php

namespace Tests\Unit;

use App\Models\Node;
use App\Services\Telemetry\DerivedMetricsService;
use App\Services\Telemetry\EwmaFilterService;
use App\Services\Telemetry\UnitConversionService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class EwmaAndConversionTest extends TestCase
{
    public function test_unit_conversion_formula(): void
    {
        $service = new UnitConversionService;
        $node = new Node([
            'mount_height_cm' => 220.0,
            'calib_offset_cm' => -1.8,
            'min_valid_cm' => 5.0,
            'max_valid_cm' => 240.0,
        ]);

        // Contoh PRD §23.3:
        // mount_height = 220, distance = 132.4, offset = -1.8
        // water_level = 220 - 132.4 + (-1.8) = 85.8 cm
        $result = $service->convertWaterLevel($node, 132.4);

        $this->assertEquals('OK', $result['quality']);
        $this->assertEquals(85.8, $result['water_level_cm']);
    }

    public function test_ewma_calculation(): void
    {
        $service = new EwmaFilterService;

        // Nilai pertama
        $ewma1 = $service->calculate(100.0, null, 0.30);
        $this->assertEquals(100.0, $ewma1);

        // Nilai kedua (current = 110.0, prev = 100.0, alpha = 0.30)
        // 0.3 * 110 + 0.7 * 100 = 33 + 70 = 103.0
        $ewma2 = $service->calculate(110.0, 100.0, 0.30);
        $this->assertEquals(103.0, $ewma2);
    }

    public function test_spike_rejection(): void
    {
        $service = new EwmaFilterService;

        // Lonjakan 60 cm dalam 1 pembacaan (prev = 60, current = 120, delta = 60 >= 50)
        $this->assertTrue($service->isSpike(120.0, 60.0, 50.0));

        // Perubahan wajar 5 cm
        $this->assertFalse($service->isSpike(65.0, 60.0, 50.0));
    }

    public function test_boat_status_matrix(): void
    {
        $service = new DerivedMetricsService;

        // TMA < 40 cm -> UNSAFE_SHALLOW
        $statusShallow = $service->calculateBoatStatus(35.0, 0.2);
        $this->assertEquals('UNSAFE_SHALLOW', $statusShallow['code']);

        // TMA 40–60 cm -> CAUTION
        $statusCaution = $service->calculateBoatStatus(50.0, 0.3);
        $this->assertEquals('CAUTION', $statusCaution['code']);

        // TMA > 60 cm dan arus < 1.5 m/s -> SAFE_TO_LAUNCH
        $statusSafe = $service->calculateBoatStatus(80.0, 0.5);
        $this->assertEquals('SAFE_TO_LAUNCH', $statusSafe['code']);

        // Arus >= 1.5 m/s -> DANGEROUS_CURRENT
        $statusDangerous = $service->calculateBoatStatus(90.0, 1.6);
        $this->assertEquals('DANGEROUS_CURRENT', $statusDangerous['code']);
    }

    public function test_rise_rate_calculation(): void
    {
        $service = new DerivedMetricsService;
        $t1 = Carbon::parse('2026-08-27 10:00:00');
        $t2 = Carbon::parse('2026-08-27 10:30:00'); // 30 menit = 0.5 jam

        // Kenaikan 10 cm dalam 30 menit = 20 cm/jam
        $rate = $service->calculateRiseRate(70.0, 60.0, $t2, $t1);
        $this->assertEquals(20.0, $rate);
    }
}
