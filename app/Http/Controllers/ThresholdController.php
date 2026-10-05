<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Reading;
use App\Models\Threshold;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ThresholdController extends Controller
{
    /**
     * H-06: Pengaturan Ambang Batas & Simulasi Dry-Run
     */
    public function index(Request $request): View
    {
        $thresholds = Threshold::orderBy('level', 'asc')->get();

        // Simulasi hitungan alert 30 hari terakhir berdasarkan ambang saat ini
        $countL1 = Reading::where('water_level_cm', '>=', 70.0)->count();
        $countL2 = Reading::where('water_level_cm', '>=', 100.0)->count();
        $countL3 = Reading::where('water_level_cm', '>=', 150.0)->count();

        return view('thresholds.index', compact('thresholds', 'countL1', 'countL2', 'countL3'));
    }

    /**
     * Update Nilai Ambang Batas dengan Konfirmasi Ketik 'SIMPAN' (PRD §13.3 H-06)
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $threshold = Threshold::findOrFail($id);

        $validated = $request->validate([
            'value_low' => 'required|numeric|min:0|max:500',
            'sustain_seconds' => 'required|integer|min:0|max:600',
            'hysteresis_cm' => 'required|numeric|min:0|max:50',
            'cooldown_minutes' => 'required|integer|min:1|max:120',
            'confirm_keyword' => 'required|in:SIMPAN',
        ], [
            'confirm_keyword.in' => 'Konfirmasi gagal: Anda wajib mengetik kata "SIMPAN" untuk mengubah ambang keselamatan.',
        ]);

        $before = $threshold->toArray();
        $threshold->update([
            'value_low' => $validated['value_low'],
            'sustain_seconds' => $validated['sustain_seconds'],
            'hysteresis_cm' => $validated['hysteresis_cm'],
            'cooldown_minutes' => $validated['cooldown_minutes'],
        ]);

        AuditLog::create([
            'ts' => Carbon::now(),
            'user_id' => auth()->id() ?? 1,
            'action' => 'THRESHOLD_UPDATE',
            'entity' => 'thresholds',
            'entity_id' => (string) $threshold->id,
            'before_value' => $before,
            'after_value' => $threshold->toArray(),
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('thresholds.index')->with('success', "Ambang batas Level {$threshold->level} ({$threshold->metric}) berhasil diperbarui.");
    }
}
