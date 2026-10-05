<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Node;
use App\Models\Reading;
use App\Models\Threshold;
use App\Models\Zone;
use App\Services\Telemetry\DerivedMetricsService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected DerivedMetricsService $derivedService
    ) {}

    /**
     * H-02: Dashboard Utama
     */
    public function index(): View
    {
        $activeAlert = Alert::whereIn('status', [Alert::STATUS_ACTIVE, Alert::STATUS_NOTIFYING, Alert::STATUS_TRIGGERED])
            ->latest('id')
            ->first();

        $currentLevel = $activeAlert ? $activeAlert->level : 0;
        $levelNames = [0 => 'AMAN', 1 => 'WASPADA', 2 => 'SIAGA', 3 => 'KRITIS'];
        $levelColors = [
            0 => ['bg' => 'bg-emerald-500/10', 'text' => 'text-emerald-500', 'border' => 'border-emerald-500/30', 'hex' => '#16A34A'],
            1 => ['bg' => 'bg-amber-500/10', 'text' => 'text-amber-500', 'border' => 'border-amber-500/30', 'hex' => '#EAB308'],
            2 => ['bg' => 'bg-orange-500/10', 'text' => 'text-orange-500', 'border' => 'border-orange-500/30', 'hex' => '#F97316'],
            3 => ['bg' => 'bg-rose-500/10', 'text' => 'text-rose-500', 'border' => 'border-rose-500/30', 'hex' => '#DC2626'],
        ];

        $nodes = Node::with('latestReading', 'zone')->get();

        $waterNodes = $nodes->where('node_type', Node::TYPE_WATER_LEVEL);
        $highestNode = $waterNodes->sortByDesc(fn ($n) => $n->latestReading?->water_level_cm ?? 0)->first();
        $highestTma = $highestNode?->latestReading?->water_level_cm ?? 45.0;

        $flowNode = $nodes->where('node_type', Node::TYPE_FLOW)->first();
        $flowVelocity = $flowNode?->latestReading?->flow_velocity_ms ?? 0.25;

        $rainNode = $nodes->where('node_type', Node::TYPE_RAINFALL)->first();
        $rainRate = $rainNode?->latestReading?->rain_rate_mmh ?? 0.0;

        $boatStatus = $this->derivedService->calculateBoatStatus($highestTma, $flowVelocity);

        $zones = Zone::with('nodes.latestReading')->get();
        $thresholds = Threshold::where('is_active', true)->get();

        // 20 pembacaan terakhir dari sensor utama untuk grafik
        $chartReadings = Reading::where('node_id', $highestNode?->node_id ?? 'WL-09-01')
            ->latest('ts')
            ->limit(24)
            ->get()
            ->reverse()
            ->values();

        return view('dashboard.index', compact(
            'activeAlert',
            'currentLevel',
            'levelNames',
            'levelColors',
            'nodes',
            'highestNode',
            'highestTma',
            'flowVelocity',
            'rainRate',
            'boatStatus',
            'zones',
            'thresholds',
            'chartReadings'
        ));
    }

    /**
     * H-03: Mode Kiosk Layar Pos Satpam (Terbaca dari 3 meter, font 72px+)
     */
    public function kiosk(): View
    {
        $activeAlert = Alert::whereIn('status', [Alert::STATUS_ACTIVE, Alert::STATUS_NOTIFYING, Alert::STATUS_TRIGGERED])
            ->latest('id')
            ->first();

        $currentLevel = $activeAlert ? $activeAlert->level : 0;
        $nodes = Node::with('latestReading')->get();
        $waterNodes = $nodes->where('node_type', Node::TYPE_WATER_LEVEL);
        $highestNode = $waterNodes->sortByDesc(fn ($n) => $n->latestReading?->water_level_cm ?? 0)->first();
        $highestTma = $highestNode?->latestReading?->water_level_cm ?? 45.0;

        $flowNode = $nodes->where('node_type', Node::TYPE_FLOW)->first();
        $flowVelocity = $flowNode?->latestReading?->flow_velocity_ms ?? 0.25;

        $boatStatus = $this->derivedService->calculateBoatStatus($highestTma, $flowVelocity);

        return view('dashboard.kiosk', compact(
            'activeAlert',
            'currentLevel',
            'highestNode',
            'highestTma',
            'boatStatus',
            'nodes'
        ));
    }

    /**
     * H-11: Halaman Publik Warga (Tanpa login, mobile-first)
     */
    public function publicStatus(): View
    {
        $activeAlert = Alert::whereIn('status', [Alert::STATUS_ACTIVE, Alert::STATUS_NOTIFYING, Alert::STATUS_TRIGGERED])
            ->latest('id')
            ->first();

        $currentLevel = $activeAlert ? $activeAlert->level : 0;
        $nodes = Node::where('node_type', Node::TYPE_WATER_LEVEL)->with('latestReading', 'zone')->get();
        $highestNode = $nodes->sortByDesc(fn ($n) => $n->latestReading?->water_level_cm ?? 0)->first();
        $highestTma = $highestNode?->latestReading?->water_level_cm ?? 45.0;

        $flowNode = Node::where('node_type', Node::TYPE_FLOW)->with('latestReading')->first();
        $flowVelocity = $flowNode?->latestReading?->flow_velocity_ms ?? 0.20;

        $boatStatus = $this->derivedService->calculateBoatStatus($highestTma, $flowVelocity);

        return view('dashboard.public', compact(
            'activeAlert',
            'currentLevel',
            'highestNode',
            'highestTma',
            'boatStatus'
        ));
    }
}
