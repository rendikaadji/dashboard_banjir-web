@extends('layouts.app')

@section('title', 'Analitik & KPI EPICS')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-white">Scoreboard KPI Proyek EPICS in IEEE 2026</h1>
            <p class="text-xs text-slate-400">Pengukuran empiris keberhasilan implementasi sistem FEWS di RW 08 Taman Cipulir Estate</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('analytics.export') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs font-medium transition shadow-xs">
                <i data-lucide="download" class="w-3.5 h-3.5 text-slate-400"></i>
                <span>Ekspor CSV (7 Hari)</span>
            </a>
        </div>
    </div>

    <!-- KPI Metric Cards Grid (PRD §3.2) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($kpis as $key => $kpi)
            <div class="glass-card p-5 rounded-2xl border border-slate-800 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 font-mono">
                            {{ strtoupper(str_replace('_', ' ', $key)) }}
                        </span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                            {{ $kpi['badge'] }}
                        </span>
                    </div>
                    <div class="mt-3">
                        <span class="text-xs text-slate-400">Pencapaian Terukur:</span>
                        <div class="font-mono text-3xl font-black text-white mt-0.5 tracking-tight">
                            {{ $kpi['current'] }}
                        </div>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-800 flex items-center justify-between text-xs">
                    <span class="text-slate-400">Target Proposal EPICS:</span>
                    <span class="font-bold text-cyan-400 font-mono">{{ $kpi['target'] }}</span>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Ringkasan Kejadian Banjir Per Bulan -->
    <div class="glass-card p-6 rounded-2xl border border-slate-800">
        <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-4 flex items-center space-x-2">
            <i data-lucide="calendar" class="w-4 h-4 text-cyan-400"></i>
            <span>Frekuensi Kejadian Siaga / Kritis 6 Bulan Terakhir</span>
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-900/80 text-slate-400 font-bold uppercase border-b border-slate-800">
                    <tr>
                        <th class="py-2.5 px-4">Bulan</th>
                        <th class="py-2.5 px-4">Jumlah Peringatan</th>
                        <th class="py-2.5 px-4">Level Tertinggi</th>
                        <th class="py-2.5 px-4">Evaluasi Evaluasi Dampak Warga</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 text-slate-300">
                    @forelse($alertsByMonth as $m)
                        <tr class="hover:bg-slate-800/40">
                            <td class="py-3 px-4 font-mono font-bold text-white">{{ $m->month }}</td>
                            <td class="py-3 px-4 font-mono">{{ $m->count }} kejadian</td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $m->max_level == 3 ? 'bg-red-500/20 text-red-400' : 'bg-orange-500/20 text-orange-400' }}">
                                    Level {{ $m->max_level }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-slate-400">Semua warga berhasil mengevakuasi kendaraan sebelum jalan tergenang > 40 cm.</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-6 text-center text-slate-500">Belum ada rekapitulasi data banjir bulanan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
