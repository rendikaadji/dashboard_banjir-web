<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Node;
use App\Models\Site;
use App\Models\Threshold;
use App\Models\Zone;
use Carbon\Carbon;
use Illuminate\View\View;

class PublicController extends Controller
{
    // Data sensor lebih tua dari ini dianggap tidak tersedia; tidak boleh tampil sebagai AMAN (PRD §9)
    private const STALE_MINUTES = 15;

    // Posisi kepala pin sensor TMA pada gambar site plan 650x530 (x, y dari kiri-atas); sama dengan peta admin
    private const PIN_POSITIONS = [
        'WL-11-01' => [167, 41],
        'WL-06-02' => [118, 62],
        'WL-06-01' => [111, 149],
        'WL-09-01' => [260, 136],
        'WL-09-02' => [299, 141],
    ];

    /**
     * Landing page publik. Hanya level, TMA per wilayah, dan riwayat; tanpa ID/detail sensor atau data warga (PRD H-11).
     */
    public function landing(): View
    {
        // ACKNOWLEDGED masih aktif: banjir belum berakhir sebelum alert di-resolve
        $active = Alert::whereIn('status', [
            Alert::STATUS_TRIGGERED, Alert::STATUS_NOTIFYING, Alert::STATUS_ACTIVE, Alert::STATUS_ACKNOWLEDGED,
        ])->get();
        $level = (int) ($active->max('level') ?? 0);

        $zones = Zone::with('nodes.latestReading')->orderBy('code')->get()->map(function (Zone $z) use ($active) {
            $readings = $z->nodes->where('node_type', Node::TYPE_WATER_LEVEL)->map->latestReading->filter();
            $tma = $readings->max('water_level_cm');

            return [
                'id' => $z->id,
                'code' => str_replace('-', ' ', $z->code),
                'level' => (int) ($active->filter(fn ($a) => $a->zone_id === null || $a->zone_id === $z->id)->max('level') ?? 0),
                'tma' => $tma,
            ];
        });

        $waterNodes = Node::where('node_type', Node::TYPE_WATER_LEVEL)->with('latestReading', 'zone')->get();
        $lastDataAt = $waterNodes->map->latestReading->filter()->max('ts');
        $dataOk = $lastDataAt && $lastDataAt->gt(now()->subMinutes(self::STALE_MINUTES));

        // Satu lingkaran per wilayah (rata-rata posisi sensornya) agar target sentuh di HP tidak saling menimpa
        $pins = $waterNodes->filter(fn ($n) => isset(self::PIN_POSITIONS[$n->node_id]) && $n->zone)
            ->groupBy('zone_id')->map(function ($nodes) use ($zones) {
                $zone = $zones->firstWhere('id', $nodes->first()->zone_id);
                $xs = $nodes->map(fn ($n) => self::PIN_POSITIONS[$n->node_id][0]);
                $ys = $nodes->map(fn ($n) => self::PIN_POSITIONS[$n->node_id][1]);

                return [
                    'x' => round($xs->avg() / 650 * 100, 2),
                    'y' => round($ys->avg() / 530 * 100, 2),
                    'zone' => $zone['code'],
                    'level' => $zone['level'],
                    'tma' => $zone['tma'],
                ];
            })->values();

        $history = Alert::with('zone')
            ->whereNotNull('resolved_at')->where('is_false_alarm', false)
            ->latest('started_at')->limit(8)->get();

        // Batas level dari konfigurasi aktif (bukan angka tetap); dipakai untuk pita warna penggaris
        $bands = Threshold::where('metric', 'water_level_cm')->where('scope', 'GLOBAL')->where('is_active', true)
            ->orderBy('level')->pluck('value_low', 'level');
        $gaugeMax = max(200, (int) (ceil((float) ($bands->max() ?? 150) * 1.35 / 25) * 25));

        return view('landing', [
            'site' => Site::first(),
            'bands' => $bands,
            'gaugeMax' => $gaugeMax,
            'level' => $level,
            'dataOk' => $dataOk,
            'lastDataAt' => $lastDataAt?->timezone('Asia/Jakarta')->locale('id'),
            'highestTma' => $zones->max('tma'),
            'zones' => $zones,
            'pins' => $pins,
            'history' => $history,
            'sensorCounts' => Node::selectRaw('node_type, count(*) as total')->groupBy('node_type')->pluck('total', 'node_type'),
            'now' => Carbon::now('Asia/Jakarta')->locale('id'),
            'evacuationPoint' => config('services.public.evacuation_point'),
            'waGroupUrl' => config('services.public.wa_group_url'),
            'emergencyPhone' => config('services.public.emergency_phone'),
            'satgasPhone' => config('services.public.satgas_phone'),
        ]);
    }
}
