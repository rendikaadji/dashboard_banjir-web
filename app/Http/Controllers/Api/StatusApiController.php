<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Node;
use App\Models\Reading;
use App\Models\Zone;
use App\Services\Telemetry\DerivedMetricsService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StatusApiController extends Controller
{
    public function __construct(
        protected DerivedMetricsService $derivedService
    ) {}

    /**
     * Ringkasan status terkini kompleks dan per-zona (PRD §14.3).
     * GET /api/v1/status/current
     */
    public function current(): JsonResponse
    {
        $activeAlert = Alert::whereIn('status', [Alert::STATUS_ACTIVE, Alert::STATUS_NOTIFYING, Alert::STATUS_TRIGGERED])
            ->latest('id')
            ->first();

        $currentLevel = $activeAlert ? $activeAlert->level : 0;
        $levelLabels = [0 => 'AMAN', 1 => 'WASPADA', 2 => 'SIAGA', 3 => 'KRITIS'];

        // Ambil pembacaan terkini dari seluruh node level air, lalu cari TMA tertinggi saat ini
        $waterNodes = Node::where('node_type', Node::TYPE_WATER_LEVEL)
            ->with('latestReading')
            ->get();

        $highestNode = $waterNodes->sortByDesc(fn ($n) => $n->latestReading?->water_level_cm ?? 0)->first();
        $highestReading = $highestNode?->latestReading;

        $latestRain = Reading::join('nodes', 'readings.node_id', '=', 'nodes.node_id')
            ->where('nodes.node_type', Node::TYPE_RAINFALL)
            ->latest('readings.ts')
            ->first();

        $latestFlow = Reading::join('nodes', 'readings.node_id', '=', 'nodes.node_id')
            ->where('nodes.node_type', Node::TYPE_FLOW)
            ->latest('readings.ts')
            ->first();

        $highestTma = $highestReading ? (float) $highestReading->water_level_cm : 45.0;
        $flowVelocity = $latestFlow ? (float) $latestFlow->flow_velocity_ms : 0.2;
        $boatStatus = $this->derivedService->calculateBoatStatus($highestTma, $flowVelocity);

        // Status per zona
        $zones = Zone::with(['nodes.latestReading'])->get()->map(function ($zone) {
            $maxZoneTma = 0.0;
            $hasStaleNode = false;

            foreach ($zone->nodes as $node) {
                if ($node->latestReading && $node->latestReading->water_level_cm) {
                    $maxZoneTma = max($maxZoneTma, (float) $node->latestReading->water_level_cm);
                }
                if ($node->status !== Node::STATUS_ACTIVE) {
                    $hasStaleNode = true;
                }
            }

            $zoneLevel = 0;
            if ($maxZoneTma >= 150.0) {
                $zoneLevel = 3;
            } elseif ($maxZoneTma >= 100.0) {
                $zoneLevel = 2;
            } elseif ($maxZoneTma >= 70.0) {
                $zoneLevel = 1;
            }

            return [
                'zone' => $zone->code,
                'name' => $zone->name,
                'level' => $zoneLevel,
                'water_level_cm' => $maxZoneTma > 0 ? $maxZoneTma : null,
                'degraded' => $hasStaleNode,
            ];
        });

        $totalNodes = Node::count();
        $onlineNodes = Node::where('status', Node::STATUS_ACTIVE)->count();

        return response()->json([
            'site' => 'TCE',
            'site_name' => 'Taman Cipulir Estate',
            'level' => $currentLevel,
            'level_label' => $levelLabels[$currentLevel] ?? 'AMAN',
            'since' => $activeAlert ? $activeAlert->started_at->toIso8601String() : Carbon::now()->toIso8601String(),
            'active_alert_id' => $activeAlert?->id,
            'updated_at' => Carbon::now()->toIso8601String(),
            'highest' => [
                'node_id' => $highestReading?->node_id ?? 'WL-09-01',
                'label' => $highestReading?->label ?? 'Gorong-gorong Depan Masjid',
                'water_level_cm' => $highestTma,
                'rise_rate_cmh' => $highestReading ? (float) $highestReading->rise_rate_cmh : 0.0,
            ],
            'rain_rate_mmh' => $latestRain ? (float) $latestRain->rain_rate_mmh : 0.0,
            'max_flow_velocity_ms' => $flowVelocity,
            'boat_status' => $boatStatus,
            'zones' => $zones,
            'system' => [
                'mode' => 'NORMAL',
                'nodes_online' => $onlineNodes,
                'nodes_total' => $totalNodes,
            ],
        ]);
    }

    /**
     * Ringkasan status publik terbatas untuk warga (PRD §14.1 & H-11).
     * Tanpa memaparkan data sensitif atau nomor telepon.
     * GET /api/v1/public/status
     */
    public function publicStatus(): JsonResponse
    {
        $activeAlert = Alert::whereIn('status', [Alert::STATUS_ACTIVE, Alert::STATUS_NOTIFYING, Alert::STATUS_TRIGGERED])
            ->latest('id')
            ->first();

        $currentLevel = $activeAlert ? $activeAlert->level : 0;
        $levelLabels = [0 => 'AMAN', 1 => 'WASPADA', 2 => 'SIAGA', 3 => 'KRITIS'];

        $waterNodes = Node::where('node_type', Node::TYPE_WATER_LEVEL)->with('latestReading')->get();
        $highestNode = $waterNodes->sortByDesc(fn ($n) => $n->latestReading?->water_level_cm ?? 0)->first();
        $highestReading = $highestNode?->latestReading;

        $tma = $highestReading ? (float) $highestReading->water_level_cm : 45.0;
        $boatStatus = $this->derivedService->calculateBoatStatus($tma, 0.2);

        return response()->json([
            'site' => 'Taman Cipulir Estate (RW 08)',
            'level' => $currentLevel,
            'level_label' => $levelLabels[$currentLevel],
            'water_level_cm' => $tma,
            'boat_status' => $boatStatus['label'],
            'updated_at' => Carbon::now()->isoFormat('D MMMM Y, HH:mm:ss').' WIB',
            'emergency_contacts' => [
                'pos_satpam' => '021-7388XXXX',
                'satgas_banjir' => '0812-XXXX-XXXX',
            ],
            'evacuation_center' => 'Masjid Al-Ikhlas & Pos RW 08',
            'disclaimer' => 'Sistem ini adalah alat bantu kesiapsiagaan dini, bukan jaminan mutlak. Tetap ikuti arahan petugas lapangan.',
        ]);
    }

    /**
     * Stream Realtime Server-Sent Events (SSE) (PRD §14.2 & NFR-04).
     * Mengalirkan pembaruan data sensor seketika ke browser tanpa refresh!
     * GET /api/v1/stream
     */
    public function stream(Request $request): StreamedResponse
    {
        return new StreamedResponse(function () {
            // Ambil data pembacaan sensor terbaru dari semua node
            $nodes = Node::with('latestReading')->get()->map(function ($node) {
                return [
                    'node_id' => $node->node_id,
                    'label' => $node->label,
                    'type' => $node->node_type,
                    'water_level_cm' => $node->latestReading?->water_level_cm,
                    'water_level_ewma' => $node->latestReading?->water_level_ewma,
                    'rise_rate_cmh' => $node->latestReading?->rise_rate_cmh,
                    'battery_v' => $node->latestReading?->battery_v,
                    'status' => $node->status,
                    'last_seen' => $node->last_seen_at?->diffForHumans(),
                ];
            });

            $activeAlert = Alert::whereIn('status', [Alert::STATUS_ACTIVE, Alert::STATUS_NOTIFYING, Alert::STATUS_TRIGGERED])
                ->latest('id')
                ->first();

            $highestTma = $nodes->where('type', Node::TYPE_WATER_LEVEL)->max('water_level_cm') ?? 62.4;
            $boatStatus = $this->derivedService->calculateBoatStatus($highestTma, 0.2);

            $payload = [
                'ts' => Carbon::now()->toIso8601String(),
                'time_formatted' => Carbon::now()->format('H:i:s'),
                'level' => $activeAlert ? $activeAlert->level : 0,
                'highest_tma' => $highestTma,
                'boat_status' => $boatStatus,
                'nodes' => $nodes,
            ];

            // Kirim event telemetri dengan retry otomatis browser 2 detik (tidak mengunci worker single-thread)
            echo "retry: 2000\n";
            echo "event: telemetry\n";
            echo 'data: '.json_encode($payload)."\n\n";

            if (ob_get_level() > 0) {
                @ob_flush();
            }
            @flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
