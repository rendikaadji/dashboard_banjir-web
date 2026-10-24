# Panduan Tim TCE FloodGuard

Untuk anggota tim yang baru bergabung atau butuh mengingat alurnya. Baca sampai habis sebelum commit pertama.

## Daftar isi

1. [Gambaran sistem](#1-gambaran-sistem)
2. [Install dan menjalankan](#2-install-dan-menjalankan)
3. [Peta halaman](#3-peta-halaman)
4. [Konfigurasi `.env`](#4-konfigurasi-env)
5. [WhatsApp: cara kerja dan cara uji](#5-whatsapp-cara-kerja-dan-cara-uji)
6. [Alur kerja Git](#6-alur-kerja-git)
7. [Di mana kode berada](#7-di-mana-kode-berada)
8. [Sumber data dan fakta](#8-sumber-data-dan-fakta)
9. [Aturan keras](#9-aturan-keras)
10. [Pemecahan masalah](#10-pemecahan-masalah)

---

## 1. Gambaran sistem

```text
Sensor (air, arus, hujan)
   |  kirim data  POST /api/v1/telemetry
   v
Server (Laravel)
   |-- simpan pembacaan, haluskan dengan filter EWMA
   |-- Alert engine: tentukan level 0 sampai 3 (anti alarm palsu)
   |-- Alert baru? --> antrean WhatsApp --> Fonnte --> pengurus dan grup warga
   v
Tampilan
   |-- /        Landing publik (warga)
   |-- /admin   Dashboard (pengurus, satgas, petugas keamanan)
```

**Level peringatan** (sesuai proposal EPICS):

| Level | Nama | Tinggi air | Yang dikabari |
| :---: | ---- | ---------- | ------------- |
| 1 | Waspada | di bawah 100 cm (sistem mulai di 70 cm) | tidak ada, dicatat saja |
| 2 | Siaga | 100 sampai 150 cm | pengurus RT dan satgas |
| 3 | Kritis | di atas 150 cm | semua grup warga, plus alarm suara |

Aturan penting: **data sensor lebih dari 15 menit dianggap tidak tersedia**, dan landing tidak boleh menampilkan "Aman" tanpa data.

---

## 2. Install dan menjalankan

### Prasyarat

- PHP 8.3 atau lebih baru dan Composer (`php -v`, `composer -V`)
- Node.js 20 atau lebih baru (`node -v`)
- Git
- Disarankan: [Laravel Herd](https://herd.laravel.com) (sudah termasuk PHP dan Composer). Belum punya PHP? Lihat <https://php.new>.

### Langkah

```bash
git clone https://github.com/rendikaadji/dashboard_banjir-web.git
cd dashboard_banjir-web
composer run setup      # composer install, salin .env, key, migrasi, npm install, build
php artisan db:seed     # isi data awal (zona, sensor, ambang)
composer run dev        # server + queue + vite sekaligus
```

Buka `http://localhost:8000` (landing) dan `http://localhost:8000/admin` (admin).

> **Penting untuk data seeder:** seeder membuat beberapa penerima dummy dengan nomor palsu. Jangan jalankan seeder di komputer yang `FONNTE_TOKEN`-nya sudah terisi, atau hapus dulu penerima dummy di `/admin/recipients`.

### Data sensor palsu untuk uji

```bash
php artisan fews:simulate
```

Tanpa data baru, status di landing berubah jadi "Data belum tersedia" setelah 15 menit. Itu perilaku yang benar, bukan galat.

### Aset tidak berubah?

```bash
npm run dev       # mode watch
npm run build     # build produksi
```

---

## 3. Peta halaman

| Alamat | Untuk siapa | Isi |
| ------ | ----------- | --- |
| `/` | warga | Landing publik |
| `/admin/dashboard` | pengurus, satgas | Status, peta sensor, grafik, tombol peringatan manual dan uji sirine |
| `/admin/kiosk` | layar pos satpam | Tampilan gelap, besar, dibaca dari jauh |
| `/admin/nodes` | teknisi | Daftar sensor, kalibrasi |
| `/admin/alerts` | pengurus | Riwayat dan tindakan operator |
| `/admin/thresholds` | Technical Lead dan RW | Ambang level (jangan ubah sendiri, lihat bagian 9) |
| `/admin/recipients` | pengurus | Penerima WhatsApp |
| `/admin/analytics` | tim | KPI proyek dan ekspor CSV |
| `/admin/system` | teknisi | Kesehatan sistem dan jejak audit |

---

## 4. Konfigurasi `.env`

Salin dari `.env.example`. **File `.env` tidak boleh di-commit.**

| Variabel | Fungsi |
| -------- | ------ |
| `FONNTE_TOKEN` | Token perangkat Fonnte. **Kosong = pengiriman WhatsApp hanya simulasi** |
| `FONNTE_URL` | Alamat API Fonnte (bawaan sudah benar) |
| `PUBLIC_WA_GROUP_URL` | Tautan undangan grup WhatsApp yang tampil di landing |
| `PUBLIC_CONTACT_PHONE`, `PUBLIC_CONTACT_EMAIL` | Kontak pengurus RW 08 di landing. Kosong tampil `[ISI DATA]` |
| `PUBLIC_EMERGENCY_PHONE`, `PUBLIC_SATGAS_PHONE` | Dipakai API publik `/api/v1/public/status` |
| `PUBLIC_EVACUATION_POINT` | Titik evakuasi. Belum ada di proposal; kosong berarti pesan menulis "titik aman yang ditentukan pengurus RW" |

---

## 5. WhatsApp: cara kerja dan cara uji

Pengiriman memakai **antrean** supaya request sensor tidak menunggu. Artinya **worker harus berjalan**. `composer run dev` sudah menyalakannya; kalau menjalankan manual:

```bash
php artisan queue:work
```

Alur satu pesan: alert baru dibuat, `WhatsAppDispatcher` membuat baris `QUEUED` di tabel `alert_deliveries`, lalu `SendWhatsAppMessage` mengirim ke Fonnte. Berhasil menjadi `SENT` (dengan `latency_ms`), gagal dicoba ulang 3 kali (jeda 2, 10, 60 detik) lalu `FAILED`.

**Uji kirim ke satu nomor sendiri dulu**, jangan langsung ke semua penerima:

1. Isi `FONNTE_TOKEN`. Pastikan perangkat di dashboard Fonnte berstatus **Connected**.
2. Nonaktifkan penerima lain di `/admin/recipients`, sisakan nomor uji.
3. Picu alert uji dari dashboard admin (tombol Peringatan Manual).
4. Cek status di tabel `alert_deliveries`.

Catatan Fonnte:

- Fonnte membalas HTTP 200 walau gagal; status sebenarnya ada di isi respons. Pesan "device disconnected" berarti perangkat belum tersambung.
- Paket gratis menambahkan tulisan "sent via fonnte.com" pada pesan.
- **Isi pesan ke warga wajib disetujui tertulis Pengurus RW 08** sebelum dipakai sungguhan.

---

## 6. Alur kerja Git

**Jangan commit langsung ke `main`.** Satu branch untuk satu tugas.

### 6.1 Mulai tugas

```bash
git checkout main
git pull origin main
git checkout -b <tipe>/<deskripsi-singkat>
```

| Tipe | Contoh |
| ---- | ------ |
| `feat/` | `feat/halaman-monitoring-node` |
| `fix/` | `fix/timezone-tabel-readings` |
| `docs/` | `docs/update-panduan` |
| `refactor/` | `refactor/service-ingest` |
| `test/` | `test/skenario-alert-sustain` |
| `chore/` | `chore/update-dependency` |

Huruf kecil, pakai tanda hubung, singkat.

### 6.2 Sebelum commit

```bash
vendor/bin/pint        # rapikan gaya kode
composer test          # semua tes harus hijau
```

Checklist:

- [ ] Tes hijau dan `pint` tidak menyisakan perubahan
- [ ] Tidak ada token, nomor warga, atau isi `.env` di dalam perubahan
- [ ] Input dari luar divalidasi
- [ ] Tampilan baru dicek di HP dan desktop

### 6.3 Commit

```bash
git status                  # lihat apa yang berubah
git add <file-spesifik>     # hindari "git add ." supaya .env dan file sampah tidak ikut
git commit
```

Format pesan commit: [Conventional Commits](https://www.conventionalcommits.org), contoh `feat(landing): tambah bagian riwayat kejadian`. Pakai kata kerja, singkat, jelaskan *apa dan kenapa*.

Kebijakan tim tentang bantuan AI (tetap berlaku, lihat [docs/AI-USAGE-POLICY.md](docs/AI-USAGE-POLICY.md)): bila ada bagian yang dibantu AI, tambahkan trailer di akhir pesan commit dan isi satu baris di [docs/AI-USAGE-LOG.md](docs/AI-USAGE-LOG.md).

```text
AI-Assisted: yes
AI-Tool: <nama alat>
AI-Scope: <bagian yang dibantu>
Human-Owner: <nama Anda>
Reviewed-by: <nama reviewer>
```

Tanpa bantuan AI cukup `AI-Assisted: no`.

### 6.4 Push dan Pull Request

```bash
git push -u origin <tipe>/<deskripsi-singkat>     # pertama kali
git push                                           # berikutnya
```

Buka Pull Request ke `main` di GitHub. Aturan review:

- Pembuat PR tidak me-review PR sendiri.
- Minimal 1 reviewer. Perubahan alert engine, ambang, dispatcher, atau kalibrasi **wajib** ditinjau Technical Lead.
- Merge setelah disetujui dan tes hijau.

### 6.5 Setelah merge

```bash
git checkout main
git pull origin main
git branch -d <nama-branch>
```

### 6.6 File yang tidak boleh masuk Git

Sudah diatur di `.gitignore`, tapi periksa dengan `git status` sebelum `git add`:

- `.env` dan file berisi token atau kata sandi
- `vendor/`, `node_modules/`, `public/build`
- Konfigurasi alat AI di komputer masing-masing: `.agents/`, `CLAUDE.md`, `AGENTS.md`, `boost.json`, `graphify-out/`, `.claude/`
- Hasil tangkapan layar atau data warga di luar `docs/screenshots/`

Bila terlanjur `git add` file yang salah: `git restore --staged <file>`.

---

## 7. Di mana kode berada

```text
app/Http/Controllers/        Admin (Dashboard, Node, Alert, ...) dan PublicController (landing)
app/Http/Controllers/Api/    Endpoint sensor dan status (telemetri, SSE)
app/Services/Alert/          AlertEngineService  (kritis, lihat bagian 9)
app/Services/Notification/   WhatsAppDispatcher  (pilih penerima, susun pesan)
app/Services/Telemetry/      Penerimaan data, filter EWMA, konversi unit
app/Jobs/                    SendWhatsAppMessage (kirim ke Fonnte, coba ulang)
config/tce.php               Fakta kawasan dan proyek (sumber: proposal EPICS)
config/services.php          Fonnte dan kontak publik
database/seeders/            Data awal
resources/views/landing.blade.php          Landing publik
resources/views/layouts/app.blade.php      Kerangka admin (sidebar)
resources/css/app.css                      Warna merek dan tema admin
routes/web.php               Rute web ( / dan /admin )
docs/                        PRD, DESIGN, kebijakan AI, tangkapan layar
tests/                       Tes fitur dan unit
```

Tema admin: blok `.admin-theme` di `resources/css/app.css` memetakan warna gelap lama ke palet baru. Ubah palet di sana, bukan di tiap halaman.

---

## 8. Sumber data dan fakta

**Proposal EPICS in IEEE 2026 adalah patokan.** Angka dan nama kawasan hanya boleh diubah di `config/tce.php`, bukan ditulis ulang di tampilan.

- Jangan menambah angka, nama tempat, testimoni, atau klaim yang tidak ada di proposal. Data kosong ditulis `[ISI DATA]`.
- Contoh yang pernah salah dan sudah dibuang: nama masjid karangan, jumlah KK per RT, deskripsi sektor tiap RT.
- Nama titik sensor di database berasal dari seeder, belum dari dokumen resmi.

Arah desain ada di [docs/DESIGN.md](docs/DESIGN.md).

---

## 9. Aturan keras

1. **Jangan commit `.env`, token, atau data warga** (nomor, nama, alamat). Bila terlanjur, ganti token dalam 24 jam dan lapor Project Leader.
2. **Alert engine dan ambang adalah komponen keselamatan.** Jangan menyederhanakannya (menghapus konfirmasi durasi, hysteresis, atau konfirmasi banyak sensor). Perubahan wajib ditinjau Technical Lead dan lulus seluruh `AlertEngineSafetyTest`.
3. **Ambang batas ditetapkan manusia**, dari survei lapangan dan kesepakatan RW. Saat ini sistem memakai Waspada 70 cm, sedangkan proposal menyebut Level 1 di bawah 100 cm; keputusan belum diambil.
4. **Isi pesan WhatsApp ke warga** wajib disetujui tertulis Pengurus RW 08.
5. **`/admin` belum punya login.** Jangan dibuka ke internet.
6. **Tidak paham, tidak masuk repo.** Bila tidak bisa menjelaskan sebuah fungsi, jangan di-commit.

---

## 10. Pemecahan masalah

| Gejala | Penyebab dan solusi |
| ------ | ------------------- |
| Landing menulis "Data belum tersedia" | Data sensor lebih dari 15 menit. Jalankan `php artisan fews:simulate` |
| Pesan WhatsApp tetap `QUEUED` | Worker mati. Jalankan `php artisan queue:work` |
| Pesan `FAILED`, "request invalid on disconnected device" | Perangkat Fonnte belum tersambung. Scan ulang QR di dashboard Fonnte |
| Tampilan tidak berubah setelah edit | `npm run build` (atau `npm run dev`) lalu muat ulang paksa |
| Ikon tab salah | Cache browser. Muat ulang paksa (Cmd+Shift+R) |
| `composer test` gagal setelah `git pull` | `composer install` lalu `php artisan migrate` |
| Halaman `/dashboard` 404 | Alamat lama. Sekarang `/admin/dashboard` |
| Salah `git add` | `git restore --staged <file>` |
