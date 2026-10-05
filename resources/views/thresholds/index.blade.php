@extends('layouts.app')

@section('title', 'Ambang Batas & Simulasi')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-white">Ambang Batas & Logika Keselamatan Alert</h1>
            <p class="text-xs text-slate-400">Konfigurasi aturan pemicu otomatis Level 1 (Waspada), Level 2 (Siaga), dan Level 3 (Kritis)</p>
        </div>
    </div>

    <!-- Dry-Run Impact Simulator Banner (PRD §13.3 H-06) -->
    <div class="glass-card p-5 rounded-2xl border border-cyan-500/30 bg-cyan-950/10">
        <div class="flex items-start space-x-3">
            <span class="p-2 rounded-xl bg-cyan-500/20 text-cyan-400">
                <i data-lucide="sparkles" class="w-5 h-5"></i>
            </span>
            <div class="flex-1">
                <h3 class="text-xs font-bold uppercase tracking-wider text-cyan-300">Simulasi Dry-Run Dampak Ambang (Data Historis 30 Hari Terakhir)</h3>
                <p class="text-xs text-slate-300 mt-1">
                    Bila ambang di bawah ini diterapkan pada rekaman sensor bulan lalu, sistem menghasilkan estimasi:
                </p>
                <div class="grid grid-cols-3 gap-3 mt-3">
                    <div class="p-3 rounded-xl bg-slate-900/80 border border-amber-500/30 text-center">
                        <span class="text-[11px] font-bold text-amber-400 uppercase block">Level 1 (Waspada)</span>
                        <span class="font-mono text-xl font-extrabold text-white">{{ $countL1 }}</span>
                        <span class="text-[10px] text-slate-400 block">pembacaan TMA ≥ 70 cm</span>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-900/80 border border-orange-500/30 text-center">
                        <span class="text-[11px] font-bold text-orange-400 uppercase block">Level 2 (Siaga)</span>
                        <span class="font-mono text-xl font-extrabold text-white">{{ $countL2 }}</span>
                        <span class="text-[10px] text-slate-400 block">pembacaan TMA ≥ 100 cm</span>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-900/80 border border-red-500/30 text-center">
                        <span class="text-[11px] font-bold text-red-400 uppercase block">Level 3 (Kritis)</span>
                        <span class="font-mono text-xl font-extrabold text-white">{{ $countL3 }}</span>
                        <span class="text-[10px] text-slate-400 block">pembacaan TMA ≥ 150 cm</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Threshold Rules List -->
    <div class="space-y-4">
        @foreach($thresholds as $t)
            <div class="glass-card p-5 rounded-2xl border border-slate-800 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center font-black text-lg {{ $t->level === 3 ? 'bg-red-500/20 text-red-400 border border-red-500/40' : ($t->level === 2 ? 'bg-orange-500/20 text-orange-400 border border-orange-500/40' : 'bg-amber-500/20 text-amber-400 border border-amber-500/40') }}">
                        L{{ $t->level }}
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h3 class="text-sm font-bold text-white uppercase">{{ $t->level_name }} — {{ $t->metric }}</h3>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-800 text-slate-300">
                                Target: {{ $t->target_audience }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Ambang: <strong class="text-white font-mono">≥ {{ $t->value_low }}</strong> · Sustain: <strong class="text-white font-mono">{{ $t->sustain_seconds }}s</strong> · Hysteresis: <strong class="text-white font-mono">{{ $t->hysteresis_cm }} cm</strong> · Cooldown: <strong class="text-white font-mono">{{ $t->cooldown_minutes }} menit</strong>
                        </p>
                    </div>
                </div>

                <div>
                    <button onclick="openEditThresholdModal({{ $t->id }}, '{{ $t->level_name }}', '{{ $t->metric }}', {{ $t->value_low }}, {{ $t->sustain_seconds }}, {{ $t->hysteresis_cm }}, {{ $t->cooldown_minutes }})" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs font-medium transition shadow-xs">
                        <i data-lucide="sliders" class="w-3.5 h-3.5 text-slate-400"></i>
                        <span>Ubah Parameter</span>
                    </button>
                </div>
            </div>
        @endforeach
    </div>
</div>

<!-- Modal: Edit Threshold dengan Safety Confirmation -->
<div id="threshold-modal" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="glass-card max-w-md w-full p-6 rounded-2xl border border-slate-700 shadow-2xl">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-bold text-white flex items-center space-x-2">
                <i data-lucide="shield-check" class="w-5 h-5 text-cyan-400"></i>
                <span id="modal-thresh-title">Ubah Ambang Batas</span>
            </h3>
            <button onclick="closeEditThresholdModal()" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form id="edit-threshold-form" method="POST" action="">
            @csrf
            <div class="space-y-3.5">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Nilai Ambang Pemicu (Value Low)</label>
                    <input type="number" step="0.1" id="m-value-low" name="value_low" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white font-mono focus:outline-none focus:border-cyan-500">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Sustain (Detik)</label>
                        <input type="number" id="m-sustain" name="sustain_seconds" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white font-mono focus:outline-none focus:border-cyan-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Hysteresis (cm)</label>
                        <input type="number" step="0.1" id="m-hysteresis" name="hysteresis_cm" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white font-mono focus:outline-none focus:border-cyan-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Cooldown Antar Notifikasi (Menit)</label>
                    <input type="number" id="m-cooldown" name="cooldown_minutes" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white font-mono focus:outline-none focus:border-cyan-500">
                </div>

                <div class="p-3 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300 text-xs space-y-1.5">
                    <p class="font-bold">⚠️ Mekanisme Keselamatan (Safety Lock):</p>
                    <p class="text-[11px] leading-relaxed">
                        Mengubah ambang batas berdampak langsung pada keselamatan evakuasi warga RW 08. Ketik kata <strong>SIMPAN</strong> untuk konfirmasi:
                    </p>
                    <input type="text" name="confirm_keyword" required placeholder="Ketik SIMPAN" class="w-full bg-slate-900 border border-red-500/40 rounded-lg px-3 py-1.5 text-xs text-white font-bold focus:outline-none">
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-2 pt-3 border-t border-slate-800">
                <button type="button" onclick="closeEditThresholdModal()" class="px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-white">Batal</button>
                <button type="submit" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-white text-slate-900 text-xs font-semibold transition shadow-xs">Simpan Ambang Baru</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditThresholdModal(id, name, metric, val, sustain, hyst, cool) {
        const modal = document.getElementById('threshold-modal');
        const form = document.getElementById('edit-threshold-form');
        form.action = `/thresholds/${id}`;

        document.getElementById('modal-thresh-title').textContent = `Ubah Ambang ${name} (${metric})`;
        document.getElementById('m-value-low').value = val;
        document.getElementById('m-sustain').value = sustain;
        document.getElementById('m-hysteresis').value = hyst;
        document.getElementById('m-cooldown').value = cool;

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeEditThresholdModal() {
        const modal = document.getElementById('threshold-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
</script>
@endsection
