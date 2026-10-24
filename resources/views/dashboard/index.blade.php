@extends('layouts.app')

@section('title', 'Dashboard Utama')

@section('content')
<div class="space-y-6">

    <!-- 1. Banner Status Utama Kompleks (Sesuai Standar Operasi Tanggap Darurat) -->
    @php
        $levelConfig = [
            0 => [
                'name' => 'AMAN / NORMAL',
                'badge' => 'bg-emerald-600 text-white',
                'banner' => 'bg-emerald-950/40 border-emerald-800/80 text-emerald-100',
                'desc' => 'Seluruh saluran pembuang dan gorong-gorong dalam kondisi lancar di bawah batas waspada.',
            ],
            1 => [
                'name' => 'WASPADA',
                'badge' => 'bg-amber-500 text-slate-950 font-bold',
                'banner' => 'bg-amber-950/40 border-amber-800/80 text-amber-100',
                'desc' => 'Permukaan air mengalami kenaikan di atas ambang waspada. Siaga pemantauan berkala.',
            ],
            2 => [
                'name' => 'SIAGA BANJIR',
                'badge' => 'bg-orange-600 text-white font-bold',
                'banner' => 'bg-orange-950/40 border-orange-800/80 text-orange-100',
                'desc' => 'Air meluap ke badan jalan permukiman. Notifikasi otomatis telah dikirim ke Pengurus RW & Tim Satgas.',
            ],
            3 => [
                'name' => 'KRITIS (DARURAT)',
                'badge' => 'bg-red-600 text-white font-bold animate-pulse',
                'banner' => 'bg-red-950/50 border-red-800 text-red-100',
                'desc' => 'TMA melampaui ambang kritis 150 cm. Sirine aktif dan notifikasi evakuasi telah disiarkan ke grup warga.',
            ],
        ];
        $currentCfg = $levelConfig[$currentLevel] ?? $levelConfig[0];
    @endphp

    <div id="status-banner" class="p-5 rounded-2xl border transition-all duration-300 {{ $currentCfg['banner'] }}">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-start md:items-center gap-4">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center font-black text-xl shrink-0 {{ $currentLevel === 3 ? 'bg-red-600 text-white' : ($currentLevel === 2 ? 'bg-orange-600 text-white' : ($currentLevel === 1 ? 'bg-amber-500 text-slate-950' : 'bg-emerald-700 text-white')) }}">
                    L{{ $currentLevel }}
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs uppercase tracking-wider font-semibold text-slate-400">Status Banjir Kompleks</span>
                        <span id="banner-level-badge" class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider {{ $currentCfg['badge'] }}">
                            {{ $currentCfg['name'] }}
                        </span>
                    </div>
                    <p id="banner-subtitle" class="text-sm font-medium mt-1">
                        @if($activeAlert)
                            Sejak {{ $activeAlert->started_at->format('H.i') }} WIB · Dipicu {{ $activeAlert->trigger_node_id ?? 'Sensor Utama' }} ({{ $activeAlert->trigger_value }} {{ $activeAlert->trigger_metric }})
                        @else
                            {{ $currentCfg['desc'] }}
                        @endif
                    </p>
                </div>
            </div>

            <!-- Tombol Aksi Operator -->
            <div class="flex flex-wrap items-center gap-2">
                @if($activeAlert && $activeAlert->status === 'ACTIVE')
                    <button onclick="acknowledgeAlert({{ $activeAlert->id }})" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-white text-xs font-medium border border-slate-700 transition shadow-xs">
                        <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-400"></i>
                        <span>Acknowledge</span>
                    </button>

                    @if($activeAlert->level === 3 && $activeAlert->grace_period_ends_at && $activeAlert->grace_period_ends_at->isFuture())
                        <button onclick="cancelAlert({{ $activeAlert->id }})" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-red-900/80 hover:bg-red-800 text-red-100 text-xs font-semibold border border-red-700 transition shadow-xs">
                            <i data-lucide="x-circle" class="w-3.5 h-3.5 text-red-300"></i>
                            <span>Batalkan Blast (<span id="grace-timer">60</span>s)</span>
                        </button>
                    @endif
                @endif

                <button onclick="openManualAlertModal()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs font-medium transition shadow-xs">
                    <i data-lucide="bell" class="w-3.5 h-3.5 text-amber-400"></i>
                    <span>Peringatan Manual</span>
                </button>

                <button onclick="triggerSirenTest()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs font-medium transition shadow-xs">
                    <i data-lucide="volume-2" class="w-3.5 h-3.5 text-slate-400"></i>
                    <span>Uji Sirine</span>
                </button>
            </div>
        </div>
    </div>

    <!-- 2. Grid 4 Metrik Operasional Utama -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Metrik 1: TMA Tertinggi -->
        <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Tinggi Air Tertinggi</span>
                    <i data-lucide="activity" class="w-4 h-4 text-slate-500"></i>
                </div>
                <div class="mt-3 flex items-baseline gap-1">
                    <span id="hero-tma" class="font-mono text-4xl font-extrabold text-white tracking-tight">{{ number_format($highestTma, 1) }}</span>
                    <span class="text-sm font-semibold text-slate-400">cm</span>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-400">
                <span id="hero-location" class="truncate">{{ $highestNode?->label ?? 'Depan Masjid' }}</span>
                <span id="hero-rise-rate" class="font-mono text-slate-300">
                    +{{ $highestNode?->latestReading?->rise_rate_cmh ?? 0.0 }} cm/h
                </span>
            </div>
        </div>

        <!-- Metrik 2: Status Perahu Karet (PRD §10.4) -->
        <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Status Perahu Karet</span>
                    <i data-lucide="life-buoy" class="w-4 h-4 text-slate-500"></i>
                </div>
                <div class="mt-3">
                    <span id="boat-status-badge" class="inline-block px-2.5 py-1 rounded-md font-bold text-xs {{ $boatStatus['badge_class'] }}">
                        {{ $boatStatus['label'] }}
                    </span>
                </div>
            </div>
            <p id="boat-status-desc" class="mt-3 pt-3 border-t border-slate-800/80 text-xs text-slate-400 line-clamp-1">
                {{ $boatStatus['desc'] }}
            </p>
        </div>

        <!-- Metrik 3: Curah Hujan -->
        <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Curah Hujan</span>
                    <i data-lucide="cloud-rain" class="w-4 h-4 text-slate-500"></i>
                </div>
                <div class="mt-3 flex items-baseline gap-1">
                    <span id="hero-rain-rate" class="font-mono text-4xl font-extrabold text-white">{{ number_format($rainRate, 1) }}</span>
                    <span class="text-sm font-semibold text-slate-400">mm/jam</span>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t border-slate-800/80 text-xs text-slate-400 truncate">
                Stasiun Hujan Pos RW 08
            </div>
        </div>

        <!-- Metrik 4: Kecepatan Debit Arus -->
        <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Kecepatan Arus</span>
                    <i data-lucide="gauge" class="w-4 h-4 text-slate-500"></i>
                </div>
                <div class="mt-3 flex items-baseline gap-1">
                    <span id="hero-flow-velocity" class="font-mono text-4xl font-extrabold text-white">{{ number_format($flowVelocity, 2) }}</span>
                    <span class="text-sm font-semibold text-slate-400">m/s</span>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t border-slate-800/80 text-xs text-slate-400 truncate">
                Pintu Air Inlet Utama RT 06
            </div>
        </div>
    </div>

    <!-- 3. Peta Interaktif & Grafik Tren Time-Series -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Peta Wilayah RW 08 (7 Kolom) -->
        <div class="lg:col-span-7 p-5 rounded-2xl bg-slate-900 border border-slate-800 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <i data-lucide="map" class="w-4 h-4 text-emerald-400"></i>
                    <h3 class="text-xs font-bold text-white uppercase tracking-wider">Site Plan & Titik Sensor TCE</h3>
                </div>
            </div>
            
            <div class="relative z-0 isolate w-full rounded-xl overflow-hidden border border-slate-800 bg-white">
                <div id="map-siteplan" class="w-full h-80 sm:h-[26rem]" style="background:#fff"></div>

                <!-- Panel detail titik: melayang di kanan peta (sm ke atas), di bawah peta pada HP -->
                <aside id="node-panel" hidden aria-live="polite"
                       class="bg-slate-900 text-slate-100 border-t border-slate-800 sm:border sm:rounded-xl sm:absolute sm:top-3 sm:right-3 sm:bottom-3 sm:w-72 z-[500] overflow-y-auto shadow-2xl shadow-black/40"></aside>
            </div>
            <div class="mt-2.5 flex items-center justify-between text-[11px] text-slate-400">
                <span>Klik pin atau titik di daftar untuk melihat data sensor lengkap</span>
                <span class="text-slate-500 font-mono">Taman Cipulir Estate · RW 08</span>
            </div>
            <div id="node-list" class="mt-3 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2"></div>
        </div>

        <!-- Grafik Time-Series Sensor Utama (5 Kolom) -->
        <div class="lg:col-span-5 p-5 rounded-2xl bg-slate-900 border border-slate-800 flex flex-col">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <i data-lucide="trending-up" class="w-4 h-4 text-slate-400"></i>
                    <h3 class="text-xs font-bold text-white uppercase tracking-wider">Tren Air (Depan Masjid)</h3>
                </div>
                <span class="text-xs font-mono text-slate-400">Garis L1/L2/L3</span>
            </div>
            <div class="relative flex-1 min-h-[280px]">
                <canvas id="timeSeriesChart"></canvas>
            </div>
        </div>
    </div>

    <!-- 4. Grid Kartu Status Seluruh Node Sensor -->
    <div>
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <i data-lucide="cpu" class="w-4 h-4 text-slate-400"></i>
                <h3 class="text-xs font-bold text-white uppercase tracking-wider">Seluruh Node Sensor IoT ({{ $nodes->count() }} Titik)</h3>
            </div>
            <span class="text-xs text-slate-500">Streaming data aktif</span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4" id="nodes-grid">
            @foreach($nodes as $node)
                @php
                    $val = $node->latestReading?->water_level_cm;
                    $borderStyle = 'border-slate-800';
                    if ($val !== null) {
                        if ($val >= 150) $borderStyle = 'border-red-600 bg-red-950/20';
                        elseif ($val >= 100) $borderStyle = 'border-orange-600 bg-orange-950/20';
                        elseif ($val >= 70) $borderStyle = 'border-amber-600 bg-amber-950/20';
                    }
                @endphp
                <div id="node-card-{{ $node->node_id }}" class="p-4 rounded-2xl bg-slate-900 border {{ $borderStyle }} flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full {{ $node->status === 'ACTIVE' ? 'bg-emerald-500' : 'bg-slate-600' }}"></span>
                                <span class="font-mono font-bold text-sm text-white">{{ $node->node_id }}</span>
                                @if($node->is_critical)
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase bg-red-950 border border-red-800 text-red-300">Kritis</span>
                                @endif
                            </div>
                            <span class="text-xs text-slate-400 font-medium">{{ $node->zone?->code }}</span>
                        </div>

                        <p class="text-xs text-slate-300 font-medium mt-1 truncate">{{ $node->label }}</p>

                        <div class="mt-3 flex items-baseline justify-between border-t border-slate-800 pt-3">
                            <div>
                                @if($node->node_type === 'WATER_LEVEL')
                                    <div class="flex items-baseline gap-1">
                                        <span class="font-mono text-2xl font-bold text-white node-val">{{ number_format($val ?? 0, 1) }}</span>
                                        <span class="text-xs text-slate-400">cm</span>
                                    </div>
                                    <span class="text-[11px] text-slate-500">Tinggi Muka Air</span>
                                @elseif($node->node_type === 'FLOW')
                                    <div class="flex items-baseline gap-1">
                                        <span class="font-mono text-2xl font-bold text-white node-val">{{ number_format($node->latestReading?->flow_velocity_ms ?? 0, 2) }}</span>
                                        <span class="text-xs text-slate-400">m/s</span>
                                    </div>
                                    <span class="text-[11px] text-slate-500">Kecepatan Arus</span>
                                @elseif($node->node_type === 'RAINFALL')
                                    <div class="flex items-baseline gap-1">
                                        <span class="font-mono text-2xl font-bold text-white node-val">{{ number_format($node->latestReading?->rain_rate_mmh ?? 0, 1) }}</span>
                                        <span class="text-xs text-slate-400">mm/h</span>
                                    </div>
                                    <span class="text-[11px] text-slate-500">Intensitas Hujan</span>
                                @endif
                            </div>

                            <div class="text-right text-[11px] space-y-0.5">
                                <div class="text-slate-400 flex items-center justify-end gap-1">
                                    <i data-lucide="battery" class="w-3 h-3 text-slate-400"></i>
                                    <span>{{ $node->latestReading?->battery_v ?? 12.6 }}V</span>
                                </div>
                                <div class="text-slate-500 font-mono">
                                    {{ $node->last_seen_at ? $node->last_seen_at->diffForHumans(null, true) : 'Online' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

</div>

<!-- Modal: Trigger Peringatan Manual Operator -->
<div id="manual-alert-modal" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 max-w-md w-full p-6 rounded-2xl shadow-2xl">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-400"></i>
                <span>Pemicu Peringatan Manual</span>
            </h3>
            <button onclick="closeManualAlertModal()" class="text-slate-400 hover:text-white">&times;</button>
        </div>
        <form id="manual-alert-form" onsubmit="submitManualAlert(event)">
            <div class="space-y-3.5">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Tingkat Level</label>
                    <select id="manual-level" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-slate-500">
                        <option value="1">Level 1 — WASPADA (Kuning)</option>
                        <option value="2" selected>Level 2 — SIAGA (Oranye - Notif Pengurus/Satgas)</option>
                        <option value="3">Level 3 — KRITIS (Merah - Blast Grup Warga + Sirine)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Wilayah Terdampak</label>
                    <select id="manual-zone" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-slate-500">
                        @foreach($zones as $z)
                            <option value="{{ $z->id }}">{{ $z->code }} — {{ $z->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Alasan Peringatan Manual</label>
                    <textarea id="manual-reason" rows="3" required placeholder="Contoh: Terjadi genangan cepat akibat luapan parit Pasar Cipadu..." class="w-full bg-slate-950 border border-slate-700 rounded-xl p-3 text-xs text-white focus:outline-none focus:border-slate-500"></textarea>
                </div>
            </div>
            <div class="mt-5 flex items-center justify-end gap-2">
                <button type="button" onclick="closeManualAlertModal()" class="px-3 py-2 rounded-xl text-xs font-medium text-slate-400 hover:text-white">Batal</button>
                <button type="submit" class="px-4 py-2 rounded-xl bg-orange-600 hover:bg-orange-500 text-white text-xs font-semibold">Siarkan Peringatan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // ── 0. Kamus Deskripsi Operasional Setiap Titik Sensor ──
    const nodeDescriptions = {
        'WL-09-01': 'Titik elevasi terendah di kompleks RW 08. Genangan selalu bermula dari gorong-gorong depan masjid ini; acuan utama sirine bahaya.',
        'WL-09-02': 'Memantau saluran primer sisi timur masjid sebelum air mengalir masuk ke saluran perumahan warga RT 09.',
        'WL-06-01': 'Saluran drainase boulevard jalan utama barat perumahan; pemantau limpasan dari jalan primer kompleks.',
        'WL-06-02': 'Saluran cabang blok dalam barat laut untuk mendeteksi potensi genangan balik (backflow) ke rumah warga.',
        'WL-11-01': 'Saluran pemukiman perbatasan barat laut yang rawan luapan limpasan dari saluran luar kompleks perumahan.',
        'WF-01-01': 'Gerbang Masuk Utama TCE (Utara); memantau debit dan kecepatan arus air kiriman dari Jl. Taman Cipulir Raya.',
        'WF-02-01': 'Saluran perimeter timur laut; mendeteksi perlambatan arus akibat potensi sumbatan endapan atau sampah.',
        'WF-06-01': 'Pintu pelepasan buangan belakang masjid menuju saluran pembuang primer luar perumahan.',
        'WF-11-01': 'Saluran retensi parit selatan menuju kolam penampungan; pengukur efektivitas pompa pembuang.',
        'RN-07-01': 'Stasiun curah hujan sektor barat hulu perumahan; deteksi awal hujan lebat sebelum air menggenang.',
        'RN-08-01': 'Stasiun curah hujan sektor timur hilir perumahan untuk kalkulasi prediksi laju kenaikan air (EWMA).',
        'POS-RW-08': 'Pos Satpam Utama & Balai RW 08: Pusat kendali operasional FEWS 24 jam, layar Kiosk, dan aktivasi sirine darurat.'
    };

    const nodeColors = {
        'WATER_LEVEL': '#2563eb',
        'FLOW': '#16a34a',
        'RAINFALL': '#dc2626',
        'DASHBOARD': '#ec4899'
    };

    // ── 2. Fungsi Pin Teardrop SVG (Konsisten untuk Site Plan & GPS) ──
    // Inject pulse animation keyframe sekali
    if (!document.getElementById('pin-pulse-css')) {
        const s = document.createElement('style');
        s.id = 'pin-pulse-css';
        s.textContent = '@keyframes pin-pulse{0%{transform:scale(1);opacity:.7}70%{transform:scale(1.8);opacity:0}100%{transform:scale(1);opacity:0}}';
        document.head.appendChild(s);
    }

    function createTeardropIcon(color, isCritical, sz) {
        sz = sz || 26;
        const h = Math.round(sz * 1.31);
        const half = Math.round(sz / 2);
        const cy = Math.round(sz * 0.46);
        const r1 = Math.round(sz * 0.21);
        const r2 = Math.round(sz * 0.115);
        const c1 = Math.round(sz * 0.224);
        const c2 = Math.round(sz * 0.776);
        const c3 = Math.round(sz * 0.875);
        const pulse = isCritical
            ? '<div style="position:absolute;inset:-5px;border-radius:50%;background:rgba(239,68,68,0.35);animation:pin-pulse 1.5s ease-out infinite;pointer-events:none;"></div>'
            : '';
        return L.divIcon({
            className: '',
            html: '<div style="position:relative;width:' + sz + 'px;height:' + h + 'px;opacity:0.01;">' + pulse +
                '<svg width="' + sz + '" height="' + h + '" viewBox="0 0 ' + sz + ' ' + h + '" style="position:absolute;top:0;left:0;">' +
                '<path d="M' + half + ' 0C' + c1 + ' 0 0 ' + c1 + ' 0 ' + cy + 'C0 ' + c3 + ' ' + half + ' ' + h + ' ' + half + ' ' + h + 'C' + half + ' ' + h + ' ' + sz + ' ' + c3 + ' ' + sz + ' ' + cy + 'C' + sz + ' ' + c1 + ' ' + c2 + ' 0 ' + half + ' 0Z" fill="' + color + '"/>' +
                '<circle cx="' + half + '" cy="' + cy + '" r="' + r1 + '" fill="#fff"/>' +
                '<circle cx="' + half + '" cy="' + cy + '" r="' + r2 + '" fill="' + color + '"/>' +
                '</svg></div>',
            iconSize: [sz, h],
            iconAnchor: [half, cy],
            popupAnchor: [0, -(cy + 4)]
        });
    }

    // Helper: format nilai telemetri
    function getValText(n) {
        if (n.node_type === 'FLOW') return (n.latest_reading ? n.latest_reading.flow_velocity_ms : 0) + ' m/s';
        if (n.node_type === 'RAINFALL') return (n.latest_reading ? n.latest_reading.rain_rate_mmh : 0) + ' mm/jam';
        return (n.latest_reading ? n.latest_reading.water_level_cm : 0) + ' cm';
    }

    // Waktu relatif ("3 mnt lalu") dari timestamp ISO
    function timeAgo(iso) {
        if (!iso) return 'belum pernah';
        const sec = Math.max(0, Math.round((Date.now() - new Date(iso).getTime()) / 1000));
        if (sec < 60) return sec + ' dtk lalu';
        if (sec < 3600) return Math.round(sec / 60) + ' mnt lalu';
        if (sec < 86400) return Math.round(sec / 3600) + ' jam lalu';
        return Math.round(sec / 86400) + ' hari lalu';
    }

    const typeMeta = {
        'WATER_LEVEL': { label: 'Tinggi Muka Air', color: '#3b82f6' },
        'FLOW': { label: 'Arus Air', color: '#22c55e' },
        'RAINFALL': { label: 'Curah Hujan', color: '#ef4444' },
        'DASHBOARD': { label: 'Posko', color: '#ec4899' }
    };

    // Data lengkap satu titik untuk panel detail
    function buildDetail(n) {
        const r = n.latest_reading || {};
        const fmt = (v, unit, d) => (v === null || v === undefined) ? '-' : Number(v).toFixed(d === undefined ? 1 : d) + ' ' + unit;
        let stats = [];
        if (n.node_type === 'WATER_LEVEL') {
            stats = [['TMA (EWMA)', fmt(r.water_level_ewma, 'cm')], ['Laju naik', fmt(r.rise_rate_cmh, 'cm/jam')],
                     ['Jarak sensor ke air', fmt(r.distance_cm, 'cm')], ['Tinggi pasang', fmt(n.mount_height_cm, 'cm', 0)]];
        } else if (n.node_type === 'FLOW') {
            stats = [['Kecepatan arus', fmt(r.flow_velocity_ms, 'm/s', 2)], ['Debit', fmt(r.flow_lpm, 'L/mnt')]];
        } else if (n.node_type === 'RAINFALL') {
            stats = [['Intensitas hujan', fmt(r.rain_rate_mmh, 'mm/jam')], ['Akumulasi', fmt(r.rainfall_mm, 'mm')],
                     ['Kecepatan angin', fmt(r.wind_speed_ms, 'm/s')]];
        }
        stats.push(['Baterai', fmt(r.battery_v, 'V')], ['Panel surya', fmt(r.solar_v, 'V')],
                   ['Sinyal (RSSI)', r.rssi !== undefined && r.rssi !== null ? r.rssi + ' dBm' : '-'],
                   ['Kualitas data', r.quality || '-'], ['Status node', n.status || '-'], ['Firmware', n.firmware_version || '-']);
        return {
            id: n.node_id, label: n.label || '', type: n.node_type, main: getValText(n),
            zone: n.zone ? n.zone.name : 'TCE', critical: !!n.is_critical,
            desc: nodeDescriptions[n.node_id] || 'Sensor pemantau kondisi hidrologi kompleks TCE.',
            stats: stats, footer: 'Data terakhir ' + timeAgo(r.ts || n.last_seen_at)
        };
    }

    const kioskDetail = {
        id: 'POS-RW-08', label: 'Pos Satpam Utama & Monitor Kiosk 24 Jam', type: 'DASHBOARD', main: 'Kiosk Aktif',
        zone: 'RW 08', critical: false, desc: nodeDescriptions['POS-RW-08'],
        stats: [['Layar kiosk', 'Aktif 24 jam'], ['Sirine darurat', 'Dikendalikan dari sini']], footer: ''
    };

    function esc(t) { const d = document.createElement('div'); d.textContent = t; return d.innerHTML; }

    function renderPanel(d) {
        const m = typeMeta[d.type] || typeMeta['WATER_LEVEL'];
        const badge = d.critical
            ? '<span class="text-[11px] font-semibold px-2 py-0.5 rounded-md bg-red-950 text-red-300 border border-red-900">Titik kritis</span>'
            : '<span class="text-[11px] font-semibold px-2 py-0.5 rounded-md bg-emerald-950 text-emerald-300 border border-emerald-900">Normal</span>';
        return '<div class="p-4">' +
            '<div class="flex items-start justify-between gap-2">' +
                '<div class="min-w-0"><div class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full shrink-0" style="background:' + m.color + '"></span>' +
                '<span class="font-mono text-xs font-bold text-white">' + esc(d.id) + '</span></div>' +
                '<div class="mt-0.5 text-[11px] text-slate-400">' + m.label + '</div></div>' +
                '<button type="button" id="node-panel-close" class="p-1.5 -m-1 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 focus-visible:outline-2 focus-visible:outline-emerald-400" aria-label="Tutup detail titik">' +
                '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg></button>' +
            '</div>' +
            '<h4 class="mt-3 text-sm font-semibold text-white leading-snug">' + esc(d.label) + '</h4>' +
            '<div class="mt-3 flex items-end justify-between gap-2"><div class="text-3xl font-bold tracking-tight text-white font-mono">' + esc(d.main) + '</div>' + badge + '</div>' +
            '<div class="mt-1 text-[11px] text-slate-400">' + esc(d.zone) + '</div>' +
            '<p class="mt-3 text-xs leading-relaxed text-slate-300">' + esc(d.desc) + '</p>' +
            '<dl class="mt-3 divide-y divide-slate-800 border-t border-slate-800">' +
                d.stats.map(function (x) {
                    return '<div class="flex items-baseline justify-between gap-3 py-1.5 text-xs"><dt class="text-slate-400">' + x[0] + '</dt><dd class="font-semibold text-slate-100 text-right">' + esc(String(x[1])) + '</dd></div>';
                }).join('') +
            '</dl>' +
            (d.footer ? '<p class="mt-2 text-[11px] text-slate-400">' + esc(d.footer) + '</p>' : '') +
        '</div>';
    }

    // ── 3. Data Nodes dari Server ──
    const nodesData = @json($nodes);

    // ── 4. Peta Site Plan (CRS.Simple — Default Aktif) ──
    const imgWidth = 650, imgHeight = 530;
    const sitePlanBounds = [[0, 0], [imgHeight, imgWidth]];

    const mapSitePlan = L.map('map-siteplan', {
        crs: L.CRS.Simple, minZoom: -2, maxZoom: 2.5,
        zoomSnap: 0.1, attributionControl: false,
        // Peta diam: zoom hanya terjadi lewat klik pin
        zoomControl: false, dragging: false, scrollWheelZoom: false, doubleClickZoom: false,
        touchZoom: false, boxZoom: false, keyboard: false
    });
    L.imageOverlay('{{ asset('images/tce_site_plan.png') }}', sitePlanBounds).addTo(mapSitePlan);
    mapSitePlan.fitBounds(sitePlanBounds);

    // Klik pin: zoom ke pin dan tampilkan panel detail. Tutup panel: kembali ke ukuran semula.
    const panelEl = document.getElementById('node-panel');
    const wide = () => window.matchMedia('(min-width: 640px)').matches;
    let activeId = null;

    function selectNode(id, detail, latlng) {
        activeId = id;
        panelEl.innerHTML = renderPanel(detail);
        panelEl.hidden = false;
        panelEl.scrollTop = 0;
        document.getElementById('node-panel-close').addEventListener('click', clearSelection);
        mapSitePlan.invalidateSize();
        const z = Math.min(mapSitePlan.getMaxZoom(), mapSitePlan.getBoundsZoom(sitePlanBounds) + 0.9);
        // Di layar lebar panel menutup sisi kanan peta: geser pusat agar pin tetap terlihat di sisi kiri
        const shift = wide() ? 144 : 0;
        const pin = mapSitePlan.project(latlng, z);
        mapSitePlan.flyTo(mapSitePlan.unproject(pin.add([shift, 0]), z), z, { duration: 0.6 });
        document.querySelectorAll('#node-list button').forEach(b => b.setAttribute('aria-pressed', b.dataset.id === id));
    }

    function clearSelection() {
        if (activeId === null) return;
        activeId = null;
        panelEl.hidden = true;
        mapSitePlan.invalidateSize();
        mapSitePlan.flyToBounds(sitePlanBounds, { duration: 0.6 });
        document.querySelectorAll('#node-list button').forEach(b => b.setAttribute('aria-pressed', 'false'));
    }
    mapSitePlan.on('click', clearSelection);
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') clearSelection(); });

    // Koordinat [leafY, leafX] = [imgHeight - pixelY, pixelX] berdasarkan deteksi piksel
    const sitePlanHotspots = {
        'WL-11-01': [489, 167],
        'WL-06-02': [468, 118],
        'WL-06-01': [381, 111],
        'WL-09-01': [394, 260],
        'WL-09-02': [389, 299],
        'WF-01-01': [481, 266],
        'WF-02-01': [425, 365],
        'WF-06-01': [332, 299],
        'WF-11-01': [118, 391],
        'RN-07-01': [348, 101],
        'RN-08-01': [203, 493],
    };

    const markersById = {};
    nodesData.forEach(n => {
        const coords = sitePlanHotspots[n.node_id];
        if (!coords) return;
        const color = nodeColors[n.node_type] || '#2563eb';
        markersById[n.node_id] = L.marker(coords, { icon: createTeardropIcon(color, n.is_critical, 26), keyboard: true, title: n.node_id + ': ' + (n.label || '') })
            .addTo(mapSitePlan)
            .bindTooltip('<b>' + n.node_id + '</b>: ' + getValText(n), { direction: 'top', offset: [0, -36] })
            .on('click', () => selectNode(n.node_id, buildDetail(n), coords));
    });

    // Daftar semua titik di bawah peta: klik/Enter membuka popup titik yang sama
    const nodeListEl = document.getElementById('node-list');
    const listItems = nodesData.filter(n => markersById[n.node_id]).map(n => ({ id: n.node_id, label: n.label || '', color: nodeColors[n.node_type] || '#2563eb', val: getValText(n) }));
    listItems.push({ id: 'POS-RW-08', label: 'Posko RW 08', color: '#ec4899', val: 'Kiosk Aktif' });
    listItems.forEach(it => {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'flex items-center gap-2 px-2.5 py-2 rounded-lg bg-slate-950 border border-slate-800 hover:border-slate-600 text-left focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-400';
        b.innerHTML = '<span class="w-2.5 h-2.5 rounded-full shrink-0" style="background:' + it.color + '"></span>' +
            '<span class="min-w-0"><span class="block font-mono text-[11px] font-bold text-white">' + it.id + '</span>' +
            '<span class="block text-[11px] text-slate-400 truncate">' + it.val + '</span></span>';
        b.dataset.id = it.id;
        b.addEventListener('click', () => (it.id === 'POS-RW-08' ? kioskMarker : markersById[it.id]).fire('click'));
        nodeListEl.appendChild(b);
    });

    // Posko Kiosk RW 08 (Pink)
    const kioskCoords = [261, 521];
    const kioskMarker = L.marker(kioskCoords, { icon: createTeardropIcon('#ec4899', false, 26), keyboard: true, title: 'POS-RW-08: Posko RW 08' })
        .addTo(mapSitePlan)
        .bindTooltip('<b>POSKO RW 08</b>: Kiosk Satpam', { direction: 'top', offset: [0, -36] })
        .on('click', () => selectNode('POS-RW-08', kioskDetail, kioskCoords));

    // 2. Grafik Time-Series Chart.js
    const ctx = document.getElementById('timeSeriesChart').getContext('2d');
    const chartReadings = @json($chartReadings);

    const labels = chartReadings.map(r => new Date(r.ts).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }));
    const waterLevels = chartReadings.map(r => r.water_level_cm);
    const ewmaLevels = chartReadings.map(r => r.water_level_ewma);

    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'TMA (cm)',
                    data: waterLevels,
                    borderColor: '#38bdf8',
                    backgroundColor: 'rgba(56, 189, 248, 0.05)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.2
                },
                {
                    label: 'EWMA',
                    data: ewmaLevels,
                    borderColor: '#94a3b8',
                    borderWidth: 1.5,
                    borderDash: [3, 3],
                    pointRadius: 0
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { labels: { color: '#94a3b8', font: { size: 11 } } }
            },
            scales: {
                x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#64748b', font: { size: 10 } } },
                y: { min: 0, max: 180, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#64748b', font: { size: 10 } } }
            }
        }
    });

    // 3. Listener Realtime SSE
    window.addEventListener('fews:telemetry', (e) => {
        const data = e.detail;

        if (data.highest_tma !== undefined) {
            const heroTmaEl = document.getElementById('hero-tma');
            if (heroTmaEl) heroTmaEl.textContent = parseFloat(data.highest_tma).toFixed(1);
        }

        if (data.boat_status) {
            const badge = document.getElementById('boat-status-badge');
            const desc = document.getElementById('boat-status-desc');
            if (badge) {
                badge.textContent = data.boat_status.label;
                badge.className = 'inline-block px-2.5 py-1 rounded-md font-bold text-xs ' + data.boat_status.badge_class;
            }
            if (desc) desc.textContent = data.boat_status.desc;
        }

        if (data.nodes && Array.isArray(data.nodes)) {
            data.nodes.forEach(n => {
                const card = document.getElementById('node-card-' + n.node_id);
                if (card && n.water_level_cm !== null && n.water_level_cm !== undefined) {
                    const valEl = card.querySelector('.node-val');
                    if (valEl) valEl.textContent = parseFloat(n.water_level_cm).toFixed(1);
                }
            });
        }

        if (data.highest_tma !== undefined) {
            chart.data.labels.push(data.time_formatted);
            chart.data.datasets[0].data.push(data.highest_tma);
            chart.data.datasets[1].data.push(data.highest_tma);
            if (chart.data.labels.length > 25) {
                chart.data.labels.shift();
                chart.data.datasets[0].data.shift();
                chart.data.datasets[1].data.shift();
            }
            chart.update('none');
        }
    });

    function openManualAlertModal() {
        const modal = document.getElementById('manual-alert-modal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    function closeManualAlertModal() {
        const modal = document.getElementById('manual-alert-modal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    async function submitManualAlert(e) {
        e.preventDefault();
        const level = document.getElementById('manual-level').value;
        const zone_id = document.getElementById('manual-zone').value;
        const reason = document.getElementById('manual-reason').value;

        try {
            const res = await fetch('/api/v1/alerts/manual', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ level, zone_id, reason })
            });
            const data = await res.json();
            if (data.success) {
                alert('Peringatan manual berhasil dikirim.');
                window.location.reload();
            } else {
                alert('Gagal: ' + data.message);
            }
        } catch (err) {
            alert('Kesalahan jaringan: ' + err.message);
        }
    }

    async function acknowledgeAlert(id) {
        if (!confirm('Acknowledge peringatan ini sebagai operator?')) return;
        try {
            const res = await fetch(`/api/v1/alerts/${id}/acknowledge`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
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

    async function triggerSirenTest() {
        if (!confirm('Uji sirine selama 10 detik di Pos Satpam?')) return;
        try {
            const res = await fetch('/api/v1/system/siren-test', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            });
            const data = await res.json();
            alert(data.message);
        } catch (err) {
            alert('Gagal: ' + err.message);
        }
    }
</script>
@endpush
