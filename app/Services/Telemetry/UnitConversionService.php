<?php

namespace App\Services\Telemetry;

use App\Models\Node;

class UnitConversionService
{
    /**
     * Konversi jarak sensor (distance_cm) ke tinggi muka air (water_level_cm).
     * Rumus PRD §23.3:
     * water_level_cm = mount_height_cm - distance_cm + calib_offset_cm
     *
     * @return array{water_level_cm: ?float, quality: string}
     */
    public function convertWaterLevel(Node $node, ?float $distanceCm): array
    {
        if ($distanceCm === null) {
            return ['water_level_cm' => null, 'quality' => 'OK'];
        }

        $mountHeight = (float) ($node->mount_height_cm ?? 220.0);
        $offset = (float) ($node->calib_offset_cm ?? 0.0);
        $minValid = (float) ($node->min_valid_cm ?? 5.0);
        $maxValid = (float) ($node->max_valid_cm ?? ($mountHeight + 20.0));

        // Cek validitas jarak
        if ($distanceCm < 10.0 || $distanceCm > ($mountHeight + 20.0)) {
            return [
                'water_level_cm' => null,
                'quality' => 'OUT_OF_RANGE',
            ];
        }

        $waterLevel = round($mountHeight - $distanceCm + $offset, 2);

        // Jika air di bawah dasar saluran (negatif akibat toleransi), normalkan ke 0
        if ($waterLevel < 0) {
            $waterLevel = 0.0;
        }

        if ($waterLevel < $minValid || $waterLevel > $maxValid) {
            return [
                'water_level_cm' => $waterLevel,
                'quality' => 'OUT_OF_RANGE',
            ];
        }

        return [
            'water_level_cm' => $waterLevel,
            'quality' => 'OK',
        ];
    }
}
