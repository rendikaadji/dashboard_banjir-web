<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — TCE FloodGuard | FEWS RW 08</title>

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
<body class="bg-slate-950 text-slate-100 min-h-screen flex flex-col antialiased">

    <!-- Top Navigation Bar: Responsif & Fleksibel untuk Semua Ukuran Layar -->
    <header class="sticky top-0 z-50 bg-slate-900 border-b border-slate-800">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 gap-4">
                
                <!-- 1. Sisi Kiri: Brand Resmi -->
                <div class="flex items-center gap-3 shrink-0">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 group">
                        <div class="w-8 h-8 rounded-lg bg-slate-800 border border-slate-700 flex items-center justify-center text-slate-300 group-hover:border-slate-600 transition shrink-0">
                            <i data-lucide="shield" class="w-4 h-4 text-emerald-400"></i>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="font-bold text-sm tracking-tight text-white whitespace-nowrap">TCE <span class="text-slate-400 font-normal">FloodGuard</span></span>
                            <span class="text-[10px] font-semibold uppercase tracking-wider px-1.5 py-0.5 rounded bg-slate-800 text-slate-400 border border-slate-700">RW 08</span>
                        </div>
                    </a>
                </div>

                <!-- 2. Sisi Tengah: Tab Navigasi (Tampil Elegan di Layar Lebar >= 1280px) -->
                <nav class="hidden xl:flex items-center gap-1">
                    @php
                        $navItems = [
                            ['route' => 'dashboard', 'pattern' => 'dashboard*', 'label' => 'Dashboard', 'icon' => 'layout-dashboard'],
                            ['route' => 'nodes.index', 'pattern' => 'nodes*', 'label' => 'Sensor', 'icon' => 'cpu'],
                            ['route' => 'alerts.index', 'pattern' => 'alerts*', 'label' => 'Riwayat Alert', 'icon' => 'history'],
                            ['route' => 'thresholds.index', 'pattern' => 'thresholds*', 'label' => 'Ambang Batas', 'icon' => 'sliders'],
                            ['route' => 'recipients.index', 'pattern' => 'recipients*', 'label' => 'Penerima WA', 'icon' => 'users'],
                            ['route' => 'analytics.index', 'pattern' => 'analytics*', 'label' => 'KPI EPICS', 'icon' => 'bar-chart-3'],
                            ['route' => 'system.health', 'pattern' => 'system*', 'label' => 'Audit & Log', 'icon' => 'shield-check'],
                        ];
                    @endphp

                    @foreach($navItems as $item)
                        @php $isActive = request()->routeIs($item['pattern']) || (request()->routeIs('home') && $item['route'] === 'dashboard'); @endphp
                        <a href="{{ route($item['route']) }}" 
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap shrink-0 transition {{ $isActive ? 'bg-slate-800 text-white font-semibold border border-slate-700 shadow-xs' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/40' }}">
                            <i data-lucide="{{ $item['icon'] }}" class="w-3.5 h-3.5 {{ $isActive ? 'text-emerald-400' : 'text-slate-500' }}"></i>
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </nav>

                <!-- 3. Sisi Kanan: Status Digital & Tombol Aksi Responsif -->
                <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                    <!-- Jam Operasional & Status SSE (Mulai dari Tablet >= 640px) -->
                    <div class="hidden sm:flex items-center gap-2 px-2.5 py-1.5 rounded-lg bg-slate-950 border border-slate-800 text-xs shrink-0">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                        <span id="live-clock" class="font-mono text-slate-300 text-xs whitespace-nowrap">--:--:-- WIB</span>
                    </div>

                    <!-- Tombol Cepat Desktop (Layar Lebar >= 1280px) -->
                    <div class="hidden xl:flex items-center gap-1.5">
                        <a href="{{ route('kiosk') }}" 
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition shadow-xs whitespace-nowrap"
                           title="Buka Mode Layar Kiosk Pos Satpam">
                            <i data-lucide="tv" class="w-3.5 h-3.5 text-slate-400"></i>
                            <span>Layar Kiosk</span>
                        </a>

                        <a href="{{ route('public') }}" target="_blank"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 transition shadow-xs whitespace-nowrap"
                           title="Buka Halaman Status Publik Warga RW 08">
                            <i data-lucide="globe" class="w-3.5 h-3.5 text-slate-400"></i>
                            <span>Portal Warga</span>
                            <i data-lucide="arrow-up-right" class="w-3 h-3 text-slate-500"></i>
                        </a>
                    </div>

                    <!-- Tombol Menu Mobile / Tablet (< 1280px) -->
                    <button id="mobile-menu-btn" class="xl:hidden p-2 rounded-lg bg-slate-800 text-slate-300 hover:text-white border border-slate-700" aria-label="Buka Menu Navigasi">
                        <i data-lucide="menu" class="w-5 h-5"></i>
                    </button>
                </div>

            </div>
        </div>

        <!-- Menu Mobile Drawer: Fleksibel untuk Layar Kecil & Tablet -->
        <div id="mobile-menu" class="hidden xl:hidden border-t border-slate-800 bg-slate-900 px-4 py-3 space-y-1.5">
            @foreach($navItems as $item)
                @php $isActive = request()->routeIs($item['pattern']) || (request()->routeIs('home') && $item['route'] === 'dashboard'); @endphp
                <a href="{{ route($item['route']) }}" class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium transition {{ $isActive ? 'bg-slate-800 text-white font-semibold border border-slate-700' : 'text-slate-300 hover:bg-slate-800' }}">
                    <i data-lucide="{{ $item['icon'] }}" class="w-4 h-4 {{ $isActive ? 'text-emerald-400' : 'text-slate-500' }}"></i>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach

            <!-- Tombol Aksi Kiosk & Warga di Drawer Mobile -->
            <div class="pt-2 border-t border-slate-800 grid grid-cols-2 gap-2">
                <a href="{{ route('kiosk') }}" class="flex items-center justify-center gap-1.5 py-2 rounded-lg text-xs font-medium bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700">
                    <i data-lucide="tv" class="w-3.5 h-3.5 text-slate-400"></i>
                    <span>Layar Kiosk</span>
                </a>
                <a href="{{ route('public') }}" target="_blank" class="flex items-center justify-center gap-1.5 py-2 rounded-lg text-xs font-medium bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700">
                    <i data-lucide="globe" class="w-3.5 h-3.5 text-slate-400"></i>
                    <span>Portal Warga ↗</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content Area: Rapi & Terpusat di Layar Ultra-Wide -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        @if(session('success'))
            <div class="mb-6 p-4 rounded-xl bg-emerald-950/40 border border-emerald-800/80 text-emerald-300 flex items-center justify-between">
                <div class="flex items-center gap-2 text-sm font-medium">
                    <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-400"></i>
                    <span>{{ session('success') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-white">&times;</button>
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

    <!-- Footer Formal & Ringkas -->
    <footer class="border-t border-slate-800 bg-slate-900 py-5 text-xs text-slate-500">
        <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-2 text-center sm:text-left">
            <p>© 2026 <strong>TCE FloodGuard</strong> — Proyek Pengabdian EPICS in IEEE di RW 08 Taman Cipulir Estate.</p>
            <div class="flex items-center justify-center gap-3 text-slate-400">
                <span>Edge Server Pos Satpam (Raspberry Pi 5)</span>
                <span>·</span>
                <span>Latensi &lt; 1 Detik</span>
            </div>
        </div>
    </footer>

    <script>
        lucide.createIcons();

        const menuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        if (menuBtn && mobileMenu) {
            menuBtn.addEventListener('click', () => mobileMenu.classList.toggle('hidden'));
        }

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
