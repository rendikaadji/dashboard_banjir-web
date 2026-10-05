<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Recipient;
use App\Models\Zone;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecipientController extends Controller
{
    /**
     * H-07: Manajemen Penerima WhatsApp & Cakupan RT
     */
    public function index(Request $request): View
    {
        $query = Recipient::with('zone')->latest();

        if ($request->filled('zone_id')) {
            $query->where('zone_id', $request->zone_id);
        }

        if ($request->filled('role_tag')) {
            $query->where('role_tag', $request->role_tag);
        }

        $recipients = $query->paginate(20);
        $zones = Zone::withCount('recipients')->get();

        $totalRecipients = Recipient::where('is_active', true)->count();

        return view('recipients.index', compact('recipients', 'zones', 'totalRecipients'));
    }

    /**
     * Tambah Penerima WhatsApp Baru (PRD §12.4 FR-406 & §16.2 Privasi)
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone_e164' => 'required|string|regex:/^\+62[0-9]{8,13}$/|unique:recipients,phone_e164',
            'zone_id' => 'required|exists:zones,id',
            'role_tag' => 'required|in:WARGA,KETUA_RT,SATPAM,SATGAS,PENGURUS_RW',
            'min_level' => 'required|integer|in:1,2,3',
        ], [
            'phone_e164.regex' => 'Nomor WhatsApp wajib berformat E.164 (contoh: +6281234567890).',
            'phone_e164.unique' => 'Nomor WhatsApp ini sudah terdaftar.',
        ]);

        $recipient = Recipient::create(array_merge($validated, [
            'consent_at' => Carbon::now(),
            'is_active' => true,
            'channel' => 'WHATSAPP',
        ]));

        AuditLog::create([
            'ts' => Carbon::now(),
            'user_id' => auth()->id() ?? 1,
            'action' => 'ADD_RECIPIENT',
            'entity' => 'recipients',
            'entity_id' => (string) $recipient->id,
            'after_value' => [
                'name' => $recipient->full_name,
                'role' => $recipient->role_tag,
                'phone' => substr($recipient->phone_e164, 0, 7).'****', // Samarkan untuk privasi
            ],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('recipients.index')->with('success', 'Penerima notifikasi WhatsApp berhasil didaftarkan.');
    }

    /**
     * Hapus / Nonaktifkan Penerima
     */
    public function destroy(int $id): RedirectResponse
    {
        $recipient = Recipient::findOrFail($id);
        $recipient->delete();

        return redirect()->route('recipients.index')->with('success', 'Penerima berhasil dihapus dari daftar notifikasi.');
    }
}
