# TCE FloodGuard — Dashboard Banjir (Web)

Platform web **Flood Early Warning System (FEWS)** untuk Taman Cipulir Estate, RW 08, Kel. Cipadu Jaya, Kec. Larangan, Kota Tangerang (EPICS in IEEE 2026).

Dibangun dengan **Laravel 13**, **PHP 8.3+**, **Vite**, dan **Tailwind CSS v4**.

> Dokumen ini menjelaskan **cara kerja tim**: dari install, belajar dari PRD, minta bantuan AI, sampai push ke branch baru. Baca sampai habis sebelum commit pertama.

---

## Daftar Isi

1. [Dokumen Wajib Baca](#1-dokumen-wajib-baca)
2. [Install](#2-install)
3. [Menjalankan Aplikasi](#3-menjalankan-aplikasi)
4. [Alur Kerja Tim (Ringkasan)](#4-alur-kerja-tim-ringkasan)
5. [Langkah 1 — AI Membaca PRD Dulu](#5-langkah-1--ai-membaca-prd-dulu)
6. [Langkah 2 — Tentukan Tier Risiko](#6-langkah-2--tentukan-tier-risiko)
7. [Langkah 3 — Buat Branch Baru](#7-langkah-3--buat-branch-baru)
8. [Langkah 4 — Kerjakan dengan Bantuan AI](#8-langkah-4--kerjakan-dengan-bantuan-ai)
9. [Langkah 5 — Test & Lint](#9-langkah-5--test--lint)
10. [Langkah 6 — Commit](#10-langkah-6--commit)
11. [Langkah 7 — Push & Pull Request](#11-langkah-7--push--pull-request)
12. [Aturan Keras (Jangan Dilanggar)](#12-aturan-keras-jangan-dilanggar)
13. [Struktur Folder](#13-struktur-folder)
14. [Troubleshooting](#14-troubleshooting)

---

## 1. Dokumen Wajib Baca

| Dokumen | Isi | Kapan dibaca |
| --- | --- | --- |
| [docs/PRD.md](docs/PRD.md) | Product Requirements: fitur, alert engine, skema DB, UI, API | **Sebelum mengerjakan tugas apa pun** |
| [docs/AI-USAGE-POLICY.md](docs/AI-USAGE-POLICY.md) | Aturan pakai AI, tier risiko, format commit, checklist | **Sebelum memakai AI pertama kali** |
| [CLAUDE.md](CLAUDE.md) / [AGENTS.md](AGENTS.md) | Instruksi untuk AI agent (Laravel Boost) | AI membacanya otomatis |

> Anggota baru wajib membaca dan menandatangani AI Usage Policy (§19.2) sebelum mendapat akses repo.

---

## 2. Install

### 2.1 Prasyarat

- **PHP 8.3+** dan **Composer** (cek: `php -v`, `composer -V`)
- **Node.js 20+** dan npm (cek: `node -v`, `npm -v`)
- **Git**
- (Disarankan) [Laravel Herd](https://herd.laravel.com) di Windows/macOS — sudah termasuk PHP, Composer, dan web server lokal
- (Opsional) [Claude Code](https://claude.com/claude-code) atau AI agent lain untuk membantu coding

Belum punya PHP/Composer? Lihat <https://php.new>.

### 2.2 Clone & Setup

```bash
# 1. Clone repo (ganti <URL-REPO> dengan URL repo tim)
git clone <URL-REPO> dashboard_banjir-web
cd dashboard_banjir-web

# 2. Setup otomatis: composer install, salin .env, generate key, migrate, npm install, build
composer run setup
```

`composer run setup` menjalankan:

1. `composer install`
2. Salin `.env.example` → `.env` (jika belum ada)
3. `php artisan key:generate`
4. `php artisan migrate --force`
5. `npm install --ignore-scripts`
6. `npm run build`

### 2.3 Konfigurasi `.env`

- Cek nilai `DB_*` di `.env` sesuai database lokal Anda.
- **JANGAN** commit file `.env`.
- **JANGAN** tempel isi `.env` ke chat AI (token, password, kredensial = terlarang mutlak).

### 2.4 Install Laravel Boost (untuk AI agent)

Jika `AGENTS.md` / `CLAUDE.md` belum ter-generate di mesin Anda:

```bash
composer require laravel/boost --dev
php artisan boost:install
```

Boost memberi AI akses ke dokumentasi Laravel, skema database, dan log browser — sehingga saran AI sesuai versi yang terpasang.

---

## 3. Menjalankan Aplikasi

```bash
composer run dev     # server + queue + vite sekaligus
```

Atau, jika memakai Herd, buka `http://dashboard_banjir-web.test`.

### Alamat halaman

| Alamat | Isi |
| ------ | --- |
| `/` | Landing page **publik** (tanpa login): status banjir, kondisi per RT, riwayat, cara gabung grup WhatsApp, info kawasan TCE |
| `/admin` | Dashboard dan semua halaman pengelolaan (`/admin/dashboard`, `/admin/kiosk`, `/admin/nodes`, `/admin/alerts`, `/admin/thresholds`, `/admin/recipients`, `/admin/analytics`, `/admin/system`) |
| `/public` | Dialihkan ke `/` |

> **Belum ada login untuk `/admin`.** Jangan buka ke internet sebelum autentikasi dipasang.

### Variabel `.env` tambahan

| Variabel | Fungsi |
| -------- | ------ |
| `FONNTE_TOKEN`, `FONNTE_URL` | Token gateway WhatsApp Fonnte. Kosong = pengiriman disimulasikan (tidak ada pesan keluar) |
| `PUBLIC_WA_GROUP_URL` | Tautan undangan grup WhatsApp di landing |
| `PUBLIC_CONTACT_PHONE`, `PUBLIC_CONTACT_EMAIL` | Kontak pengurus RW 08 di landing. Kosong = tampil `[ISI DATA]` |
| `PUBLIC_EMERGENCY_PHONE`, `PUBLIC_SATGAS_PHONE` | Dipakai API `/api/v1/public/status` |
| `PUBLIC_EVACUATION_POINT` | Titik evakuasi (belum ada di proposal). Kosong = pesan memakai "titik aman yang ditentukan pengurus RW" |

### Pengiriman WhatsApp

Pengiriman lewat **queue** (`QUEUE_CONNECTION=database`), jadi worker harus jalan:

```bash
php artisan queue:work
```

Status tiap pesan (`QUEUED`, `SENT`, `FAILED`) dan `latency_ms` tercatat di tabel `alert_deliveries`. Gagal dicoba ulang 3 kali (jeda 2, 10, 60 detik). Fonnte membalas HTTP 200 meski gagal, jadi status dibaca dari isi respons; "device disconnected" berarti perangkat belum tersambung di dashboard Fonnte. Paket gratis menambahkan tulisan "sent via fonnte.com" pada pesan.

Simulasi data sensor: `php artisan fews:simulate`. Data sensor lebih dari 15 menit dianggap tidak tersedia dan landing tidak menampilkan status Aman.

Jika perubahan frontend tidak muncul:

```bash
npm run dev          # mode watch
npm run build        # build produksi
```

---

## 4. Alur Kerja Tim (Ringkasan)

```
 ┌────────────────────────────────────────────────────────────────────┐
 │ 1. AI baca PRD       →  paham konteks & aturan sebelum menulis kode │
 │ 2. Tentukan Tier     →  T0–T4, menentukan seberapa ketat review     │
 │ 3. Buat branch baru  →  jangan pernah kerja langsung di main/master │
 │ 4. Kerjakan + AI     →  rumuskan → minta → PAHAMI → verifikasi      │
 │ 5. Test & lint       →  composer test + pint                        │
 │ 6. Commit            →  dengan trailer AI-Assisted                  │
 │ 7. Push + PR         →  isi template PR, minta reviewer             │
 └────────────────────────────────────────────────────────────────────┘
```

---

## 5. Langkah 1 — AI Membaca PRD Dulu

**Aturan:** setiap sesi AI baru harus memulai dengan membaca PRD. AI yang tidak tahu konteks akan menghasilkan kode generik yang salah untuk sistem keselamatan jiwa ini.

### 5.1 Prompt pembuka sesi (salin-tempel)

```text
Sebelum mengerjakan apa pun, baca dulu:
1. docs/PRD.md  — fokus ke bagian yang relevan dengan tugas saya
2. docs/AI-USAGE-POLICY.md — terutama §4 (tier), §5 (matriks artefak), §7 (zona terlarang)
3. AGENTS.md — konvensi Laravel proyek ini

Setelah membaca, jawab singkat:
- Bagian PRD mana yang relevan dengan tugas saya? (sebut nomor §)
- Artefak ini masuk Tier berapa menurut AI Usage Policy?
- Apakah ada hal yang TIDAK boleh dikerjakan AI untuk tugas ini?

Tugas saya: <tulis satu tugas spesifik>
```

### 5.2 Kenapa ini penting

- PRD berisi keputusan yang **tidak boleh diubah AI** (mis. aturan anti false-alarm di PRD §9, warna level di §13.2, ambang batas banjir).
- AI sering "menyempurnakan" desain dengan komponen yang tidak ada di anggaran atau melanggar prinsip *offline-capable*.
- Dengan memaksa AI merangkum dulu, Anda bisa **mengecek apakah AI paham** sebelum satu baris kode ditulis.

### 5.3 Peta PRD (bagian yang paling sering dipakai)

| Mengerjakan… | Baca PRD |
| --- | --- |
| Tabel / migrasi / model | §7 Model Data |
| Pipeline sensor, EWMA, fusion | §8 |
| Alert engine | §9 (**safety-critical**) |
| WhatsApp / sirine / Telegram | §10 |
| Fitur & kebutuhan | §12 Functional Requirements |
| Halaman & warna UI | §13 |
| Endpoint REST / WebSocket | §14 API |
| Keamanan & privasi | §16 |
| Skenario uji | §18 |
| Keputusan belum final | §22 Open Questions (**jangan dijawab AI**) |

---

## 6. Langkah 2 — Tentukan Tier Risiko

Sebelum menulis kode, tentukan tier (detail lengkap di [AI Usage Policy §4–5](docs/AI-USAGE-POLICY.md)).

| Tier | Contoh | Aturan AI | Review |
| :-: | --- | --- | --- |
| **T0** | Data dummy, debugging error | Bebas | — |
| **T1** | Komponen UI, dokumentasi, test | AI boleh draft, manusia verifikasi | 1 reviewer |
| **T2** | Migrasi, controller/service, query SQL | AI boleh bantu, **wajib test + review baris demi baris** | 1 reviewer + test hijau |
| **T3** | Alert engine, pesan WA ke warga, kalibrasi | AI hanya **eksplorasi & review**; implementasi final **ditulis manusia** | 2 reviewer (termasuk Technical Lead) |
| **T4** | Ambang batas banjir, angka KPI, klaim keselamatan, anggaran | **AI dilarang total** | — |

Tidak yakin tier-nya? Tanya Technical Lead **sebelum** mulai.

---

## 7. Langkah 3 — Buat Branch Baru

**Jangan pernah commit langsung ke `main` / `master`.**

```bash
# Pastikan branch utama up-to-date
git checkout main
git pull origin main

# Buat branch baru dari main
git checkout -b <tipe>/<deskripsi-singkat>
```

### Konvensi nama branch

| Tipe | Contoh |
| --- | --- |
| `feat/` | `feat/halaman-monitoring-node` |
| `fix/` | `fix/timezone-tabel-readings` |
| `docs/` | `docs/update-readme` |
| `refactor/` | `refactor/service-ingest` |
| `test/` | `test/skenario-alert-sustain` |
| `chore/` | `chore/update-dependency` |

Aturan: huruf kecil, pakai tanda hubung, singkat dan jelas. Satu branch = satu tugas.

---

## 8. Langkah 4 — Kerjakan dengan Bantuan AI

Ikuti **6 langkah wajib** dari AI Usage Policy §9:

```
1. RUMUSKAN    Tentukan sendiri dulu apa yang dibutuhkan & batasannya.
2. MINTA       Beri konteks lengkap + 1 tugas spesifik per permintaan.
3. PAHAMI      Baca hasilnya. Ada yang gelap? Tanya balik / tulis ulang sendiri.
4. VERIFIKASI  Cek dokumentasi resmi, jalankan checklist (§10 policy).
5. UJI         Jalankan test lokal.
6. CATAT       Commit dengan trailer AI + isi docs/AI-USAGE-LOG.md.
```

### Tips prompt yang baik

- Beri konteks: Laravel 13, PHP 8.3, sistem harus tetap jalan tanpa internet.
- **Satu fungsi per permintaan** — lebih mudah diverifikasi daripada satu modul utuh.
- Minta **alternatif + trade-off**, bukan satu jawaban.
- Minta AI **mengkritik** kerja Anda: *"Cari celah di logika ini."*
- Selalu minta penjelasan setiap bagian.

### Aturan emas

> **Tidak paham = tidak masuk repo.**
> Jika Anda tidak bisa menjelaskan sebuah fungsi tanpa membuka AI lagi, kode itu **tidak boleh di-commit**.

### Yang TIDAK boleh masuk prompt AI

- Nama, nomor WhatsApp, alamat warga
- Isi `.env`, token, password, kredensial MQTT/DB
- Isi tabel `recipients`, `users`, `audit_logs` yang belum dianonimkan

Kalau tidak sengaja terjadi: **rotasi kredensial dalam 24 jam** dan laporkan ke Project Leader.

---

## 9. Langkah 5 — Test & Lint

Jalankan **sebelum** commit:

```bash
# Format kode sesuai standar Laravel
vendor/bin/pint

# Jalankan seluruh test
composer test

# Atau test tertentu saja
php artisan test --filter=NamaTest
```

Checklist cepat:

- [ ] `composer test` hijau
- [ ] `vendor/bin/pint` tidak menyisakan perubahan
- [ ] Tidak ada kredensial / URL internal ter-hardcode
- [ ] Input eksternal divalidasi eksplisit
- [ ] Kolom waktu memakai timezone-aware (`timestampTz`), simpan UTC, tampil WIB
- [ ] Migrasi punya `down()` yang **sudah diuji** (`php artisan migrate:rollback`)
- [ ] Dependency baru sudah dicek lisensi & pemeliharaannya

Untuk komponen **T3** (alert engine, dll.): seluruh 12 skenario uji PRD §18.2 harus lulus.

---

## 10. Langkah 6 — Commit

```bash
git status                  # cek file apa saja yang berubah
git add <file-spesifik>     # hindari "git add ." agar .env / file sampah tidak ikut
git commit
```

### Format commit message

Gunakan [Conventional Commits](https://www.conventionalcommits.org) + **trailer AI** (wajib, dari AI Usage Policy §12):

```text
feat(ingest): tambah validasi rentang pembacaan sensor ultrasonik

Menolak nilai di luar rentang mount_height, menandai OUT_OF_RANGE
agar tidak masuk perhitungan fusion.

AI-Assisted: yes
AI-Tool: Claude
AI-Scope: draft fungsi validasi + unit test
Human-Owner: <nama Anda>
Reviewed-by: <nama reviewer>
Tier: T2
```

Tanpa bantuan AI? Cukup tulis `AI-Assisted: no`.

Isi juga satu baris di **[docs/AI-USAGE-LOG.md](docs/AI-USAGE-LOG.md)** (tanggal, anggota, artefak, tier, alat, cakupan bantuan, yang ditulis manusia, reviewer, hash commit).

---

## 11. Langkah 7 — Push & Pull Request

```bash
# Push branch baru pertama kali
git push -u origin <tipe>/<deskripsi-singkat>

# Push berikutnya cukup
git push
```

Lalu buka Pull Request ke `main` di GitHub. Isi template berikut (wajib):

```markdown
## Ringkasan
<apa yang berubah dan kenapa>

## Tier risiko
- [ ] T0  - [ ] T1  - [ ] T2  - [ ] T3

## Penggunaan AI
- Dibantu AI? Ya / Tidak
- Alat:
- Bagian mana yang dibantu:
- Bagian mana yang ditulis manusia:

## Verifikasi
- [ ] Saya memahami setiap baris perubahan ini
- [ ] Checklist §10 yang relevan sudah dijalankan
- [ ] Test lulus
- [ ] Tidak ada kredensial/data warga di dalam perubahan ini
- [ ] (T3) Skenario safety §18.2 lulus & 2 reviewer disetujui
```

### Aturan review

- **Human Owner tidak boleh me-review PR-nya sendiri.**
- T1–T2: minimal 1 reviewer. T3: 2 reviewer, salah satunya Technical Lead.
- Reviewer berhak menolak PR yang tidak lolos checklist.
- Setelah disetujui dan test hijau, merge ke `main`.

### Komponen safety-critical

Perubahan pada alert engine, threshold service, notification dispatcher, dan kalkulasi kalibrasi **wajib** review Technical Lead dan **tidak boleh di-deploy** saat prakiraan hujan lebat 24 jam ke depan (kecuali bug kritis).

---

## 12. Aturan Keras (Jangan Dilanggar)

| ⛔ | Larangan |
| :-: | --- |
| 1 | AI menentukan / mengubah **nilai ambang batas banjir** |
| 2 | AI mengarang / memperkirakan **angka hasil pengujian** |
| 3 | AI menulis **klaim keselamatan** |
| 4 | AI menulis **naskah inti proposal** EPICS |
| 5 | **Data pribadi warga** atau **kredensial** masuk prompt AI |
| 6 | Menjalankan migrasi / perintah destruktif hasil AI **langsung di produksi** |
| 7 | Meminta AI **melonggarkan** mekanisme anti-false-alarm |
| 8 | Menyalin logika alert engine dari AI langsung ke repo |
| 9 | Commit kode yang tidak dipahami |

Pelanggaran dicatat sebagai temuan proyek (lihat [AI Usage Policy §17](docs/AI-USAGE-POLICY.md)). Melaporkan kesalahan sendiri lebih awal selalu diperlakukan lebih ringan.

---

## 13. Struktur Folder

```text
app/            Kode aplikasi Laravel (model, controller, service, `Jobs/SendWhatsAppMessage`)
bootstrap/      Bootstrap framework
config/         Konfigurasi (`tce.php` = fakta kawasan dari proposal EPICS, `services.php` = Fonnte dan kontak publik)
database/       Migrasi, factory, seeder
docs/           PRD.md, AI-USAGE-POLICY.md, DESIGN.md, AI-USAGE-LOG.md
public/         Entry point web & aset publik
resources/      View (Blade), CSS, JS
routes/         Definisi route
storage/        Log, cache, file upload
tests/          Unit & feature test
.agents/        Skills untuk AI agent (Laravel best practices, testing, Tailwind)
```

---

## 14. Troubleshooting

| Masalah | Solusi |
| --- | --- |
| `Class not found` / error autoload | `composer dump-autoload` |
| Perubahan CSS/JS tidak muncul | `npm run build` atau jalankan `npm run dev` |
| Error config / env lama | `php artisan config:clear` |
| `APP_KEY` kosong | `php artisan key:generate` |
| Migrasi gagal | Cek `DB_*` di `.env`, pastikan database sudah dibuat |
| `git push` ditolak (belum ada upstream) | `git push -u origin <nama-branch>` |
| Tidak sengaja commit `.env` / kredensial | **Stop**, jangan push. Hubungi Technical Lead; rotasi kredensial |

---

## Kontak Peran

| Peran | Nama |
| --- | --- |
| Project Leader | Alifah Ghina Salsabila |
| Technical Lead | Abdullah Kafabihi |
| Faculty Advisor | Dr. Indra, S.Kom., M.T.I / Dr. Muhammad Syafrullah, S.Kom., M.Sc |

---

*Dokumen ini turunan dari [PRD](docs/PRD.md) dan [AI Usage Policy](docs/AI-USAGE-POLICY.md). Jika ada konflik, kedua dokumen itu yang berlaku.*
