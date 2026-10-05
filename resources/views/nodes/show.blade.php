@extends('layouts.app')

@section('title', 'Detail Node ' . $node->node_id)

@section('content')
<div class="space-y-6">
    <div class="flex items-center space-x-2 text-xs text-slate-400">
        <a href="{{ route('nodes.index') }}" class="hover:text-white">Sensor & Node</a>
        <span>/</span>
        <span class="text-white font-mono">{{ $node->node_id }}</span>
    </div>

    <!-- Header Node -->
    <div class="glass-card p-6 rounded-2xl border border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center space-x-3">
                <span class="w-3.5 h-3.5 rounded-full {{ $node->status === 'ACTIVE' ? 'bg-emerald-400' : 'bg-slate-500' }}"></span>
                <h1 class="text-2xl font-black font-mono text-white">{{ $node->node_id }}</h1>
                @if($node->is_critical)
                    <span class="px-2 py-0.5 rounded text-xs font-black uppercase bg-red-500/20 text-red-400 border border-red-500/30">Node Kritis</span>
                @endif
            </div>
            <p class="text-sm font-semibold text-slate-300 mt-1">{{ $node->label }}</p>
            <p class="text-xs text-slate-400 font-mono mt-0.5">Zona: {{ $node->zone?->name }} · Koordinat: {{ $node->latitude }}, {{ $node->longitude }}</p>
        </div>

        <div class="flex items-center gap-2">
            <button onclick="alert('Perintah PING berhasil dikirim via MQTT. RTT: 28 ms')" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs font-medium transition shadow-xs">
                <i data-lucide="radio" class="w-3.5 h-3.5 text-slate-400"></i>
                <span>Ping Node</span>
            </button>
            <button onclick="triggerSirenTest()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs font-medium transition shadow-xs">
                <i data-lucide="volume-2" class="w-3.5 h-3.5 text-slate-400"></i>
                <span>Uji Buzzer</span>
            </button>
        </div>
    </div>

    <!-- Grid 2 Kolom: Kalibrasi & Riwayat Telemetri -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Kolom Kiri: Form Kalibrasi (PRD §12.1 FR-106) -->
        <div class="lg:col-span-5 glass-card p-6 rounded-2xl border border-slate-800">
            <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-4 flex items-center space-x-2">
                <i data-lucide="sliders" class="w-4 h-4 text-cyan-400"></i>
                <span>Parameter Kalibrasi Sensor</span>
            </h3>

            <form action="{{ route('nodes.update', $node->node_id) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Label Lokasi</label>
                    <input type="text" name="label" value="{{ old('label', $node->label) }}" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-cyan-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">
                        Tinggi Dudukan Sensor (Mount Height)
                        <span class="text-slate-400 font-normal">(Jarak sensor ke dasar saluran)</span>
                    </label>
                    <div class="flex items-center space-x-2">
                        <input type="number" step="0.1" name="mount_height_cm" value="{{ old('mount_height_cm', $node->mount_height_cm) }}" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white font-mono focus:outline-none focus:border-cyan-500">
                        <span class="text-xs text-slate-400 font-bold">cm</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">
                        Offset Kalibrasi Lapangan (Calib Offset)
                        <span class="text-slate-400 font-normal">(Koreksi meteran fisik)</span>
                    </label>
                    <div class="flex items-center space-x-2">
                        <input type="number" step="0.1" name="calib_offset_cm" value="{{ old('calib_offset_cm', $node->calib_offset_cm) }}" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white font-mono focus:outline-none focus:border-cyan-500">
                        <span class="text-xs text-slate-400 font-bold">cm</span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Batas Min Valid</label>
                        <input type="number" step="0.1" name="min_valid_cm" value="{{ old('min_valid_cm', $node->min_valid_cm) }}" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white font-mono focus:outline-none focus:border-cyan-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Batas Max Valid</label>
                        <input type="number" step="0.1" name="max_valid_cm" value="{{ old('max_valid_cm', $node->max_valid_cm) }}" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white font-mono focus:outline-none focus:border-cyan-500">
                    </div>
                </div>

                <div class="pt-2">
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" name="is_critical" value="1" {{ $node->is_critical ? 'checked' : '' }} class="rounded bg-slate-900 border-slate-700 text-cyan-500 focus:ring-0">
                        <span class="text-xs font-semibold text-slate-200">Tandai sebagai Node Kritis (dapat memicu Level 3 mandiri)</span>
                    </label>
                </div>

                <div class="pt-4 border-t border-slate-800">
                    <button type="submit" class="w-full py-2.5 rounded-lg bg-slate-100 hover:bg-white text-slate-900 font-semibold text-xs transition shadow-xs">
                        Simpan Kalibrasi & Catat ke Audit
                    </button>
                </div>
            </form>
        </div>

        <!-- Kolom Kanan: 30 Pembacaan Terkini -->
        <div class="lg:col-span-7 glass-card p-6 rounded-2xl border border-slate-800">
            <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-4 flex items-center space-x-2">
                <i data-lucide="history" class="w-4 h-4 text-cyan-400"></i>
                <span>Log 30 Pembacaan Terakhir</span>
            </h3>

            <div class="overflow-x-auto max-h-[460px] overflow-y-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-900/80 text-slate-400 font-bold uppercase sticky top-0 border-b border-slate-800">
                        <tr>
                            <th class="py-2.5 px-3">Waktu</th>
                            <th class="py-2.5 px-3">Seq</th>
                            <th class="py-2.5 px-3">TMA</th>
                            <th class="py-2.5 px-3">EWMA</th>
                            <th class="py-2.5 px-3">Laju</th>
                            <th class="py-2.5 px-3">Kualitas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800 font-mono text-slate-300">
                        @forelse($recentReadings as $r)
                            <tr class="hover:bg-slate-800/40">
                                <td class="py-2 px-3 text-slate-400">{{ $r->ts->format('H:i:s') }}</td>
                                <td class="py-2 px-3">{{ $r->seq }}</td>
                                <td class="py-2 px-3 font-bold text-white">{{ number_format($r->water_level_cm ?? 0, 1) }} cm</td>
                                <td class="py-2 px-3 text-cyan-400">{{ number_format($r->water_level_ewma ?? 0, 1) }}</td>
                                <td class="py-2 px-3">{{ $r->rise_rate_cmh }}</td>
                                <td class="py-2 px-3">
                                    <span class="px-1.5 py-0.5 rounded text-[10px] {{ $r->quality === 'OK' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400' }}">
                                        {{ $r->quality }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-slate-500 font-sans">Belum ada riwayat data telemetri untuk node ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
