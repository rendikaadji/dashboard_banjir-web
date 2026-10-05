@extends('layouts.app')

@section('title', 'Sensor & Node IoT')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-white">Daftar Sensor & Node IoT</h1>
            <p class="text-xs text-slate-400">Node ESP-32 pengukur tinggi air, debit arus, stasiun hujan, dan gateway solar</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @foreach($nodes as $node)
            <div class="glass-card p-5 rounded-2xl glass-card-hover border border-slate-800 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <span class="w-3 h-3 rounded-full {{ $node->status === 'ACTIVE' ? 'bg-emerald-400 shadow-sm shadow-emerald-400/50' : 'bg-slate-600' }}"></span>
                            <span class="font-mono font-bold text-base text-white">{{ $node->node_id }}</span>
                        </div>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded bg-slate-800 text-slate-300">{{ $node->zone?->code }}</span>
                    </div>

                    <h3 class="text-sm font-semibold text-slate-200 mt-2">{{ $node->label }}</h3>
                    <p class="text-xs text-slate-400 mt-0.5 font-mono">Tipe: {{ $node->node_type }}</p>

                    <div class="mt-4 p-3 rounded-xl bg-slate-900/60 border border-slate-800/80 grid grid-cols-2 gap-2 text-xs">
                        <div>
                            <span class="text-[11px] text-slate-400 block">Nilai Terakhir:</span>
                            <span class="font-mono font-bold text-white text-base">
                                @if($node->node_type === 'WATER_LEVEL')
                                    {{ number_format($node->latestReading?->water_level_cm ?? 0, 1) }} cm
                                @elseif($node->node_type === 'FLOW')
                                    {{ number_format($node->latestReading?->flow_velocity_ms ?? 0, 2) }} m/s
                                @else
                                    {{ number_format($node->latestReading?->rain_rate_mmh ?? 0, 1) }} mm/h
                                @endif
                            </span>
                        </div>
                        <div>
                            <span class="text-[11px] text-slate-400 block">Baterai & Sinyal:</span>
                            <span class="font-mono text-slate-300">
                                {{ $node->latestReading?->battery_v ?? 12.6 }}V · {{ $node->latestReading?->rssi ?? -68 }} dBm
                            </span>
                        </div>
                    </div>
                </div>

                <div class="mt-5 pt-3 border-t border-slate-800 flex items-center justify-between">
                    <span class="text-[11px] text-slate-500 font-mono">
                        {{ $node->last_seen_at ? $node->last_seen_at->diffForHumans(null, true) : 'Online' }}
                    </span>
                    <a href="{{ route('nodes.show', $node->node_id) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs font-medium transition shadow-xs">
                        <span>Kalibrasi & Detail</span>
                        <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-400"></i>
                    </a>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
