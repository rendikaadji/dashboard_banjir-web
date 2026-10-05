<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Node;
use App\Models\Reading;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NodeController extends Controller
{
    /**
     * Daftar Semua Node Sensor IoT
     */
    public function index(): View
    {
        $nodes = Node::with(['zone', 'latestReading'])->get();

        return view('nodes.index', compact('nodes'));
    }

    /**
     * H-04: Detail Node, Grafik, & Form Kalibrasi
     */
    public function show(string $nodeId): View
    {
        $node = Node::where('node_id', $nodeId)->with(['zone', 'latestReading'])->firstOrFail();
        $recentReadings = Reading::where('node_id', $nodeId)
            ->latest('ts')
            ->limit(30)
            ->get();

        return view('nodes.show', compact('node', 'recentReadings'));
    }

    /**
     * Update Parameter Kalibrasi Node (PRD §12.1 FR-106)
     */
    public function update(Request $request, string $nodeId): RedirectResponse
    {
        $node = Node::where('node_id', $nodeId)->firstOrFail();

        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'mount_height_cm' => 'required|numeric|min:50|max:500',
            'calib_offset_cm' => 'required|numeric|min:-50|max:50',
            'min_valid_cm' => 'nullable|numeric|min:0',
            'max_valid_cm' => 'nullable|numeric|min:50',
            'is_critical' => 'nullable|boolean',
        ]);

        $before = $node->toArray();
        $node->update([
            'label' => $validated['label'],
            'mount_height_cm' => $validated['mount_height_cm'],
            'calib_offset_cm' => $validated['calib_offset_cm'],
            'min_valid_cm' => $validated['min_valid_cm'] ?? 5.0,
            'max_valid_cm' => $validated['max_valid_cm'] ?? 300.0,
            'is_critical' => $request->has('is_critical'),
        ]);

        AuditLog::create([
            'ts' => Carbon::now(),
            'user_id' => auth()->id() ?? 1,
            'action' => 'CALIBRATE_NODE',
            'entity' => 'nodes',
            'entity_id' => $node->node_id,
            'before_value' => $before,
            'after_value' => $node->toArray(),
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('nodes.show', $node->node_id)->with('success', 'Parameter kalibrasi node berhasil diperbarui.');
    }
}
