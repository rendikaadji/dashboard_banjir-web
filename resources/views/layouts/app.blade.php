<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <title>@yield('title', 'Dashboard') | TCE FloodGuard FEWS RW 08</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">

    <!-- Leaflet CSS & JS for Maps -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <!-- Chart.js for Time-Series -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        .leaflet-container { isolation: isolate; }
    </style>
</head>
<body class="bg-[#fbfdf8] text-forest min-h-screen antialiased">
    @php
        $navGroups = [
            'Pantau' => [
                ['route' => 'dashboard', 'pattern' => 'dashboard*', 'label' => 'Dashboard', 'icon' => 'layout-dashboard'],
                ['route' => 'nodes.index', 'pattern' => 'nodes*', 'label' => 'Sensor', 'icon' => 'cpu'],
                ['route' => 'alerts.index', 'pattern' => 'alerts*', 'label' => 'Riwayat Alert', 'icon' => 'history'],
            ],
            'Kelola' => [
                ['route' => 'thresholds.index', 'pattern' => 'thresholds*', 'label' => 'Ambang Batas', 'icon' => 'sliders'],
                ['route' => 'recipients.index', 'pattern' => 'recipients*', 'label' => 'Penerima WA', 'icon' => 'users'],
            ],
            'Laporan' => [
                ['route' => 'analytics.index', 'pattern' => 'analytics*', 'label' => 'KPI EPICS', 'icon' => 'bar-chart-3'],
                ['route' => 'system.health', 'pattern' => 'system*', 'label' => 'Audit & Log', 'icon' => 'shield-check'],
            ],
        ];
        $focus = 'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-lime';
    @endphp

    <!-- Bar atas hanya untuk layar kecil: membuka sidebar -->
    <header class="lg:hidden sticky top-0 z-40 flex items-center justify-between h-14 px-4 bg-forest text-white border-b border-white/10">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 {{ $focus }} rounded">
            <i data-lucide="shield" class="w-5 h-5 text-lime"></i>
            <span class="font-bold text-sm text-white">TCE <span class="text-lime font-semibold">FloodGuard</span></span>
        </a>
        <button id="sidebar-open" type="button" class="p-2.5 rounded-lg bg-white/10 text-white border border-white/20 {{ $focus }}" aria-label="Buka menu navigasi" aria-controls="sidebar" aria-expanded="false">
            <i data-lucide="menu" class="w-5 h-5"></i>
        </button>
    </header>

    <!-- Lapisan gelap di belakang sidebar saat terbuka di layar kecil -->
    <div id="sidebar-backdrop" class="hidden lg:hidden fixed inset-0 z-40 bg-black/60"></div>

    <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 -translate-x-full lg:translate-x-0 transition-transform duration-200 motion-reduce:transition-none bg-forest text-white flex flex-col" aria-label="Navigasi utama">
        <div class="flex items-center justify-between h-16 px-4 border-b border-white/10 shrink-0">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 {{ $focus }} rounded">
                <div class="w-8 h-8 rounded-lg bg-lime flex items-center justify-center shrink-0">
                    <i data-lucide="shield" class="w-4 h-4 text-forest"></i>
                </div>
                <div class="leading-tight">
                    <div class="font-bold text-sm text-white whitespace-nowrap">TCE <span class="text-lime font-semibold">FloodGuard</span></div>
                    <div class="text-[11px] text-slate-300">RW 08 Taman Cipulir Estate</div>
                </div>
            </a>
            <button id="sidebar-close" type="button" class="lg:hidden p-2 rounded-lg text-slate-300 hover:text-white {{ $focus }}" aria-label="Tutup menu navigasi">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-5">
            @foreach($navGroups as $groupLabel => $items)
                <div>
                    <p class="px-3 mb-1.5 text-[11px] font-semibold text-lime/80">{{ $groupLabel }}</p>
                    <ul class="space-y-0.5">
                        @foreach($items as $item)
                            @php $isActive = request()->routeIs($item['pattern']) || (request()->routeIs('home') && $item['route'] === 'dashboard'); @endphp
                            <li>
                                <a href="{{ route($item['route']) }}" @if($isActive) aria-current="page" @endif
                                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors {{ $focus }} {{ $isActive ? 'bg-lime text-forest font-bold' : 'text-slate-200 hover:text-white hover:bg-white/10' }}">
                                    <i data-lucide="{{ $item['icon'] }}" class="w-4 h-4 shrink-0 {{ $isActive ? 'text-forest' : 'text-slate-300' }}"></i>
                                    <span>{{ $item['label'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </nav>

        <div class="shrink-0 border-t border-white/10 p-3 space-y-2">
            <a href="{{ route('kiosk') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-slate-200 hover:text-white hover:bg-white/10 {{ $focus }}" title="Buka layar kiosk pos satpam">
                <i data-lucide="tv" class="w-4 h-4 text-slate-300"></i><span>Layar Kiosk</span>
            </a>
            <a href="{{ route('home') }}" target="_blank" rel="noopener" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-slate-200 hover:text-white hover:bg-white/10 {{ $focus }}" title="Buka halaman status publik warga">
                <i data-lucide="globe" class="w-4 h-4 text-slate-300"></i><span>Portal Warga</span><span class="sr-only">(tab baru)</span>
            </a>
            <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-white/5 border border-white/10">
                <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0" aria-hidden="true"></span>
                <span id="live-clock" class="font-mono text-xs text-slate-300 whitespace-nowrap">--:--:-- WIB</span>
            </div>
        </div>
    </aside>

    <div class="lg:pl-64 min-h-screen flex flex-col admin-theme">
        <main class="flex-1 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            @if(session('success'))
                <div class="mb-6 p-4 rounded-xl bg-emerald-950/40 border border-emerald-800/80 text-emerald-300 flex items-center justify-between">
                    <div class="flex items-center gap-2 text-sm font-medium">
                        <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-400"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                    <button type="button" onclick="this.parentElement.remove()" class="px-2 text-slate-400 hover:text-white {{ $focus }}" aria-label="Tutup pesan">&times;</button>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-rose-950/40 border border-rose-800/80 text-rose-300 space-y-1">
                    <div class="flex items-center gap-2 font-semibold text-sm">
                        <i data-lucide="alert-triangle" class="w-4 h-4 text-rose-400"></i>
                        <span>Terdapat kesalahan input:</span>
                    </div>
                    <ul class="list-disc list-inside text-xs pl-4 space-y-0.5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>

        <footer class="border-t border-slate-800 bg-slate-900 py-5 text-xs text-slate-400">
            <div class="px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-2 text-center sm:text-left">
                <p>&copy; 2026 <strong>TCE FloodGuard</strong>. Proyek pengabdian EPICS in IEEE 2026, Universitas Budi Luhur, bersama Pengurus RW 08 Taman Cipulir Estate.</p>
                <p>Edge Server Pos Satpam (Raspberry Pi 5)</p>
            </div>
        </footer>
    </div>

    <script>
        lucide.createIcons();

        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebar-backdrop');
        const openBtn = document.getElementById('sidebar-open');
        function setSidebar(open) {
            sidebar.classList.toggle('-translate-x-full', !open);
            backdrop.classList.toggle('hidden', !open);
            openBtn.setAttribute('aria-expanded', open);
            if (open) sidebar.querySelector('a').focus(); else if (document.activeElement && sidebar.contains(document.activeElement)) openBtn.focus();
        }
        openBtn.addEventListener('click', () => setSidebar(true));
        document.getElementById('sidebar-close').addEventListener('click', () => setSidebar(false));
        backdrop.addEventListener('click', () => setSidebar(false));
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') setSidebar(false); });

        function updateClock() {
            const clockEl = document.getElementById('live-clock');
            if (clockEl) {
                const now = new Date();
                clockEl.textContent = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ' WIB';
            }
        }
        setInterval(updateClock, 1000);
        updateClock();

        let evtSource = null;
        function initRealtimeSSE() {
            if (evtSource) evtSource.close();
            try {
                evtSource = new EventSource('/api/v1/stream');
                evtSource.addEventListener('telemetry', (e) => {
                    const data = JSON.parse(e.data);
                    window.dispatchEvent(new CustomEvent('fews:telemetry', { detail: data }));
                });
                evtSource.onerror = () => {
                    setTimeout(initRealtimeSSE, 5000);
                };
            } catch (err) {
                console.error('SSE Error:', err);
            }
        }
        document.addEventListener('DOMContentLoaded', initRealtimeSSE);
    </script>
    @stack('scripts')
</body>
</html>
