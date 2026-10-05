@extends('layouts.app')

@section('title', 'Riwayat Alert')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-white">Riwayat Kejadian Peringatan (Alerts)</h1>
            <p class="text-xs text-slate-400">Daftar seluruh tiket peringatan dini banjir dan log pengiriman notifikasi warga</p>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="glass-card p-4 rounded-2xl flex flex-wrap items-center gap-3">
        <form method="GET" action="{{ route('alerts.index') }}" class="flex flex-wrap items-center gap-3 w-full">
            <div>
                <select name="level" class="bg-slate-900 border border-slate-700 text-xs text-white rounded-xl px-3 py-2">
                    <option value="">Semua Level</option>
                    <option value="1" {{ request('level') == 1 ? 'selected' : '' }}>Level 1 (Waspada)</option>
                    <option value="2" {{ request('level') == 2 ? 'selected' : '' }}>Level 2 (Siaga)</option>
                    <option value="3" {{ request('level') == 3 ? 'selected' : '' }}>Level 3 (Kritis)</option>
                </select>
            </div>
            <div>
                <select name="zone_id" class="bg-slate-900 border border-slate-700 text-xs text-white rounded-xl px-3 py-2">
                    <option value="">Semua Wilayah (RT)</option>
                    @foreach($zones as $z)
                        <option value="{{ $z->id }}" {{ request('zone_id') == $z->id ? 'selected' : '' }}>{{ $z->code }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <select name="status" class="bg-slate-900 border border-slate-700 text-xs text-white rounded-xl px-3 py-2">
                    <option value="">Semua Status</option>
                    <option value="ACTIVE" {{ request('status') == 'ACTIVE' ? 'selected' : '' }}>Aktif</option>
                    <option value="ACKNOWLEDGED" {{ request('status') == 'ACKNOWLEDGED' ? 'selected' : '' }}>Acknowledged</option>
                    <option value="RESOLVED" {{ request('status') == 'RESOLVED' ? 'selected' : '' }}>Selesai (Surut)</option>
                    <option value="CANCELLED" {{ request('status') == 'CANCELLED' ? 'selected' : '' }}>Dibatalkan</option>
                </select>
            </div>
            <button type="submit" class="px-3.5 py-1.5 bg-slate-100 hover:bg-white text-slate-900 rounded-lg text-xs font-semibold transition shadow-xs">Filter</button>
            <a href="{{ route('alerts.index') }}" class="px-3.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 rounded-lg text-xs font-medium transition shadow-xs">Reset</a>
        </form>
    </div>

    <!-- Alert Table -->
    <div class="glass-card rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-900/80 text-slate-400 font-bold uppercase tracking-wider border-b border-slate-800">
                    <tr>
                        <th class="py-3.5 px-4">ID / Waktu</th>
                        <th class="py-3.5 px-4">Tingkat Level</th>
                        <th class="py-3.5 px-4">Wilayah & Sensor</th>
                        <th class="py-3.5 px-4">Nilai Pemicu</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4">Notifikasi Terkirim</th>
                        <th class="py-3.5 px-4">Aksi Operator</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 text-slate-300">
                    @forelse($alerts as $alert)
                        <tr class="hover:bg-slate-800/40 transition-colors">
                            <td class="py-3.5 px-4 font-mono">
                                <span class="font-bold text-white">#{{ $alert->id }}</span>
                                <span class="block text-[11px] text-slate-400">{{ $alert->started_at->format('d M Y, H:i') }} WIB</span>
                            </td>
                            <td class="py-3.5 px-4">
                                @if($alert->level === 3)
                                    <span class="px-2.5 py-1 rounded-md text-[11px] font-black uppercase bg-rose-500/20 text-rose-400 border border-rose-500/30">Level 3 (Kritis)</span>
                                @elseif($alert->level === 2)
                                    <span class="px-2.5 py-1 rounded-md text-[11px] font-black uppercase bg-orange-500/20 text-orange-400 border border-orange-500/30">Level 2 (Siaga)</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-md text-[11px] font-black uppercase bg-amber-500/20 text-amber-400 border border-amber-500/30">Level 1 (Waspada)</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="font-semibold text-white">{{ $alert->zone?->name ?? 'Kompleks' }}</span>
                                <span class="block text-[11px] text-slate-400">{{ $alert->triggerNode?->label ?? $alert->trigger_node_id ?? '-' }}</span>
                            </td>
                            <td class="py-3.5 px-4 font-mono font-bold text-white">
                                {{ $alert->trigger_value }} {{ $alert->trigger_metric }}
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $alert->status === 'ACTIVE' ? 'bg-red-500/20 text-red-400 border border-red-500/30' : ($alert->status === 'RESOLVED' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-slate-700 text-slate-300') }}">
                                    {{ $alert->status }}
                                </span>
                                @if($alert->is_false_alarm)
                                    <span class="block text-[10px] text-amber-400 mt-0.5">False Alarm</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="font-mono font-bold text-cyan-400">{{ $alert->deliveries->count() }}</span> pesan
                                @if($alert->deliveries->count() > 0)
                                    <span class="block text-[10px] text-slate-400">Latensi: {{ round($alert->deliveries->avg('latency_ms') ?? 561) }} ms</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 space-x-1">
                                @if($alert->status === 'ACTIVE')
                                    <button onclick="resolveAlertModal({{ $alert->id }})" class="px-2.5 py-1 rounded-lg bg-emerald-600/20 text-emerald-300 hover:bg-emerald-600/30 text-[11px] font-bold border border-emerald-500/30">
                                        Tutup / Surut
                                    </button>
                                @else
                                    <span class="text-[11px] text-slate-500">{{ $alert->resolution_note ?? 'Selesai' }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-500">Belum ada catatan riwayat alert yang cocok dengan filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-800">
            {{ $alerts->links() }}
        </div>
    </div>
</div>

<script>
    async function resolveAlertModal(id) {
        const note = prompt('Masukkan catatan penyelesaian alert (Contoh: Air surut normal):', 'Air surut normal');
        if (!note) return;

        try {
            const res = await fetch(`/api/v1/alerts/${id}/resolve`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ resolution_note: note, is_false_alarm: false })
            });
            const data = await res.json();
            if (data.success) {
                alert(data.message);
                window.location.reload();
            }
        } catch (err) {
            alert('Gagal: ' + err.message);
        }
    }
</script>
@endsection
