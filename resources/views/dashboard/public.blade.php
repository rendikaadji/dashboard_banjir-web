<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Pantauan Banjir RW 08 — Taman Cipulir Estate</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen flex flex-col justify-between py-8 px-4 sm:px-6 antialiased">

    <!-- Container Utama: Bersih, Rapi, Aksesibel -->
    <div class="max-w-xl w-full mx-auto space-y-6">

        <!-- Kop Resmi Wilayah RW 08 -->
        <header class="text-center space-y-1">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-200 text-slate-700 text-xs font-semibold">
                <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                <span>Pusat Informasi Siaga Bencana RW 08</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight pt-1">
                Taman Cipulir Estate
            </h1>
            <p class="text-xs sm:text-sm text-slate-600">
                Kel. Cipadu Jaya, Kec. Larangan, Kota Tangerang
            </p>
            <p class="text-xs text-slate-500 font-mono pt-1">
                Pembaruan: {{ \Carbon\Carbon::now()->translatedFormat('d F Y, H:i') }} WIB
            </p>
        </header>

        <!-- Kartu Status Utama -->
        @php
            $statusConfigs = [
                0 => [
                    'label' => 'NORMAL (AMAN)',
                    'bg' => 'bg-emerald-50 border-emerald-300 text-emerald-950',
                    'badge' => 'bg-emerald-600 text-white',
                    'desc' => 'Tinggi air seluruh saluran di bawah ambang waspada. Aktivitas warga dan lalu lintas kompleks berjalan normal.',
                ],
                1 => [
                    'label' => 'WASPADA',
                    'bg' => 'bg-amber-50 border-amber-300 text-amber-950',
                    'badge' => 'bg-amber-500 text-white',
                    'desc' => 'Permukaan air saluran pembuang mulai naik. Mohon warga di sekitar saluran utama tetap waspada terhadap cuaca.',
                ],
                2 => [
                    'label' => 'SIAGA BANJIR',
                    'bg' => 'bg-orange-50 border-orange-300 text-orange-950',
                    'badge' => 'bg-orange-600 text-white',
                    'desc' => 'Genangan air telah mencapai badan jalan di titik rawan. Siapkan pemindahan kendaraan ke titik evakuasi.',
                ],
                3 => [
                    'label' => 'KRITIS / DARURAT',
                    'bg' => 'bg-red-50 border-red-300 text-red-950',
                    'badge' => 'bg-red-600 text-white animate-pulse',
                    'desc' => 'Banjir meluas di permukiman. Segera amankan aliran listrik dan utamakan keselamatan lansia dan balita.',
                ],
            ];
            $currentConfig = $statusConfigs[$currentLevel] ?? $statusConfigs[0];
        @endphp

        <section class="rounded-2xl border-2 p-6 shadow-sm {{ $currentConfig['bg'] }}">
            <div class="flex items-center justify-between gap-2">
                <span class="text-xs font-bold tracking-wider uppercase opacity-75">Status Banjir Kompleks</span>
                <span class="px-3 py-1 rounded-full text-xs font-extrabold tracking-wide uppercase {{ $currentConfig['badge'] }}">
                    {{ $currentConfig['label'] }}
                </span>
            </div>

            <p class="text-sm font-medium mt-3 leading-relaxed">
                {{ $currentConfig['desc'] }}
            </p>

            <!-- 2 Metrik Utama yang Jelas -->
            <div class="mt-6 pt-5 border-t border-black/10 grid grid-cols-2 gap-4">
                <div>
                    <span class="text-xs font-semibold opacity-75 block">Tinggi Air Tertinggi</span>
                    <div class="flex items-baseline gap-1 mt-1">
                        <span class="font-mono text-3xl font-extrabold tracking-tight">{{ number_format($highestTma, 1) }}</span>
                        <span class="text-xs font-bold opacity-75">cm</span>
                    </div>
                    <span class="text-[11px] opacity-75 block mt-0.5 truncate">{{ $highestNode?->label ?? 'Depan Masjid' }}</span>
                </div>
                <div class="border-l border-black/10 pl-4">
                    <span class="text-xs font-semibold opacity-75 block">Instruksi Perahu Karet</span>
                    <span class="text-sm font-bold block mt-1.5">{{ $boatStatus['label'] }}</span>
                    <span class="text-[11px] opacity-75 block mt-0.5">{{ $boatStatus['desc'] }}</span>
                </div>
            </div>
        </section>

        <!-- Petunjuk Tindakan Warga -->
        <section class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-3">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wide flex items-center gap-2">
                <i data-lucide="clipboard-list" class="w-4 h-4 text-slate-700"></i>
                <span>Tindakan Penting Warga</span>
            </h2>
            <ul class="space-y-3 text-xs sm:text-sm text-slate-700">
                <li class="flex items-start gap-3">
                    <span class="w-5 h-5 rounded-full bg-slate-100 text-slate-800 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">1</span>
                    <span><strong>Parkir Kendaraan:</strong> Pindahkan mobil/motor ke <strong>Masjid Al-Ikhlas</strong> atau <strong>Balai RW 08</strong> jika jalan mulai tergenang.</span>
                </li>
                <li class="flex items-start gap-3">
                    <span class="w-5 h-5 rounded-full bg-slate-100 text-slate-800 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">2</span>
                    <span><strong>Keamanan Rumah:</strong> Matikan MCB meteran listrik rumah apabila air mulai masuk ke teras atau lantai hunian.</span>
                </li>
                <li class="flex items-start gap-3">
                    <span class="w-5 h-5 rounded-full bg-slate-100 text-slate-800 flex items-center justify-center font-bold text-xs shrink-0 mt-0.5">3</span>
                    <span><strong>Prioritas Evakuasi:</strong> Segera laporkan lansia, balita, atau warga yang sedang sakit ke Pos Satpam agar segera dijemput.</span>
                </li>
            </ul>
        </section>

        <!-- Nomor Telepon Darurat Langsung Klik -->
        <section class="bg-slate-900 text-white rounded-2xl p-6 shadow-sm space-y-3">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">Kontak Darurat Bencana RW 08</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                <a href="tel:02173880000" class="flex items-center gap-3 p-3 rounded-xl bg-slate-800 hover:bg-slate-700 transition border border-slate-700">
                    <i data-lucide="phone" class="w-5 h-5 text-emerald-400 shrink-0"></i>
                    <div>
                        <span class="text-[11px] text-slate-400 block">Pos Satpam Utama (24 Jam)</span>
                        <span class="text-sm font-bold font-mono text-white">021-7388-XXXX</span>
                    </div>
                </a>
                <a href="tel:081234567890" class="flex items-center gap-3 p-3 rounded-xl bg-slate-800 hover:bg-slate-700 transition border border-slate-700">
                    <i data-lucide="message-circle" class="w-5 h-5 text-emerald-400 shrink-0"></i>
                    <div>
                        <span class="text-[11px] text-slate-400 block">Satgas Siaga Bencana RW</span>
                        <span class="text-sm font-bold font-mono text-white">0812-XXXX-XXXX</span>
                    </div>
                </a>
            </div>
        </section>

        <!-- Formulir Pendaftaran WhatsApp Resmi -->
        <section class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
            <div>
                <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <i data-lucide="bell" class="w-4 h-4 text-slate-700"></i>
                    <span>Daftar Peringatan Darurat via WhatsApp</span>
                </h2>
                <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                    Daftarkan nomor kepala keluarga untuk menerima pesan otomatis sistem saat status air mencapai Siaga atau Kritis.
                </p>
            </div>

            @if(session('success'))
                <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-300 text-emerald-900 text-xs font-semibold flex items-center gap-2">
                    <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <form action="{{ route('recipients.store') }}" method="POST" class="space-y-3">
                @csrf
                <input type="hidden" name="role_tag" value="WARGA">
                <input type="hidden" name="min_level" value="3">

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Kepala Keluarga / Warga</label>
                    <input type="text" name="full_name" required placeholder="Contoh: Budi Santoso" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs sm:text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-transparent">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor WhatsApp (+62)</label>
                        <input type="text" name="phone_e164" required placeholder="+6281234567890" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs sm:text-sm text-slate-900 font-mono placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Wilayah RT</label>
                        <select name="zone_id" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs sm:text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-transparent">
                            <option value="">Pilih RT...</option>
                            @foreach(\App\Models\Zone::all() as $z)
                                <option value="{{ $z->id }}">{{ $z->code }} — {{ $z->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <p class="text-[11px] text-slate-500 leading-relaxed">
                    Privasi terjamin: Nomor telepon hanya digunakan untuk pengiriman sinyal darurat banjir dan tidak akan disebarluaskan.
                </p>

                <button type="submit" class="w-full py-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs sm:text-sm shadow-sm transition">
                    Daftarkan Nomor WhatsApp Warga
                </button>
            </form>
        </section>

        <!-- Footer Bersih & Formal -->
        <footer class="text-center text-xs text-slate-500 space-y-1 pt-4">
            <p>Sistem Pemantauan Dini Banjir Berbasis Sensor IoT — Inisiatif EPICS in IEEE 2026.</p>
            <p>© 2026 Pengurus RW 08 Taman Cipulir Estate, Kelurahan Cipadu Jaya.</p>
        </footer>

    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
