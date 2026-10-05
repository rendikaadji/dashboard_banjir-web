@extends('layouts.app')

@section('title', 'Kesehatan Sistem & Audit')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-white">Kesehatan Sistem & Jejak Audit (Audit Trail)</h1>
            <p class="text-xs text-slate-400">Observabilitas infrastruktur server edge Raspberry Pi 5 Pos Satpam dan log keamanan</p>
        </div>
    </div>

    <!-- Component Health Status Cards (PRD §13.3 H-09) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5">
        @foreach($components as $c)
            <div class="glass-card p-4 rounded-xl border border-slate-800 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="w-2.5 h-2.5 rounded-full {{ $c['status'] === 'ONLINE' || $c['status'] === 'HEALTHY' ? 'bg-emerald-400' : 'bg-amber-400' }}"></span>
                        <span class="text-[10px] font-bold uppercase tracking-wider {{ $c['status'] === 'ONLINE' || $c['status'] === 'HEALTHY' ? 'text-emerald-400' : 'text-amber-400' }}">
                            {{ $c['status'] }}
                        </span>
                    </div>
                    <h3 class="text-xs font-bold text-white mt-2">{{ $c['name'] }}</h3>
                    <p class="text-[11px] text-slate-400 mt-1">{{ $c['detail'] }}</p>
                </div>
                <div class="mt-3 pt-2 border-t border-slate-800 text-[10px] text-slate-500 font-mono">
                    Latensi: {{ $c['latency'] }}
                </div>
            </div>
        @endforeach
    </div>

    <!-- Tabel Jejak Audit (Audit Trail - PRD §13.3 H-10 & §15.2) -->
    <div class="glass-card rounded-2xl overflow-hidden border border-slate-800">
        <div class="p-4 border-b border-slate-800 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <i data-lucide="shield" class="w-4 h-4 text-cyan-400"></i>
                <h3 class="text-xs font-bold text-white uppercase tracking-wider">Jejak Audit Aktivitas & Perubahan Parameter</h3>
            </div>
            <span class="text-xs text-slate-400">Tercatat permanen untuk akuntabilitas EPICS</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-900/80 text-slate-400 font-bold uppercase border-b border-slate-800">
                    <tr>
                        <th class="py-2.5 px-4">Waktu</th>
                        <th class="py-2.5 px-4">Pengguna</th>
                        <th class="py-2.5 px-4">Aksi / Operasi</th>
                        <th class="py-2.5 px-4">Entitas</th>
                        <th class="py-2.5 px-4">Rincian Perubahan</th>
                        <th class="py-2.5 px-4">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 text-slate-300 font-mono">
                    @forelse($auditLogs as $log)
                        <tr class="hover:bg-slate-800/40">
                            <td class="py-3 px-4 text-slate-400">{{ $log->ts->format('d/m H:i:s') }}</td>
                            <td class="py-3 px-4 font-sans font-semibold text-white">
                                {{ $log->user?->name ?? 'Sistem Otomatis' }}
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-800 text-cyan-300 border border-slate-700">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-slate-300">{{ $log->entity }} #{{ $log->entity_id }}</td>
                            <td class="py-3 px-4 font-sans text-slate-400 truncate max-w-xs text-[11px]">
                                @if($log->after_value)
                                    {{ json_encode($log->after_value) }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="py-3 px-4 text-slate-500 text-[11px]">{{ $log->ip_address ?? '127.0.0.1' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-500 font-sans">Belum ada catatan aktivitas di log audit.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-800">
            {{ $auditLogs->links() }}
        </div>
    </div>
</div>
@endsection
