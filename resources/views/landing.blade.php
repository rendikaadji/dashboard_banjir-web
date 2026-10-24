<!DOCTYPE html>
<html lang="id" class="scroll-smooth motion-reduce:scroll-auto">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="{{ asset('favicon.svg') }}?v=2" type="image/svg+xml">
    <link rel="icon" href="{{ asset('favicon-32.png') }}?v=2" type="image/png" sizes="32x32">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v=2">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}?v=2">
    <title>Status Banjir RW 08 Taman Cipulir Estate</title>
    <meta name="description" content="Status banjir terkini, tinggi air per wilayah, riwayat kejadian, dan cara menerima peringatan lewat WhatsApp untuk warga RW 08 Taman Cipulir Estate.">
    <meta name="theme-color" content="#fbfdf8">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        .gauge-mark { transform: translateY(var(--y)); animation: gauge-in .9s cubic-bezier(.2, .8, .2, 1); }
        @keyframes gauge-in { from { transform: translateY(var(--from)); } }
        @media (prefers-reduced-motion: reduce) { .gauge-mark { animation: none; } }
        summary::-webkit-details-marker { display: none; }
    </style>
</head>
@php
    // Warna level mengikuti PRD §13.2; pasangan teks selalu lolos kontras 4.5:1
    $levels = [
        0 => ['name' => 'Aman', 'badge' => 'bg-emerald-700 text-white', 'fill' => '#047857', 'bar' => 'bg-emerald-600'],
        1 => ['name' => 'Waspada', 'badge' => 'bg-yellow-400 text-slate-900', 'fill' => '#facc15', 'bar' => 'bg-yellow-400'],
        2 => ['name' => 'Siaga', 'badge' => 'bg-orange-700 text-white', 'fill' => '#c2410c', 'bar' => 'bg-orange-600'],
        3 => ['name' => 'Kritis', 'badge' => 'bg-red-700 text-white', 'fill' => '#b91c1c', 'bar' => 'bg-red-600'],
    ];
    $noData = ['name' => 'Data belum tersedia', 'badge' => 'bg-slate-600 text-white', 'fill' => '#475569', 'bar' => 'bg-slate-400'];

    // Level dari alert engine selalu menang; data basi tidak boleh tampil sebagai Aman
    $known = $level > 0 || $dataOk;
    $headline = $level > 0 ? $levels[$level] : ($dataOk ? $levels[0] : $noData);

    $summaries = [
        0 => 'Tinggi air di seluruh saluran masih di bawah batas waspada. Aktivitas warga berjalan normal.',
        1 => 'Air saluran mulai naik. Warga di sekitar saluran utama diminta memantau cuaca dan halaman ini.',
        2 => 'Genangan sudah mencapai badan jalan di titik rawan. Siapkan pemindahan kendaraan ke titik evakuasi.',
        3 => 'Banjir meluas di permukiman. Utamakan keselamatan, matikan listrik di lantai bawah, ikuti arahan petugas.',
    ];
    $steps = [
        0 => ['Simpan kontak pengurus RW 08 di ponsel.', 'Gabung grup WhatsApp peringatan di bawah.', 'Buka halaman ini lagi saat hujan deras.'],
        1 => ['Periksa saluran di depan rumah dan singkirkan sampah.', 'Siapkan senter, power bank, dan dokumen penting.', 'Pantau halaman ini setiap beberapa menit.'],
        2 => ['Mulai pindahkan kendaraan ke titik evakuasi.', 'Kumpulkan dokumen, obat, dan barang berharga ke tempat tinggi.', 'Ikuti arahan satpam dan satgas banjir.'],
        3 => ['Pindahkan kendaraan ke tempat aman sesuai arahan petugas'.($evacuationPoint ? ': '.$evacuationPoint : '').'.', 'Amankan barang berharga dan matikan aliran listrik rumah.', 'Dahulukan lansia, anak, dan warga sakit. Ikuti petugas.'],
    ];
    $fmt = fn ($v) => $v === null ? '-' : number_format($v, 1, ',', '.');
    $focus = 'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-leaf';
    $navItems = [['#status', 'Status'], ['#kawasan', 'Kawasan TCE'], ['#peta', 'Wilayah'], ['#riwayat', 'Riwayat'], ['#sistem', 'Sistem'], ['#proyek', 'Proyek']];
    $totalSensors = $sensorCounts->sum();

    // Ikon garis sederhana (24x24)
    $icons = [
        'air' => 'M12 3c3.5 4.2 6 7 6 10a6 6 0 0 1-12 0c0-3 2.5-5.8 6-10z',
        'arus' => 'M3 9c2-2 4-2 6 0s4 2 6 0 4-2 6 0M3 15c2-2 4-2 6 0s4 2 6 0 4-2 6 0',
        'hujan' => 'M7 14a4 4 0 0 1 .5-8 5 5 0 0 1 9.6 1.5A3.5 3.5 0 0 1 17 14H7zM9 17l-1 3M13 17l-1 3M17 17l-1 3',
        'pesan' => 'M5 5h14v10h-8l-6 4V5z',
    ];

    // Penggaris tinggi air: 0 cm di bawah, gaugeMax di atas
    $gH = 300; $gTop = 14;
    $yOf = fn ($v) => $gTop + (1 - min(max($v, 0), $gaugeMax) / $gaugeMax) * $gH;
    // Pita mengikuti proposal: di bawah Siaga (Level 1), Siaga (Level 2), Kritis (Level 3)
    $bandEdges = [0, (float) ($bands[2] ?? 0), (float) ($bands[3] ?? 0), $gaugeMax];
    $bandColors = [$levels[0]['fill'], $levels[2]['fill'], $levels[3]['fill']];
    $bandNames = ['Aman dan Waspada', 'Siaga', 'Kritis'];

    // Lingkaran orbit di hero: posisi tengah (x%, y%) pada busur
    $orbit = [['air', 'Tinggi air', 84, 14, 'bg-forest text-white'], ['arus', 'Arus', 92, 37, 'bg-lime text-forest'], ['hujan', 'Hujan', 92, 60, 'bg-forest text-white'], ['pesan', 'WhatsApp', 84, 83, 'bg-lime text-forest']];
@endphp
<body class="bg-[#fbfdf8] text-forest antialiased">
    <a href="#status" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:bg-white focus:px-4 focus:py-2 focus:rounded-lg {{ $focus }}">Lewati ke status banjir</a>

    {{-- Latar: cahaya hijau lembut di sudut atas, satu-satunya dekorasi latar --}}
    <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[40rem] bg-[radial-gradient(60rem_30rem_at_0%_0%,rgba(184,224,74,.22),transparent_60%)]"></div>

    <header class="sticky top-3 z-40 px-3 sm:px-6">
        <div class="max-w-6xl mx-auto h-14 sm:h-16 pl-5 pr-2 rounded-full bg-white/95 backdrop-blur shadow-lg shadow-forest/5 ring-1 ring-forest/10 flex items-center justify-between gap-4">
            <a href="#status" class="text-lg font-extrabold tracking-tight {{ $focus }} rounded">TCE <span class="text-leaf">FloodGuard</span></a>

            <nav class="hidden lg:flex items-center gap-1 text-sm font-medium" aria-label="Bagian halaman">
                @foreach ($navItems as [$href, $label])
                    <a href="{{ $href }}" class="px-3.5 py-2 rounded-full text-slate-700 hover:bg-mint hover:text-forest {{ $focus }}">{{ $label }}</a>
                @endforeach
            </nav>

            <div class="flex items-center gap-2">
                @if ($waGroupUrl)
                    <a href="{{ $waGroupUrl }}" target="_blank" rel="noopener" class="hidden sm:inline-flex items-center gap-2 min-h-11 pl-5 pr-1.5 rounded-full bg-forest text-white text-sm font-semibold hover:bg-forest-soft {{ $focus }}">Gabung grup WA<span class="sr-only"> (tab baru)</span>
                        <span class="w-8 h-8 rounded-full bg-lime text-forest flex items-center justify-center" aria-hidden="true"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17 17 7M8 7h9v9"/></svg></span></a>
                @endif
                <details class="lg:hidden relative">
                    <summary class="list-none cursor-pointer min-h-11 min-w-11 px-4 inline-flex items-center rounded-full bg-mint text-sm font-bold {{ $focus }}">Menu</summary>
                    <div class="absolute right-0 mt-3 w-60 rounded-2xl bg-white shadow-2xl ring-1 ring-forest/10 p-2">
                        @foreach ($navItems as [$href, $label])
                            <a href="{{ $href }}" onclick="this.closest('details').removeAttribute('open')" class="flex items-center min-h-11 px-4 rounded-xl text-sm font-medium hover:bg-mint {{ $focus }}">{{ $label }}</a>
                        @endforeach
                        @if ($waGroupUrl)
                            <a href="{{ $waGroupUrl }}" target="_blank" rel="noopener" class="mt-1 flex items-center justify-center min-h-11 px-4 rounded-xl bg-forest text-white text-sm font-semibold {{ $focus }}">Gabung grup WhatsApp<span class="sr-only"> (tab baru)</span></a>
                        @endif
                    </div>
                </details>
            </div>
        </div>
    </header>

    <main>
        {{-- 1. Hero: status di kiri, penggaris dan orbit sensor di kanan --}}
        <section id="status" class="max-w-6xl mx-auto px-4 sm:px-6 pt-10 sm:pt-14 pb-6 grid lg:grid-cols-12 gap-10 items-center">
            <div class="lg:col-span-7 min-w-0">
                <p class="text-sm font-medium text-slate-600">Taman Cipulir Estate, RW 08, Kel. Cipadu Jaya, Kec. Larangan, Kota Tangerang</p>
                <h1 class="mt-3 text-4xl sm:text-6xl font-extrabold tracking-tight leading-[1.05]">
                    Status banjir<br>
                    <span class="relative inline-block text-leaf">RW 08 saat ini
                        <svg aria-hidden="true" class="absolute left-0 -bottom-2 w-full h-3 text-lime" viewBox="0 0 200 12" preserveAspectRatio="none" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round"><path d="M3 8c30-6 60-6 95-3s60 3 99-2"/></svg>
                    </span>
                </h1>

                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <span class="px-4 py-2 rounded-full text-base font-extrabold {{ $headline['badge'] }}">{{ $headline['name'] }}</span>
                    <span class="text-sm text-slate-600">
                        @if ($lastDataAt) Data sensor terakhir {{ $lastDataAt->diffForHumans() }} @else Belum ada data sensor @endif
                    </span>
                </div>

                @if ($known)
                    <div class="mt-5 flex items-end gap-2">
                        <span class="text-7xl sm:text-8xl font-extrabold leading-none tracking-tight">{{ $fmt($highestTma) }}</span>
                        <span class="pb-1.5 text-xl font-bold text-leaf">cm<span class="sr-only"> tinggi air tertinggi</span></span>
                    </div>
                    <p class="mt-1 text-sm text-slate-600">Tinggi air tertinggi di saluran kompleks</p>
                @else
                    <p class="mt-5 text-2xl sm:text-3xl font-extrabold">Sensor belum mengirim data terbaru.</p>
                    <p class="mt-2 text-slate-600">Status Aman tidak ditampilkan tanpa data. Hubungi pengurus RW 08 bila hujan deras.</p>
                @endif

                <p class="mt-5 max-w-xl text-lg leading-relaxed text-slate-700">{{ $known ? $summaries[$level] : 'Pengurus RW diberi tahu otomatis bila data sensor terputus.' }}</p>

                <div class="mt-8 flex flex-wrap gap-3">
                    @if ($waGroupUrl)
                        <a href="{{ $waGroupUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-3 min-h-12 pl-6 pr-2 rounded-full bg-forest text-white font-semibold hover:bg-forest-soft {{ $focus }}">Gabung grup peringatan<span class="sr-only"> WhatsApp (tab baru)</span>
                            <span class="w-9 h-9 rounded-full bg-lime text-forest flex items-center justify-center" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17 17 7M8 7h9v9"/></svg></span></a>
                    @endif
                    <a href="#peta" class="inline-flex items-center gap-3 min-h-12 pl-6 pr-2 rounded-full bg-white ring-1 ring-forest/20 font-semibold hover:bg-mint {{ $focus }}">Lihat kondisi wilayah
                        <span class="w-9 h-9 rounded-full bg-mint text-leaf flex items-center justify-center" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M6 13l6 6 6-6"/></svg></span></a>
                </div>
            </div>

            {{-- Penggaris tinggi air + orbit sensor --}}
            <div class="lg:col-span-5">
                <div class="relative mx-auto w-full max-w-md aspect-[10/11]">
                    <div aria-hidden="true" class="absolute inset-3 rounded-[42%_58%_52%_48%/48%_44%_56%_52%] bg-gradient-to-br from-lime/35 via-mint to-white"></div>

                    <svg viewBox="0 0 260 {{ $gH + $gTop * 2 }}" class="absolute left-[4%] top-[5%] h-[90%] w-auto" role="img"
                         aria-label="{{ $known ? 'Penggaris tinggi air: '.$fmt($highestTma).' cm, status '.$headline['name'] : 'Data tinggi air belum tersedia' }}">
                        @foreach ([1, 2, 3] as $i)
                            @php $lo = $bandEdges[$i - 1]; $hi = $bandEdges[$i]; @endphp
                            <rect x="96" y="{{ $yOf($hi) }}" width="26" height="{{ max(0, $yOf($lo) - $yOf($hi)) }}" fill="{{ $bandColors[$i - 1] }}" opacity="{{ $known ? '1' : '.35' }}"/>
                        @endforeach
                        @for ($v = 0; $v <= $gaugeMax; $v += 25)
                            <line x1="{{ $v % 50 === 0 ? 76 : 84 }}" x2="96" y1="{{ $yOf($v) }}" y2="{{ $yOf($v) }}" stroke="#64748b" stroke-width="1"/>
                            @if ($v % 50 === 0)
                                <text x="68" y="{{ $yOf($v) + 4 }}" text-anchor="end" fill="#334155" font-size="12" font-family="JetBrains Mono, monospace">{{ $v }}</text>
                            @endif
                        @endfor
                        @if ($known && $highestTma !== null)
                            <g class="gauge-mark" style="--y: {{ $yOf($highestTma) }}px; --from: {{ $yOf(0) }}px">
                                <line x1="88" x2="130" y1="0" y2="0" stroke="#0a2118" stroke-width="3" stroke-linecap="round"/>
                                <rect x="130" y="-13" width="78" height="26" rx="13" fill="#0a2118"/>
                                <text x="169" y="5" text-anchor="middle" fill="#fff" font-size="14" font-weight="700" font-family="JetBrains Mono, monospace">{{ $fmt($highestTma) }}</text>
                            </g>
                        @endif
                    </svg>

                    <svg aria-hidden="true" class="absolute inset-0 w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none" fill="none"><path d="M84 14C99 26 99 71 84 83" stroke="#2d7a35" stroke-opacity=".45" stroke-width="1.5" stroke-dasharray="3 5" vector-effect="non-scaling-stroke"/></svg>
                    @foreach ($orbit as [$ico, $label, $x, $y, $tone])
                        <div class="absolute -translate-x-1/2 -translate-y-1/2 flex flex-col items-center" style="left: {{ $x }}%; top: {{ $y }}%">
                            <span class="w-11 h-11 sm:w-12 sm:h-12 rounded-full shadow-lg ring-4 ring-white flex items-center justify-center {{ $tone }}">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $icons[$ico] }}"/></svg>
                            </span>
                            <span class="mt-1.5 text-[11px] font-bold">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- 2. Ringkasan cepat --}}
        <section class="max-w-6xl mx-auto px-4 sm:px-6">
            <dl class="grid grid-cols-2 md:grid-cols-4 gap-px rounded-3xl overflow-hidden bg-forest/10 ring-1 ring-forest/10 shadow-lg shadow-forest/5">
                <div class="bg-white p-5 sm:p-6"><dt class="text-sm text-slate-600">Status</dt><dd class="mt-2"><span class="inline-block px-3 py-1 rounded-full text-sm font-bold {{ $headline['badge'] }}">{{ $headline['name'] }}</span></dd></div>
                <div class="bg-white p-5 sm:p-6"><dt class="text-sm text-slate-600">Air tertinggi</dt><dd class="mt-1 text-2xl sm:text-3xl font-extrabold">{{ $known ? $fmt($highestTma).' cm' : '-' }}</dd></div>
                <div class="bg-white p-5 sm:p-6"><dt class="text-sm text-slate-600">Data terakhir</dt><dd class="mt-1 text-lg sm:text-xl font-bold leading-tight">{{ $lastDataAt ? $lastDataAt->diffForHumans() : 'belum ada' }}</dd></div>
                <div class="bg-white p-5 sm:p-6"><dt class="text-sm text-slate-600">Sensor terpasang</dt><dd class="mt-1 text-2xl sm:text-3xl font-extrabold">{{ $totalSensors }}</dd></div>
            </dl>
        </section>

        {{-- 3. Tindakan --}}
        <section class="max-w-6xl mx-auto px-4 sm:px-6 mt-8">
            <div class="rounded-3xl bg-mint p-6 sm:p-10 grid md:grid-cols-5 gap-8">
                <div class="md:col-span-3">
                    <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Yang perlu dilakukan sekarang</h2>
                    <ol class="mt-6 space-y-4">
                        @foreach ($steps[$known ? $level : 0] as $i => $step)
                            <li class="flex gap-4 items-start">
                                <span class="shrink-0 w-9 h-9 rounded-full bg-lime text-forest font-extrabold flex items-center justify-center" aria-hidden="true">{{ $i + 1 }}</span>
                                <span class="pt-1 text-base sm:text-lg leading-snug">{{ $step }}</span>
                            </li>
                        @endforeach
                    </ol>
                </div>
                <dl class="md:col-span-2 rounded-2xl bg-white p-6 space-y-4 text-sm self-start">
                    <div><dt class="text-slate-600">Kontak pengurus RW 08</dt><dd class="mt-0.5 text-base font-bold">@if (config('tce.contact_phone'))<a class="underline underline-offset-2 {{ $focus }}" href="tel:{{ preg_replace('/[^+\d]/', '', config('tce.contact_phone')) }}">{{ config('tce.contact_phone') }}</a>@else<span class="border border-dashed border-slate-400 px-1.5 rounded">[ISI DATA]</span>@endif</dd></div>
                    <div><dt class="text-slate-600">Surel</dt><dd class="mt-0.5 text-base font-bold break-all">@if (config('tce.contact_email'))<a class="underline underline-offset-2 {{ $focus }}" href="mailto:{{ config('tce.contact_email') }}">{{ config('tce.contact_email') }}</a>@else<span class="border border-dashed border-slate-400 px-1.5 rounded">[ISI DATA]</span>@endif</dd></div>
                    <p class="text-slate-600 leading-snug">Kendaraan dan warga dievakuasi ke titik aman sesuai arahan petugas.</p>
                </dl>
            </div>
        </section>

        {{-- 4. Yang dipantau: empat kartu dengan jumlah nyata --}}
        <section id="sistem" class="max-w-6xl mx-auto px-4 sm:px-6 pt-20 sm:pt-28">
            <div class="text-center max-w-2xl mx-auto">
                <p class="text-xs font-bold uppercase tracking-widest text-leaf">Pemantauan</p>
                <h2 class="mt-2 text-3xl sm:text-5xl font-extrabold tracking-tight">Apa yang dipantau sistem ini</h2>
                <p class="mt-3 text-lg text-slate-700">Sensor di titik rawan mengirim data ke server lokal (Raspberry Pi 5) yang layarnya dipantau petugas keamanan, dengan tenaga surya agar tetap menyala saat listrik padam.</p>
            </div>
            <div class="mt-10 grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach ([
                    ['air', 'Tinggi air', 'WATER_LEVEL', 'sensor', 'Sensor ultrasonik mengukur jarak ke permukaan air tanpa menyentuh air, seperti penggaris digital.'],
                    ['arus', 'Kecepatan arus', 'FLOW', 'sensor', 'Sensor Hall-effect menghitung putaran rotor kecil: makin deras arus, makin cepat putarannya.'],
                    ['hujan', 'Curah hujan dan angin', 'RAINFALL', 'stasiun', 'Tipping bucket mengukur intensitas hujan sebagai tanda dini, anemometer mengukur kecepatan angin.'],
                    ['pesan', 'Peringatan', null, null, 'WhatsApp otomatis ke pengurus dan grup warga. Alarm suara dan lampu LED hanya menyala di level Kritis.'],
                ] as [$ico, $title, $type, $unit, $desc])
                    <article class="rounded-3xl bg-gradient-to-b from-mint to-white ring-1 ring-forest/10 p-6 flex flex-col">
                        <span class="w-14 h-14 rounded-2xl bg-white shadow-md ring-1 ring-forest/10 text-leaf flex items-center justify-center">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $icons[$ico] }}"/></svg>
                        </span>
                        <h3 class="mt-5 text-xl font-extrabold leading-tight">{{ $title }}</h3>
                        <p class="mt-2 text-slate-700 leading-relaxed flex-1">{{ $desc }}</p>
                        <p class="mt-5 pt-4 border-t border-forest/10 text-sm font-bold text-leaf">{{ $type ? ($sensorCounts[$type] ?? 0).' '.$unit.' terpasang' : 'Otomatis, tanpa menunggu operator' }}</p>
                    </article>
                @endforeach
            </div>
        </section>

        {{-- 5. Banner gelap: WhatsApp --}}
        <section id="whatsapp" class="max-w-6xl mx-auto px-4 sm:px-6 mt-20 sm:mt-28">
            <div class="relative overflow-hidden rounded-3xl bg-forest text-white p-7 sm:p-12 grid lg:grid-cols-2 gap-10 items-center">
                <div aria-hidden="true" class="absolute -right-24 -top-24 w-96 h-96 rounded-full bg-lime/20 blur-3xl"></div>
                <div class="relative">
                    <h2 class="text-3xl sm:text-5xl font-extrabold tracking-tight leading-tight">Peringatan masuk langsung ke WhatsApp</h2>
                    <p class="mt-4 text-lg text-slate-200 leading-relaxed">Sekitar {{ config('tce.whatsapp_active_pct') }}% warga sudah aktif di grup WhatsApp komunitas, jadi tidak perlu memasang aplikasi baru.</p>
                    <ul class="mt-5 space-y-2 text-slate-200">
                        <li><b class="text-white">Siaga:</b> pesan ke pengurus dan satgas untuk memantau keadaan.</li>
                        <li><b class="text-white">Kritis:</b> pesan ke semua grup warga berisi tinggi air dan arahan memindahkan kendaraan, disertai alarm suara untuk memanggil tim perahu karet.</li>
                        <li>Grup hanya untuk pengumuman otomatis. Berhenti kapan saja dengan keluar dari grup. Nomor anggota tidak ditampilkan di halaman ini.</li>
                    </ul>
                    @if ($waGroupUrl)
                        <a href="{{ $waGroupUrl }}" target="_blank" rel="noopener" class="mt-8 flex sm:inline-flex w-full sm:w-auto justify-between items-center gap-3 min-h-14 pl-7 pr-2 rounded-full bg-lime text-forest font-bold text-base sm:text-lg hover:bg-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">Gabung grup peringatan<span class="sr-only"> WhatsApp (tab baru)</span>
                            <span class="w-10 h-10 rounded-full bg-forest text-lime flex items-center justify-center" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17 17 7M8 7h9v9"/></svg></span></a>
                    @else
                        <p class="mt-8 inline-block border border-dashed border-lime/60 px-3 py-2 rounded-lg">Tautan grup WhatsApp: [ISI DATA]</p>
                    @endif
                </div>
                <figure class="relative mx-auto w-full max-w-md">
                    <figcaption class="mb-2 text-xs font-bold uppercase tracking-wider text-lime">Contoh pesan level Kritis (angka hanya contoh)</figcaption>
                    <div class="rounded-2xl rounded-tl-sm bg-[#d9fdd3] text-slate-900 p-4 shadow-2xl text-[15px] leading-relaxed">
                        <p class="font-bold">🚨 PERINGATAN BANJIR, LEVEL KRITIS</p>
                        <p class="mt-1">Taman Cipulir Estate | 27 Agu 2026, 14.03 WIB</p>
                        <p class="mt-2">Tinggi air 172 cm di Depan Masjid (RT 09)</p>
                        <p class="mt-2 font-semibold">TINDAKAN:</p>
                        <p>1. Pindahkan kendaraan ke tempat aman<br>2. Amankan barang berharga dan listrik rumah<br>3. Dahulukan lansia, anak, dan warga sakit<br>4. Ikuti arahan petugas</p>
                    </div>
                </figure>
            </div>
        </section>

        {{-- 6. Kawasan TCE --}}
        <section id="kawasan" class="max-w-6xl mx-auto px-4 sm:px-6 pt-20 sm:pt-28">
            <div class="grid lg:grid-cols-12 gap-10 lg:gap-14 items-start">
                <div class="lg:col-span-5">
                    <p class="text-xs font-bold uppercase tracking-widest text-leaf">Kawasan</p>
                    <h2 class="mt-2 text-3xl sm:text-5xl font-extrabold tracking-tight leading-tight">Mengenal Taman Cipulir Estate</h2>
                    <p class="mt-4 text-lg leading-relaxed text-slate-700">Kawasan permukiman seluas {{ config('tce.area_ha') }} hektare di RW 08, Kelurahan Cipadu Jaya, Kecamatan Larangan, Kota Tangerang. Banjir yang berulang melanda {{ config('tce.rt_affected') }} dari {{ config('tce.rt_total') }} RT, yaitu RT 06 sampai RT 11.</p>
                    @if ($site)
                        <p class="mt-5 text-sm text-slate-600"><span class="font-bold text-forest">Alamat</span><br>{{ $site->address }}</p>
                    @endif
                    <ul class="mt-6 flex flex-wrap gap-2 text-sm font-medium">
                        @foreach (['Gerbang Utama TCE', 'Masjid'] as $place)
                            <li class="px-3.5 py-1.5 rounded-full bg-mint text-forest">{{ $place }}</li>
                        @endforeach
                    </ul>
                </div>

                <div class="lg:col-span-7 min-w-0 rounded-3xl bg-forest text-white p-6 sm:p-9">
                    <dl class="grid grid-cols-2 sm:grid-cols-4 gap-y-7 gap-x-6">
                        @foreach ([[config('tce.area_ha'), 'hektare luas kawasan'], ['±'.number_format(config('tce.residents'), 0, ',', '.'), 'jiwa penghuni'], [config('tce.rt_affected').' / '.config('tce.rt_total'), 'RT terdampak (RT 06 sampai 11)'], [config('tce.pumps'), 'unit pompa air otomatis']] as [$num, $desc])
                            <div>
                                <dd class="text-4xl font-extrabold leading-none text-lime">{{ $num }}</dd>
                                <dt class="mt-2 text-sm text-slate-200">{{ $desc }}</dt>
                            </div>
                        @endforeach
                    </dl>
                    <hr class="my-7 border-white/15">
                    <h3 class="text-xl font-bold">Mengapa kawasan ini dipantau</h3>
                    <ul class="mt-3 space-y-3 text-slate-200 leading-relaxed">
                        <li>Kawasan menjadi area tangkapan air untuk Kelurahan Cipadu Jaya, terutama kiriman dari {{ implode(', ', array_slice(config('tce.catchment'), 0, -1)) }}, dan {{ last(config('tce.catchment')) }}.</li>
                        <li>Pompa air otomatis hanya mampu menyedot sekitar {{ config('tce.pump_share') }} dari debit air yang masuk.</li>
                        <li>Tinggi air dipantau manual lewat tiang berwarna sehingga kabar terlambat sampai ke warga, dan petugas tidak punya data kedalaman untuk menentukan kapan perahu karet aman diturunkan.</li>
                    </ul>
                    <p class="mt-5 text-xs text-slate-300">Sumber: proposal proyek EPICS in IEEE 2026 bersama Pengurus RW 08.</p>
                </div>
            </div>
        </section>

        {{-- 7. Wilayah: denah + batang per RT --}}
        <section id="peta" class="max-w-6xl mx-auto px-4 sm:px-6 pt-20 sm:pt-28">
            <div class="max-w-2xl">
                <p class="text-xs font-bold uppercase tracking-widest text-leaf">Wilayah</p>
                <h2 class="mt-2 text-3xl sm:text-5xl font-extrabold tracking-tight">Kondisi tiap wilayah</h2>
                <p class="mt-3 text-lg text-slate-700">Label di denah menunjukkan status tiap RT yang punya sensor tinggi air. Ketuk untuk melihat angkanya.</p>
            </div>

            <div class="mt-8 grid lg:grid-cols-12 gap-8">
                <div class="lg:col-span-7 min-w-0">
                    <div class="relative w-full rounded-3xl bg-white ring-1 ring-forest/10 shadow-lg shadow-forest/5 overflow-hidden">
                        <img src="{{ asset('images/tce_site_plan.png') }}" width="650" height="530" alt="Denah Taman Cipulir Estate dengan titik pemantauan air" class="w-full h-auto block" loading="lazy">
                        @foreach ($pins as $pin)
                            @php $pl = $known ? $levels[$pin['level']] : $noData; @endphp
                            <button type="button" data-caption="{{ $pin['zone'] }}: {{ $pl['name'] }}{{ $pin['tma'] !== null ? ', tinggi air '.$fmt($pin['tma']).' cm' : '' }}"
                                    aria-label="{{ $pin['zone'] }}, {{ $pl['name'] }}"
                                    style="left: {{ $pin['x'] }}%; top: {{ $pin['y'] }}%"
                                    class="pin absolute -translate-x-1/2 -translate-y-1/2 min-w-11 min-h-11 flex items-center justify-center rounded-full {{ $focus }}">
                                <span class="flex items-center gap-1.5 h-7 pl-1.5 pr-2.5 rounded-full bg-white shadow-md ring-1 ring-forest/30 text-[11px] font-bold"><span class="w-3.5 h-3.5 rounded-full {{ $pl['badge'] }}"></span>{{ $pin['zone'] }}</span>
                            </button>
                        @endforeach
                    </div>
                    <p id="pin-caption" class="mt-4 min-h-7 text-lg font-bold" aria-live="polite">Ketuk label RT di denah untuk melihat statusnya.</p>
                </div>

                <div class="lg:col-span-5 min-w-0">
                    <h3 class="text-xl font-extrabold">Per RT</h3>
                    <ul class="mt-3 space-y-3">
                        @foreach ($zones as $z)
                            @php $zl = $known ? $levels[$z['level']] : $noData; $pct = $z['tma'] === null ? 0 : min(100, $z['tma'] / $gaugeMax * 100); @endphp
                            <li class="rounded-2xl bg-white ring-1 ring-forest/10 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="text-lg font-extrabold leading-tight">{{ $z['code'] }}</div>
                                    </div>
                                    <span class="shrink-0 px-2.5 py-1 rounded-full text-xs font-bold {{ $zl['badge'] }}">{{ $zl['name'] }}</span>
                                </div>
                                <div class="mt-3 flex items-center gap-3">
                                    <div class="flex-1 h-2 rounded-full bg-mint overflow-hidden" aria-hidden="true"><div class="h-full rounded-full {{ $zl['bar'] }}" style="width: {{ $pct }}%"></div></div>
                                    <span class="font-mono text-sm font-bold text-right shrink-0 whitespace-nowrap {{ $z['tma'] === null ? 'text-slate-500 font-medium' : '' }}">{{ $z['tma'] !== null ? $fmt($z['tma']).' cm' : 'tanpa sensor' }}</span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </section>

        {{-- 8. Riwayat --}}
        <section id="riwayat" class="max-w-6xl mx-auto px-4 sm:px-6 pt-20 sm:pt-28">
            <div class="rounded-3xl bg-mint p-6 sm:p-10 grid lg:grid-cols-12 gap-8">
                <div class="lg:col-span-4">
                    <p class="text-xs font-bold uppercase tracking-widest text-leaf">Arsip</p>
                    <h2 class="mt-2 text-3xl sm:text-4xl font-extrabold tracking-tight">Riwayat kejadian</h2>
                    <p class="mt-3 text-slate-700">Peringatan yang sudah selesai. Alarm yang ternyata palsu tidak dicantumkan.</p>
                </div>
                <div class="lg:col-span-8 min-w-0">
                    @if ($history->isEmpty())
                        <p class="rounded-2xl bg-white p-6 text-lg text-slate-700">Belum ada kejadian tercatat sejak sistem dipasang.</p>
                    @else
                        <ul class="rounded-2xl bg-white divide-y divide-slate-200 px-5">
                            @foreach ($history as $h)
                                <li class="py-4 flex items-center justify-between gap-4">
                                    <div class="min-w-0">
                                        <div class="text-lg font-extrabold">{{ $h->started_at->timezone('Asia/Jakarta')->locale('id')->isoFormat('D MMM Y, HH.mm') }} WIB</div>
                                        <div class="text-sm text-slate-600">{{ $h->zone ? str_replace('-', ' ', $h->zone->code) : 'Seluruh kompleks' }}, berlangsung {{ $h->started_at->locale('id')->diffForHumans($h->resolved_at, true) }}</div>
                                    </div>
                                    <span class="shrink-0 px-3 py-1 rounded-full text-sm font-bold {{ $levels[$h->level]['badge'] ?? $noData['badge'] }}">{{ $levels[$h->level]['name'] ?? '-' }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </section>

        {{-- 9. Cara kerja singkat --}}
        <section class="max-w-6xl mx-auto px-4 sm:px-6 pt-20 sm:pt-28">
            <div class="grid md:grid-cols-3 gap-8 text-lg leading-relaxed text-slate-800">
                <p><b class="block text-forest text-xl mb-1">Keputusan di server lokal</b>Server lokal di kawasan memproses data sensor dan menentukan level peringatan, dengan target keterlambatan pesan rata-rata di bawah 1 detik.</p>
                <p><b class="block text-forest text-xl mb-1">Anti alarm palsu</b>Bacaan sensor dihaluskan dengan filter EWMA, dan alarm suara dibatasi hanya untuk level Kritis agar tidak memicu kepanikan.</p>
                <p><b class="block text-forest text-xl mb-1">Arsip untuk perencanaan</b>Data tersimpan sehingga pola banjir bisa ditinjau warga dan pemerintah setempat, misalnya BPBD dan kelurahan.</p>
            </div>
        </section>

        {{-- 10. Tentang proyek --}}
        <section id="proyek" class="max-w-6xl mx-auto px-4 sm:px-6 pt-20 sm:pt-28 pb-16 sm:pb-24">
            <div class="rounded-3xl bg-mint p-6 sm:p-10 grid lg:grid-cols-12 gap-8">
                <div class="lg:col-span-5">
                    <p class="text-xs font-bold uppercase tracking-widest text-leaf">Tentang proyek</p>
                    <h2 class="mt-2 text-2xl sm:text-3xl font-extrabold tracking-tight">Sistem peringatan dini banjir berbasis IoT dan AI</h2>
                    <p class="mt-3 text-slate-700 leading-relaxed">Proyek pengabdian masyarakat {{ config('tce.grant') }}. Dikerjakan tim mahasiswa {{ config('tce.university') }} bersama {{ config('tce.org_name') }}.</p>
                    
                </div>
                <div class="lg:col-span-7">
                    <h3 class="text-lg font-extrabold">Tujuan</h3>
                    <ol class="mt-3 space-y-3 text-slate-800 leading-relaxed">
                        <li class="flex gap-3"><span class="shrink-0 w-7 h-7 rounded-full bg-lime text-forest font-extrabold text-sm flex items-center justify-center" aria-hidden="true">1</span>Meningkatkan keselamatan dan kesiapsiagaan {{ number_format(config('tce.residents'), 0, ',', '.') }} warga, terutama anak-anak dan lansia.</li>
                        <li class="flex gap-3"><span class="shrink-0 w-7 h-7 rounded-full bg-lime text-forest font-extrabold text-sm flex items-center justify-center" aria-hidden="true">2</span>Melindungi rumah dan kendaraan, dan memudahkan petugas mengerahkan perahu karet dengan aman.</li>
                        <li class="flex gap-3"><span class="shrink-0 w-7 h-7 rounded-full bg-lime text-forest font-extrabold text-sm flex items-center justify-center" aria-hidden="true">3</span>Memberi informasi yang akurat, terbuka, dan real-time agar warga bertindak terkoordinasi tanpa panik.</li>
                    </ol>
                    <p class="mt-6 pt-4 border-t border-forest/15 text-sm text-slate-700">Sistem ini alat bantu kesiapsiagaan dini, bukan jaminan mutlak. Tetap ikuti arahan petugas lapangan.</p>
                </div>
            </div>
        </section>
    </main>

    <footer class="bg-forest text-slate-300">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 py-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div>
                <div class="text-lg font-extrabold text-white">TCE <span class="text-lime">FloodGuard</span></div>
                <p class="mt-1 text-sm">&copy; 2026. Proyek pengabdian EPICS in IEEE, Universitas Budi Luhur, bersama Pengurus RW 08 Taman Cipulir Estate.</p>
            </div>
            <nav class="flex flex-wrap gap-x-5 gap-y-2 text-sm font-medium" aria-label="Tautan kaki halaman">
                @foreach ($navItems as [$href, $label])
                    <a href="{{ $href }}" class="py-2 hover:text-white {{ $focus }}">{{ $label }}</a>
                @endforeach
            </nav>
        </div>
    </footer>

    <script>
        const caption = document.getElementById('pin-caption');
        document.querySelectorAll('.pin').forEach(b => b.addEventListener('click', () => { caption.textContent = b.dataset.caption; }));

        // Muat ulang tiap menit, hanya bila pengunjung sedang di atas halaman agar bacaan tidak terputus
        setTimeout(function reload() {
            if (window.scrollY < 120 && !document.querySelector('details[open]')) location.reload();
            else setTimeout(reload, 60000);
        }, 60000);
    </script>
</body>
</html>
