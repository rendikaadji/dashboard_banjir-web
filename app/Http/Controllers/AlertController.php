<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AlertController extends Controller
{
    /**
     * H-05: Riwayat Alert & Detail Pengiriman
     */
    public function index(Request $request): View
    {
        $query = Alert::with(['zone', 'triggerNode', 'deliveries', 'acknowledgedByUser'])
            ->latest('started_at');

        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        if ($request->filled('zone_id')) {
            $query->where('zone_id', $request->zone_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $alerts = $query->paginate(15);
        $zones = Zone::all();

        return view('alerts.index', compact('alerts', 'zones'));
    }
}
