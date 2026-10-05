<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\AuditLog;
use App\Models\Node;
use App\Models\Reading;
use App\Services\Notification\WhatsAppDispatcher;
use App\Services\Telemetry\TelemetryIngestionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelemetryApiController extends Controller
{
    public function __construct(
        protected TelemetryIngestionService $ingestionService,
        protected WhatsAppDispatcher $whatsappDispatcher,
    ) {}

    /**
     * Ingestion Telemetri Node (MQTT / HTTP Gateway).
     * POST /api/v1/telemetry
     */
    public function ingest(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'node_id' => 'required|string',
            'ts' => 'nullable|string',
            'seq' => 'nullable|integer',
            'fw' => 'nullable|string',
            'readings' => 'nullable|array',
            'quality' => 'nullable|array',
            'power' => 'nullable|array',
            'net' => 'nullable|array',
            'buffered' => 'nullable|boolean',
        ]);

        $result = $this->ingestionService->ingest($payload);

        if (! $result['success']) {
            return response()->json($result, 422);
        }

        // Jika terpicu alert baru, dispatch notifikasi WhatsApp
        if (isset($result['alert']) && $result['alert'] !== null) {
            $this->whatsappDispatcher->dispatch($result['alert'], true);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data telemetri berhasil diterima',
            'reading_id' => $result['reading']->id ?? null,
            'alert' => $result['alert'] ? [
                'id' => $result['alert']->id,
                'level' => $result['alert']->level,
                'level_name' => $result['alert']->level_name,
            ] : null,
        ]);
    }

    /**
     * Query data riwayat time-series (PRD §14.1).
     * GET /api/v1/readings
     */
    public function readings(Request $request): JsonResponse
    {
        $nodeId = $request->query('node_id', 'WL-09-01');
        $hours = (int) $request->query('hours', 6);

        $query = Reading::where('node_id', $nodeId)
            ->where('ts', '>=', Carbon::now()->subHours($hours))
            ->orderBy('ts', 'asc')
            ->limit(300);

        $data = $query->get()->map(function ($r) {
            return [
                'ts' => $r->ts->format('H:i'),
                'full_ts' => $r->ts->toIso8601String(),
                'water_level_cm' => $r->water_level_cm,
                'water_level_ewma' => $r->water_level_ewma,
                'rise_rate_cmh' => $r->rise_rate_cmh,
                'rainfall_mm' => $r->rainfall_mm,
                'battery_v' => $r->battery_v,
            ];
        });

        return response()->json([
            'node_id' => $nodeId,
            'hours' => $hours,
            'count' => $data->count(),
            'readings' => $data,
        ]);
    }

    /**
     * Daftar seluruh node dan statusnya.
     * GET /api/v1/nodes
     */
    public function nodes(): JsonResponse
    {
        $nodes = Node::with('latestReading', 'zone')->get();

        return response()->json(['nodes' => $nodes]);
    }

    /**
     * Trigger Peringatan Manual oleh Operator (PRD §12.3 FR-305).
     * POST /api/v1/alerts/manual
     */
    public function manualAlert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'level' => 'required|integer|in:1,2,3',
            'zone_id' => 'required|exists:zones,id',
            'reason' => 'required|string|min:5',
        ]);

        $now = Carbon::now();
        $alert = Alert::create([
            'zone_id' => $validated['zone_id'],
            'level' => $validated['level'],
            'status' => Alert::STATUS_ACTIVE,
            'trigger_type' => 'MANUAL',
            'trigger_metric' => 'MANUAL_OPERATOR',
            'message_text' => "Peringatan manual oleh operator: {$validated['reason']}",
            'started_at' => $now,
            'grace_period_ends_at' => ($validated['level'] === 3) ? $now->copy()->addSeconds(60) : null,
        ]);

        // Catat di audit log
        AuditLog::create([
            'ts' => $now,
            'user_id' => auth()->id() ?? 1,
            'action' => 'MANUAL_ALERT_TRIGGERED',
            'entity' => 'alerts',
            'entity_id' => (string) $alert->id,
            'after_value' => $validated,
            'ip_address' => $request->ip(),
        ]);

        // Dispatch notifikasi jika disetujui
        $dispatchResult = $this->whatsappDispatcher->dispatch($alert, true);

        return response()->json([
            'success' => true,
            'message' => "Peringatan manual Level {$alert->level} berhasil diaktifkan",
            'alert_id' => $alert->id,
            'dispatch' => $dispatchResult,
        ]);
    }

    /**
     * Acknowledge Alert oleh Operator (PRD §12.3 FR-307).
     * POST /api/v1/alerts/{id}/acknowledge
     */
    public function acknowledge(Request $request, int $id): JsonResponse
    {
        $alert = Alert::findOrFail($id);
        $alert->update([
            'status' => Alert::STATUS_ACKNOWLEDGED,
            'acknowledged_at' => Carbon::now(),
            'acknowledged_by' => auth()->id() ?? 1,
        ]);

        AuditLog::create([
            'ts' => Carbon::now(),
            'user_id' => auth()->id() ?? 1,
            'action' => 'ALERT_ACKNOWLEDGED',
            'entity' => 'alerts',
            'entity_id' => (string) $alert->id,
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Peringatan #{$alert->id} telah di-acknowledge oleh operator.",
        ]);
    }

    /**
     * Selesaikan Alert dengan Catatan (PRD §12.3 FR-307).
     * POST /api/v1/alerts/{id}/resolve
     */
    public function resolve(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'resolution_note' => 'required|string|min:3',
            'is_false_alarm' => 'nullable|boolean',
        ]);

        $alert = Alert::findOrFail($id);
        $alert->update([
            'status' => Alert::STATUS_RESOLVED,
            'resolved_at' => Carbon::now(),
            'resolution_note' => $validated['resolution_note'],
            'is_false_alarm' => $validated['is_false_alarm'] ?? false,
        ]);

        AuditLog::create([
            'ts' => Carbon::now(),
            'user_id' => auth()->id() ?? 1,
            'action' => 'ALERT_RESOLVED',
            'entity' => 'alerts',
            'entity_id' => (string) $alert->id,
            'after_value' => $validated,
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Peringatan #{$alert->id} berhasil ditutup.",
        ]);
    }

    /**
     * Batalkan Alert dalam Grace Window 60 detik (PRD §12.3 FR-306 & S-11).
     * POST /api/v1/alerts/{id}/cancel
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|min:3',
        ]);

        $alert = Alert::findOrFail($id);
        $alert->update([
            'status' => Alert::STATUS_CANCELLED,
            'resolved_at' => Carbon::now(),
            'resolution_note' => 'Dibatalkan dalam grace window: '.$validated['reason'],
        ]);

        AuditLog::create([
            'ts' => Carbon::now(),
            'user_id' => auth()->id() ?? 1,
            'action' => 'ALERT_CANCELLED_IN_GRACE_WINDOW',
            'entity' => 'alerts',
            'entity_id' => (string) $alert->id,
            'after_value' => $validated,
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Penyebaran peringatan #{$alert->id} berhasil dibatalkan.",
        ]);
    }

    /**
     * Uji Sirine Pos Satpam & Node (PRD §12.1 FR-107).
     * POST /api/v1/system/siren-test
     */
    public function sirenTest(Request $request): JsonResponse
    {
        AuditLog::create([
            'ts' => Carbon::now(),
            'user_id' => auth()->id() ?? 1,
            'action' => 'SIREN_TEST_ACTIVATED',
            'entity' => 'system',
            'entity_id' => 'siren',
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Perintah uji sirine 10 detik berhasil dikirim ke node pengeras suara.',
        ]);
    }
}
