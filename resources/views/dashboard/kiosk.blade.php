<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <title>MONITOR POS SATPAM — TCE FloodGuard RW 08</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@700;800;900&family=JetBrains+Mono:wght@700;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        .kiosk-theme-0 { background-color: #061912; color: #f8fafc; }
        .kiosk-theme-1 { background-color: #261b03; color: #fef3c7; }
        .kiosk-theme-2 { background-color: #3b1704; color: #ffedd5; }
        .kiosk-theme-3 { background-color: #450a0a; color: #fee2e2; }
    </style>
</head>
<body id="kiosk-body" class="min-h-screen w-full flex flex-col justify-between p-4 sm:p-6 lg:p-8 select-none transition-colors duration-500 overflow-y-auto lg:overflow-hidden {{ 'kiosk-theme-' . $currentLevel }}">

    <!-- Header: Informasi Pos Satpam & Jam Operasional (Responsif) -->
    <header class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-white/15 pb-4 sm:pb-6">
        <div class="flex items-center gap-3 sm:gap-4">
            <div class="w-10 h-10 sm:w-14 sm:h-14 rounded-xl bg-white/10 flex items-center justify-center border border-white/20 shrink-0">
                <i data-lucide="shield" class="w-6 h-6 sm:w-8 sm:h-8 text-white"></i>
            </div>
            <div>
                <h1 class="text-lg sm:text-2xl font-black tracking-tight uppercase">POS SATPAM RW 08</h1>
                <p class="text-xs sm:text-sm font-semibold opacity-75">Taman Cipulir Estate · Pengawasan 24 Jam</p>
            </div>
        </div>

        <div class="flex items-center justify-between w-full sm:w-auto gap-4 sm:gap-8">
            <div>
                <span class="text-[10px] sm:text-xs uppercase font-bold tracking-wider opacity-70 block mb-0.5 sm:mb-1">Status Kompleks</span>
                <span id="kiosk-level-badge" class="px-3 sm:px-5 py-1 sm:py-2 rounded-xl text-xs sm:text-xl font-black uppercase inline-block {{ $currentLevel === 3 ? 'bg-red-600 text-white' : ($currentLevel === 2 ? 'bg-orange-500 text-white' : ($currentLevel === 1 ? 'bg-amber-400 text-slate-950' : 'bg-emerald-600 text-white')) }}">
                    @if($currentLevel === 3) KRITIS (DARURAT) @elseif($currentLevel === 2) SIAGA @elseif($currentLevel === 1) WASPADA @else AMAN (NORMAL) @endif
                </span>
            </div>

            <div class="text-right pl-4 sm:pl-6 border-l border-white/15">
                <div id="kiosk-clock" class="font-mono text-xl sm:text-4xl font-black tracking-wider whitespace-nowrap">--:--:--</div>
                <div class="text-[10px] sm:text-xs font-semibold opacity-70 uppercase tracking-widest mt-0.5 sm:mt-1 whitespace-nowrap">LIVE SENSOR</div>
            </div>
        </div>
    </header>

    <!-- Bagian Tengah: Angka TMA Raksasa & Instruksi Perahu Karet (Fluid) -->
    <main class="flex-1 flex flex-col items-center justify-center text-center my-6 lg:my-0">
        <span class="text-xs sm:text-sm font-bold uppercase tracking-widest opacity-80 mb-2 px-2">
            TINGGI MUKA AIR TERTINGGI ({{ $highestNode?->label ?? 'GORONG-GORONG DEPAN MASJID' }})
        </span>

        <div class="flex items-baseline justify-center gap-2 sm:gap-4 my-2">
            <span id="kiosk-tma" class="font-mono text-6xl sm:text-8xl md:text-9xl lg:text-[130px] font-black leading-none tracking-tight">
                {{ number_format($highestTma, 1) }}
            </span>
            <span class="text-2xl sm:text-4xl font-extrabold opacity-75">CM</span>
        </div>

        <!-- Instruksi Perahu Karet (PRD §10.4) -->
        <div class="mt-4 sm:mt-6 px-2">
            <div id="kiosk-boat-card" class="px-5 sm:px-8 py-2.5 sm:py-3.5 rounded-2xl border-2 inline-flex items-center gap-3 {{ $boatStatus['badge_class'] }}">
                <i data-lucide="life-buoy" class="w-5 h-5 sm:w-7 sm:h-7 shrink-0"></i>
                <div class="text-left">
                    <span class="text-[10px] sm:text-[11px] font-bold uppercase tracking-wider block opacity-80">INSTRUKSI PERAHU KARET</span>
                    <span id="kiosk-boat-label" class="text-sm sm:text-xl font-black uppercase block">{{ $boatStatus['label'] }}</span>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer: Titik Kritis & Navigasi (Grid Responsif) -->
    <footer class="border-t border-white/15 pt-4 sm:pt-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 items-center">
            @foreach($nodes->take(3) as $node)
                <div class="p-3 sm:p-3.5 rounded-xl bg-white/5 border border-white/10 flex items-center justify-between">
                    <div>
                        <span class="font-mono font-bold text-xs opacity-90">{{ $node->node_id }}</span>
                        <p class="text-xs opacity-75 truncate max-w-[150px]">{{ $node->label }}</p>
                    </div>
                    <div class="text-right">
                        <span class="font-mono text-xl sm:text-2xl font-bold">
                            {{ number_format($node->latestReading?->water_level_cm ?? 0, 1) }}
                        </span>
                        <span class="text-xs opacity-70">cm</span>
                    </div>
                </div>
            @endforeach

            <div class="text-center sm:text-right">
                <a href="{{ route('dashboard') }}" class="w-full sm:w-auto px-4 sm:px-5 py-2 sm:py-2.5 rounded-xl bg-white/10 hover:bg-white/20 border border-white/20 text-xs font-bold inline-flex items-center justify-center gap-2 transition">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    <span>Kembali ke Dashboard</span>
                </a>
            </div>
        </div>
    </footer>

    <script>
        lucide.createIcons();

        function updateClock() {
            const clock = document.getElementById('kiosk-clock');
            if (clock) {
                const d = new Date();
                clock.textContent = d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            }
        }
        setInterval(updateClock, 1000);
        updateClock();

        const sse = new EventSource('/api/v1/stream');
        sse.addEventListener('telemetry', (e) => {
            const data = JSON.parse(e.data);
            if (data.highest_tma !== undefined) {
                document.getElementById('kiosk-tma').textContent = parseFloat(data.highest_tma).toFixed(1);
            }
            if (data.boat_status) {
                document.getElementById('kiosk-boat-label').textContent = data.boat_status.label;
            }
            const body = document.getElementById('kiosk-body');
            const badge = document.getElementById('kiosk-level-badge');
            body.className = `min-h-screen w-full flex flex-col justify-between p-4 sm:p-6 lg:p-8 select-none transition-colors duration-500 overflow-y-auto lg:overflow-hidden kiosk-theme-${data.level}`;

            if (data.level === 3) {
                badge.textContent = 'KRITIS (DARURAT)';
                badge.className = 'px-3 sm:px-5 py-1 sm:py-2 rounded-xl text-xs sm:text-xl font-black uppercase bg-red-600 text-white';
            } else if (data.level === 2) {
                badge.textContent = 'SIAGA';
                badge.className = 'px-3 sm:px-5 py-1 sm:py-2 rounded-xl text-xs sm:text-xl font-black uppercase bg-orange-500 text-white';
            } else if (data.level === 1) {
                badge.textContent = 'WASPADA';
                badge.className = 'px-3 sm:px-5 py-1 sm:py-2 rounded-xl text-xs sm:text-xl font-black uppercase bg-amber-400 text-slate-950';
            } else {
                badge.textContent = 'AMAN (NORMAL)';
                badge.className = 'px-3 sm:px-5 py-1 sm:py-2 rounded-xl text-xs sm:text-xl font-black uppercase bg-emerald-600 text-white';
            }
        });
    </script>
</body>
</html>
