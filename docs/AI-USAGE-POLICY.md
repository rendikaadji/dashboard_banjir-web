# AI Usage Policy & Scope of Work

## Aturan Penggunaan AI dalam Pengembangan TCE FloodGuard

### Flood Early Warning System — Taman Cipulir Estate, RW 08 | EPICS in IEEE 2026

|                   |                                                                         |
| ----------------- | ----------------------------------------------------------------------- |
| **Versi**         | 1.0                                                                     |
| **Tanggal**       | 27 Agustus 2026                                                         |
| **Berlaku untuk** | Seluruh anggota tim proyek, mahasiswa magang, dan kontributor eksternal |
| **Dokumen induk** | PRD Flood Early Warning System v1.0                                     |
| **Status**        | Wajib dipatuhi — pelanggaran dicatat sebagai temuan proyek              |

---

## Daftar Isi

1. [Tujuan & Ruang Lingkup](#1-tujuan--ruang-lingkup)
2. [Lima Prinsip Dasar](#2-lima-prinsip-dasar)
3. [Peran & Tanggung Jawab](#3-peran--tanggung-jawab)
4. [Klasifikasi Tier Risiko](#4-klasifikasi-tier-risiko)
5. [Matriks Artefak: Boleh, Bersyarat, Terlarang](#5-matriks-artefak-boleh-bersyarat-terlarang)
6. [Aturan Detail per Artefak Teknis](#6-aturan-detail-per-artefak-teknis)
7. [Zona Terlarang Mutlak](#7-zona-terlarang-mutlak)
8. [Aturan Data: Apa yang Tidak Boleh Masuk ke Prompt](#8-aturan-data-apa-yang-tidak-boleh-masuk-ke-prompt)
9. [Alur Kerja Wajib (6 Langkah)](#9-alur-kerja-wajib-6-langkah)
10. [Checklist Verifikasi per Jenis Artefak](#10-checklist-verifikasi-per-jenis-artefak)
11. [Aturan Khusus Komponen Safety-Critical](#11-aturan-khusus-komponen-safety-critical)
12. [Konvensi Penandaan & Jejak Audit](#12-konvensi-penandaan--jejak-audit)
13. [Kepatuhan Disclosure EPICS in IEEE](#13-kepatuhan-disclosure-epics-in-ieee)
14. [Lisensi, Hak Cipta, dan Orisinalitas](#14-lisensi-hak-cipta-dan-orisinalitas)
15. [Praktik Prompting yang Baik](#15-praktik-prompting-yang-baik)
16. [Log Penggunaan AI](#16-log-penggunaan-ai)
17. [Penanganan Pelanggaran](#17-penanganan-pelanggaran)
18. [Peninjauan Kebijakan](#18-peninjauan-kebijakan)
19. [Lampiran](#19-lampiran)

---

## 1. Tujuan & Ruang Lingkup

### 1.1 Kenapa dokumen ini ada

Tiga alasan konkret:

1. **Kepatuhan EPICS in IEEE.** Panduan EPICS menyatakan penggunaan AI yang terbatas dapat diterima, tetapi **proposal yang ditulis terutama oleh AI tidak akan mendapat nilai baik** pada rubrik penilaian. Tim sudah menyatakan menggunakan AI di form disclosure. Dokumen ini memastikan pernyataan itu jujur, konsisten, dan bisa dipertanggungjawabkan saat laporan 3 bulanan.
2. **Ini sistem keselamatan jiwa.** Kesalahan pada logika alert bisa berarti warga tidak dievakuasi tepat waktu, atau perahu karet diturunkan di air dangkal. Kode yang "kelihatan benar" tapi tidak dipahami penulisnya adalah risiko nyata, bukan risiko teoretis.
3. **Keberlanjutan.** Sistem akan diserahkan ke pengurus RW dan diteruskan mahasiswa magang tiap semester. Kode yang tidak dipahami siapa pun akan mati begitu tim awal lulus.

### 1.2 Yang diatur dokumen ini

Seluruh penggunaan **AI generatif** (Claude, ChatGPT, Gemini, Copilot, Cursor, DeepL, dan sejenisnya) oleh tim dalam:

- Penulisan kode (backend, frontend, firmware, skrip)
- Desain arsitektur, skema database, kontrak API
- Penulisan dokumen, laporan, dan materi sosialisasi
- Analisis data lapangan
- Terjemahan Indonesia ↔ Inggris

### 1.3 Yang TIDAK diatur dokumen ini

- **Modul AI/ML yang berjalan di dalam produk** (nowcasting, deteksi anomali) — itu diatur di PRD §11. Dua hal berbeda: dokumen ini soal _AI sebagai alat bantu tim_, PRD §11 soal _AI sebagai fitur produk_.
- Alat non-generatif: linter, formatter, autocomplete berbasis sintaks, spell-check.

---

## 2. Lima Prinsip Dasar

> **P1 — Manusia yang bertanggung jawab, selalu.**
> Setiap artefak punya satu **Human Owner** bernama. "AI yang membuat" bukan alasan yang sah untuk kesalahan apa pun. Kalau ditanya penguji EPICS atau dosen pembimbing, Human Owner harus bisa menjelaskan setiap baris.

> **P2 — Tidak paham = tidak masuk repo.**
> Kalau anggota tim tidak bisa menjelaskan cara kerja sebuah fungsi tanpa membuka AI lagi, kode itu **tidak boleh di-commit**. Ini aturan paling penting di dokumen ini.

> **P3 — AI mengusulkan, lapangan memutuskan.**
> Angka ambang batas, penempatan sensor, dan asumsi hidrologi ditentukan oleh survei lapangan dan kesepakatan dengan RW 08 — bukan oleh keluaran model.

> **P4 — Data warga dan kredensial tidak pernah masuk prompt.**
> Nomor WhatsApp, nama warga, token API, isi `.env` — tidak pernah, dalam bentuk apa pun.

> **P5 — Semua jejak tercatat.**
> Penggunaan AI dicatat di commit dan di Log Penggunaan AI. Disclosure ke EPICS harus cocok dengan log ini.

---

## 3. Peran & Tanggung Jawab

| Peran                         | Siapa                                                            | Tanggung jawab terkait AI                                                            |
| ----------------------------- | ---------------------------------------------------------------- | ------------------------------------------------------------------------------------ |
| **Project Leader**            | Alifah Ghina Salsabila                                           | Pemilik disclosure EPICS; memastikan Log AI terisi; menandatangani laporan 3 bulanan |
| **Technical Lead**            | Abdullah Kafabihi                                                | Menyetujui semua artefak Tier 2 & 3; reviewer wajib untuk alert engine & firmware    |
| **Faculty Advisor**           | Dr. Indra, S.Kom., M.T.I / Dr. Muhammad Syafrullah, S.Kom., M.Sc | Persetujuan akhir untuk klaim keselamatan, etika, dan naskah akademik                |
| **Human Owner (per artefak)** | Anggota tim yang meng-commit                                     | Memahami, memverifikasi, menguji, dan mempertanggungjawabkan artefak                 |
| **Reviewer**                  | Anggota tim lain (bukan Human Owner)                             | Menolak PR yang tidak lolos checklist §10                                            |
| **AI**                        | Claude / ChatGPT / Copilot dll.                                  | **Asisten. Bukan penulis, bukan pengambil keputusan, bukan penanggung jawab.**       |

**Aturan pemisahan:** Human Owner tidak boleh menjadi Reviewer untuk artefaknya sendiri. Untuk Tier 3, dibutuhkan **dua** reviewer, salah satunya Technical Lead.

---

## 4. Klasifikasi Tier Risiko

|  Tier  | Nama                     | Dampak bila salah                                       | Aturan AI                                                                                  | Gerbang persetujuan                                                    |
| :----: | ------------------------ | ------------------------------------------------------- | ------------------------------------------------------------------------------------------ | ---------------------------------------------------------------------- |
| **T0** | Bebas                    | Tidak ada dampak ke sistem produksi                     | AI bebas dipakai, tanpa review khusus                                                      | —                                                                      |
| **T1** | Rendah                   | Bug kosmetik, dokumentasi keliru                        | AI boleh membuat draft; Human Owner memverifikasi                                          | Self-review + 1 reviewer                                               |
| **T2** | Sedang                   | Data salah tersimpan, dashboard menyesatkan, API rusak  | AI boleh membantu; wajib ada uji otomatis + review kode baris demi baris                   | 1 reviewer + test hijau                                                |
| **T3** | Tinggi (safety-critical) | **Warga tidak diperingatkan, atau diperingatkan palsu** | AI hanya boleh untuk eksplorasi & review; **implementasi final ditulis dan diuji manusia** | 2 reviewer (termasuk Technical Lead) + 12 skenario uji §18.2 PRD lulus |
| **T4** | Terlarang                | Integritas proyek & kredibilitas IEEE                   | **AI tidak boleh dipakai sama sekali**                                                     | —                                                                      |

---

## 5. Matriks Artefak: Boleh, Bersyarat, Terlarang

Legenda: ✅ boleh penuh · ⚠️ boleh dengan syarat · 🔶 hanya draft/eksplorasi · ⛔ terlarang

| #    | Artefak                                                                |  Tier  | Status | Syarat verifikasi minimum                                                                                               | Human Owner         |
| ---- | ---------------------------------------------------------------------- | :----: | :----: | ----------------------------------------------------------------------------------------------------------------------- | ------------------- |
| A-01 | Diagram arsitektur sistem (draft)                                      |   T1   |   ✅   | Ditinjau Technical Lead; komponen dicocokkan dengan hardware yang benar-benar dibeli                                    | Technical Lead      |
| A-02 | Keputusan arsitektur final (edge vs cloud, protokol)                   |   T2   |   ⚠️   | Ditulis dalam ADR oleh manusia; AI boleh diminta membandingkan opsi                                                     | Technical Lead      |
| A-03 | Skema database / DDL                                                   |   T2   |   ⚠️   | Dijalankan di staging; setiap tabel & kolom bisa dijelaskan Human Owner; migrasi diuji rollback                         | Backend dev         |
| A-04 | Migrasi DB pada database berisi data nyata                             |   T3   |   🔶   | Skrip ditulis manusia; wajib backup + dry-run + uji restore sebelum dijalankan                                          | Technical Lead      |
| A-05 | Kontrak MQTT & payload JSON                                            |   T2   |   ⚠️   | Diuji dengan node simulator dan minimal 1 node fisik                                                                    | Firmware dev        |
| A-06 | Spesifikasi REST/WebSocket API (OpenAPI)                               |   T1   |   ✅   | Endpoint diuji lewat Postman/integration test                                                                           | Backend dev         |
| A-07 | Kode backend CRUD, service, repository                                 |   T2   |   ⚠️   | Unit test ≥ 80% pada modul terkait; review baris demi baris                                                             | Backend dev         |
| A-08 | **Logika alert engine** (sustain, hysteresis, corroboration, eskalasi) | **T3** |   🔶   | **Implementasi final ditulis manusia.** AI boleh mengusulkan pseudocode dan mereview. Wajib lulus 12 skenario §18.2 PRD | Technical Lead      |
| A-09 | **Nilai ambang batas (100/150 cm, laju kenaikan, dsb.)**               | **T4** |   ⛔   | Ditetapkan dari survei lapangan + kesepakatan tertulis RW 08                                                            | Project Leader + RW |
| A-10 | Firmware ESP-32 (pembacaan sensor, kontrol relay)                      |   T3   |   🔶   | AI boleh untuk boilerplate WiFi/MQTT. Logika sensor & aktuator ditulis manusia, diuji di hardware nyata                 | Firmware dev        |
| A-11 | Rumus kalibrasi & konversi unit                                        |   T3   |   🔶   | Diturunkan manusia dari pengukuran lapangan; diverifikasi dengan ≥ 30 pengukuran manual                                 | Technical Lead      |
| A-12 | Filter EWMA & parameter α                                              |   T2   |   ⚠️   | Implementasi boleh dibantu AI; **nilai α ditentukan dari data lapangan**, bukan dari saran AI                           | Backend dev         |
| A-13 | Komponen UI React, layout, styling                                     |   T1   |   ✅   | Diuji manual di browser target + mode kiosk                                                                             | Frontend dev        |
| A-14 | Teks/label antarmuka bahasa Indonesia                                  |   T1   |   ⚠️   | Wajib dibaca minimal 1 orang non-teknis (satpam/pengurus) sebelum rilis                                                 | Frontend dev        |
| A-15 | **Isi pesan peringatan WhatsApp ke warga**                             | **T3** |   🔶   | Draft boleh dibantu AI. **Wajib disetujui tertulis oleh Pengurus RW 08** sebelum aktif                                  | Project Leader      |
| A-16 | Query SQL & optimasi index                                             |   T2   |   ⚠️   | `EXPLAIN ANALYZE` dijalankan; diuji dengan ≥ 1 juta baris data simulasi                                                 | Backend dev         |
| A-17 | Unit test & integration test                                           |   T1   |   ✅   | Test harus benar-benar gagal saat kode dirusak sengaja (mutation check sederhana)                                       | Pemilik modul       |
| A-18 | **Skenario uji safety (12 skenario §18.2)**                            |   T3   |   🔶   | Skenario dirumuskan manusia dari kondisi lapangan nyata; AI boleh membantu menuliskan kodenya                           | Technical Lead      |
| A-19 | Node simulator & data dummy                                            |   T0   |   ✅   | Bebas — data sintetis, tanpa risiko                                                                                     | Siapa pun           |
| A-20 | Skrip DevOps, Dockerfile, CI/CD                                        |   T2   |   ⚠️   | Diuji di staging; tidak ada secret ter-hardcode                                                                         | DevOps              |
| A-21 | Konfigurasi keamanan (TLS, ACL MQTT, RBAC)                             |   T3   |   🔶   | Draft boleh AI, konfigurasi final diverifikasi manusia + uji penetrasi dasar (OWASP ZAP)                                | Technical Lead      |
| A-22 | Dokumentasi teknis, README, manual admin                               |   T1   |   ✅   | Setiap langkah dijalankan ulang oleh orang lain dan terbukti berhasil                                                   | Penulis dokumen     |
| A-23 | Manual operator untuk satpam                                           |   T1   |   ⚠️   | Wajib diuji-baca oleh minimal 3 satpam; direvisi sesuai masukan mereka                                                  | Project Leader      |
| A-24 | Terjemahan Indonesia → Inggris untuk laporan                           |   T1   |   ✅   | Dibaca ulang manusia; istilah teknis & angka diverifikasi tidak berubah                                                 | Project Leader      |
| A-25 | **Naskah inti proposal EPICS** (Problem Statement, Innovation, Impact) | **T4** |   ⛔   | Harus tulisan tim. AI hanya boleh untuk perbaikan tata bahasa & terjemahan (sesuai disclosure yang sudah diserahkan)    | Project Leader      |
| A-26 | **Klaim keselamatan & etika** ("nol bahaya listrik", pernyataan IRB)   | **T4** |   ⛔   | Hanya boleh dinyatakan pembimbing setelah asesmen nyata                                                                 | Faculty Advisor     |
| A-27 | **Angka KPI hasil pengujian** (0,561 s; 98,86%; error < 10%)           | **T4** |   ⛔   | Hanya dari pengukuran nyata. **AI tidak boleh diminta "mengestimasi" atau "membulatkan" angka hasil**                   | Technical Lead      |
| A-28 | Analisis statistik data lapangan                                       |   T2   |   ⚠️   | Metode dipilih manusia; hasil direproduksi ulang dengan skrip yang tersimpan di repo                                    | Data owner          |
| A-29 | Visualisasi & grafik untuk laporan                                     |   T1   |   ✅   | Angka sumber dicek ulang terhadap database                                                                              | Penulis laporan     |
| A-30 | **Anggaran & harga komponen**                                          | **T4** |   ⛔   | Harga diambil dari marketplace/vendor nyata dengan URL, bukan dari ingatan model                                        | Finance Manager     |
| A-31 | **Surat/komunikasi resmi ke mitra RW & IEEE**                          |   T3   |   🔶   | Draft boleh dibantu, wajib disunting & ditandatangani manusia; tidak boleh mengandung klaim yang belum terverifikasi    | Project Leader      |
| A-32 | Materi sosialisasi warga (poster, booklet)                             |   T1   |   ⚠️   | Disetujui Pengurus RW; klaim kemampuan sistem harus akurat, tidak berlebihan                                            | Publicity           |
| A-33 | Model ML nowcasting (kode training)                                    |   T2   |   ⚠️   | Dilatih pada data nyata; metrik dilaporkan apa adanya termasuk saat buruk                                               | Data owner          |
| A-34 | Refactoring kode yang sudah lulus uji                                  |   T2   |   ⚠️   | Semua test yang ada harus tetap hijau sebelum & sesudah                                                                 | Pemilik modul       |
| A-35 | Debugging & analisis pesan error                                       |   T0   |   ✅   | Bebas (selama tidak menempelkan kredensial atau data warga)                                                             | Siapa pun           |

---

## 6. Aturan Detail per Artefak Teknis

### 6.1 Arsitektur Sistem (A-01, A-02)

**Boleh:**

- Meminta AI membandingkan opsi: MQTT vs HTTP polling, TimescaleDB vs InfluxDB, edge-first vs cloud-first — lengkap dengan trade-off.
- Meminta draft diagram (Mermaid/ASCII) dan daftar komponen.
- Meminta AI mengkritik arsitektur yang sudah tim susun ("apa titik kegagalan tunggal di desain ini?").

**Wajib dilakukan manusia:**

- **Keputusan akhir** dan alasannya, ditulis sebagai **ADR (Architecture Decision Record)** dengan format:
    ```
    ADR-00X: <Judul keputusan>
    Status: Diterima | Ditolak | Digantikan oleh ADR-00Y
    Konteks: <kondisi lapangan nyata yang mendorong keputusan>
    Keputusan: <apa yang dipilih>
    Alasan: <kenapa, dikaitkan dengan kendala nyata: listrik mati, internet putus, anggaran>
    Konsekuensi: <apa yang jadi lebih sulit karena ini>
    Bantuan AI: Ya/Tidak — <bagian mana>
    ```
- Memverifikasi setiap komponen di diagram **benar-benar ada di daftar belanja**. AI cenderung menambahkan komponen ideal (load balancer, message broker terpisah, Kubernetes) yang tidak ada di anggaran $8.629,78 dan tidak muat di Raspberry Pi 5.

**Larangan spesifik:**

- ⛔ Menyalin arsitektur yang menyebut layanan berbayar yang tidak dianggarkan.
- ⛔ Menerima saran arsitektur yang membuat sistem **bergantung pada internet untuk mengambil keputusan alert** — ini melanggar prinsip desain di PRD §5.1.

### 6.2 Skema Database (A-03, A-04)

**Boleh:**

- Draft DDL, penamaan tabel/kolom, tipe data, relasi, index.
- Usulan normalisasi, partisi/hypertable, continuous aggregate.
- Menghasilkan file migrasi awal (belum ada data).

**Wajib diverifikasi manusia sebelum merge:**

| Aspek      | Yang harus dicek                                                                                 |
| ---------- | ------------------------------------------------------------------------------------------------ |
| Tipe data  | Presisi numerik cukup? `NUMERIC(7,2)` untuk cm — apakah rentangnya masuk akal untuk lokasi ini?  |
| Timezone   | Semua kolom waktu `TIMESTAMPTZ`, bukan `TIMESTAMP`. Salah di sini = urutan kejadian banjir kacau |
| Constraint | FK, UNIQUE, NOT NULL, CHECK benar-benar mencerminkan aturan bisnis                               |
| Index      | Sesuai pola query nyata, bukan index spekulatif yang memperlambat insert 1 Hz                    |
| Retensi    | Kebijakan retensi §7.2 PRD terimplementasi, tidak membuat disk Pi penuh                          |
| Nama       | Konsisten `snake_case`, bahasa Inggris, tanpa singkatan misterius                                |

**Larangan spesifik:**

- ⛔ Menjalankan DDL/migrasi hasil AI **langsung di database produksi**. Urutan wajib: local → staging → backup produksi → dry-run → produksi.
- ⛔ `DROP TABLE`, `DROP COLUMN`, `TRUNCATE` hasil generate tanpa review dua orang.
- ⛔ Memasukkan **data nyata warga** ke prompt saat meminta bantuan query. Gunakan data anonim/dummy.

**Aturan emas migrasi:** setiap migrasi wajib punya pasangan `down`/rollback yang **sudah diuji**, bukan sekadar ditulis.

### 6.3 Kode Backend & API (A-06, A-07, A-16)

**Boleh:** boilerplate controller/service/DTO, validasi input, error handling, dokumentasi OpenAPI, query builder.

**Wajib:**

- Baca setiap baris sebelum commit. Jika ada baris yang tidak dipahami → tanya, pelajari, atau tulis ulang sendiri.
- Cek dependency yang diusulkan AI: masih dipelihara? lisensinya apa? benar-benar perlu?
- Cek **halusinasi API**: fungsi/parameter library yang tidak ada. Selalu cocokkan dengan dokumentasi resmi.
- Semua input eksternal (payload MQTT, body request) divalidasi eksplisit — jangan percaya validasi yang "sepertinya sudah ada".

**Larangan spesifik:**

- ⛔ Menempelkan `.env`, connection string, token WhatsApp API, atau kredensial MQTT ke dalam prompt.
- ⛔ Menerima kode yang menyusun query SQL lewat penggabungan string (risiko SQL injection).

### 6.4 Alert Engine (A-08) — **paling ketat**

Ini jantung sistem. Kesalahan di sini adalah kesalahan yang bisa melukai orang.

**Boleh:**

- Meminta AI menjelaskan konsep hysteresis, debouncing, state machine.
- Meminta AI **mereview** implementasi yang sudah tim tulis dan mencari celah ("skenario apa yang membuat logika ini gagal?").
- Meminta AI menuliskan **kode uji** untuk skenario yang sudah tim rumuskan.

**Wajib:**

- **Implementasi final ditulis oleh manusia**, dengan komentar berbahasa Indonesia yang menjelaskan _kenapa_, bukan hanya _apa_.
- State machine digambar di kertas/diagram dan disetujui Technical Lead **sebelum** kode ditulis.
- Lulus seluruh 12 skenario uji di PRD §18.2, dijalankan di CI setiap commit.
- Setiap perubahan pada alert engine wajib disertai catatan di `CHANGELOG-SAFETY.md`.

**Larangan mutlak:**

- ⛔ Menyalin logika alert hasil generate AI langsung ke repo, sekalipun test-nya hijau.
- ⛔ Mengubah logika eskalasi/de-eskalasi tanpa persetujuan Technical Lead.
- ⛔ Meminta AI "menyederhanakan" alert engine dengan menghapus sustain/hysteresis/corroboration.

### 6.5 Firmware ESP-32 (A-10)

**Boleh:** boilerplate koneksi WiFi, klien MQTT dengan reconnect, serialisasi JSON, watchdog, OTA.

**Wajib manusia:** pembacaan sensor (timing ultrasonik, interrupt Hall-effect & tipping bucket), kontrol relay/buzzer, manajemen daya & deep sleep, ambang fail-safe lokal.

**Alasan:** AI sering menghasilkan kode Arduino yang secara sintaks benar tetapi salah pada timing interrupt, debouncing mekanis, atau pin yang tidak tersedia di board tertentu — dan ini baru ketahuan di lapangan saat hujan deras.

**Wajib diuji di hardware nyata**, bukan hanya di simulator, sebelum dipasang di lokasi.

### 6.6 Frontend & UX (A-13, A-14)

**Boleh:** komponen React, styling Tailwind, layout responsif, konfigurasi chart, state management.

**Wajib:**

- Uji nyata di **monitor pos satpam** dalam mode kiosk, dilihat dari jarak 3 meter.
- Uji di HP kelas menengah dengan koneksi lambat.
- Semua teks dibaca oleh orang non-teknis. AI cenderung menghasilkan bahasa Indonesia yang kaku atau kebarat-baratan ("Silakan lakukan verifikasi terhadap status perangkat") — harus diganti ke bahasa yang wajar ("Cek dulu alatnya nyala atau tidak").
- Warna level tidak boleh diubah AI tanpa persetujuan — sudah disepakati di PRD §13.2.

### 6.7 Dokumentasi & Laporan (A-22 – A-32)

**Boleh:** struktur dokumen, draft awal, perbaikan tata bahasa, terjemahan, ringkasan.

**Wajib:**

- Setiap instruksi teknis dalam manual **dijalankan ulang oleh orang lain** dan terbukti berhasil. Manual yang salah lebih berbahaya daripada tidak ada manual.
- Setiap angka, tanggal, nama, dan URL diverifikasi ke sumber aslinya.
- Untuk laporan EPICS: gaya bahasa boleh dirapikan AI, **substansi dan klaim harus dari tim**.

**Larangan spesifik:**

- ⛔ Meminta AI "mengisi" bagian laporan yang datanya belum ada. Bagian kosong ditulis "belum tersedia", bukan diisi karangan.
- ⛔ Meminta AI membuat referensi/sitasi — risiko sitasi palsu sangat tinggi. Semua referensi dicari manual dan dibuka satu per satu.

---

## 7. Zona Terlarang Mutlak

Berikut hal yang **tidak boleh sama sekali** menggunakan AI, tanpa pengecualian dan tanpa persetujuan siapa pun:

| ⛔  | Terlarang                                                                                         | Alasan                                                                                             |
| :-: | ------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------- |
|  1  | Menentukan atau mengubah **nilai ambang batas banjir**                                            | Harus dari survei lapangan + kesepakatan RW; salah = alert palsu atau alert terlambat              |
|  2  | **Mengarang atau memperkirakan angka hasil pengujian** (latensi, akurasi, error margin)           | Fabrikasi data = pelanggaran integritas akademik & penipuan terhadap pemberi hibah                 |
|  3  | Menulis **klaim keselamatan** ("nol risiko bahaya listrik", "tidak akan gagal")                   | Hanya boleh setelah asesmen nyata oleh pembimbing                                                  |
|  4  | Menulis **naskah inti proposal** (Problem Statement, Innovation, Impact)                          | Melanggar semangat rubrik EPICS; disclosure tim menyatakan AI hanya untuk terjemahan & tata bahasa |
|  5  | Membuat/menyunting **surat dukungan mitra** atau tanda tangan                                     | Pemalsuan dokumen                                                                                  |
|  6  | Memasukkan **data pribadi warga** ke layanan AI                                                   | Melanggar UU PDP No. 27/2022 & komitmen etika ke RW                                                |
|  7  | Memasukkan **kredensial, token, atau isi `.env`** ke prompt                                       | Kebocoran keamanan permanen                                                                        |
|  8  | **Menjalankan perintah destruktif** hasil generate langsung di produksi                           | Kehilangan data historis banjir yang tidak bisa diulang                                            |
|  9  | Membuat **harga/anggaran** dari ingatan model                                                     | Harga komponen berubah; anggaran hibah harus dapat diaudit dengan URL sumber                       |
| 10  | Menggunakan AI untuk **mengirim notifikasi otomatis ke warga** tanpa aturan deterministik         | Pesan ke 1.300 orang tidak boleh bergantung pada keluaran probabilistik                            |
| 11  | Meminta AI **menonaktifkan atau melonggarkan** mekanisme anti-false-alarm                         | Merusak fondasi kepercayaan warga                                                                  |
| 12  | Menyalin kode dari AI yang jelas mereproduksi karya berlisensi ketat (mis. GPL ke proyek non-GPL) | Pelanggaran lisensi                                                                                |

---

## 8. Aturan Data: Apa yang Tidak Boleh Masuk ke Prompt

### 8.1 Daftar merah (jangan pernah)

- Nama lengkap, nomor WhatsApp, atau alamat warga
- Isi file `.env`, `secrets.yaml`, kunci privat, sertifikat
- Token WhatsApp Business API, kredensial MQTT, password database
- Isi tabel `recipients`, `users`, atau `audit_logs` yang belum dianonimkan
- Dokumen bertanda tangan (surat dukungan, KTP, dokumen kelurahan)
- Foto warga atau rumah yang bisa mengidentifikasi orang
- Koordinat GPS presisi rumah warga (koordinat titik sensor di area publik: boleh)

### 8.2 Daftar kuning (boleh setelah dianonimkan)

| Data                            | Cara menganonimkan                                     |
| ------------------------------- | ------------------------------------------------------ |
| Baris data `readings`           | Boleh apa adanya — data lingkungan, bukan data pribadi |
| Contoh baris `recipients`       | Ganti nama → `Warga A`, nomor → `+6281200000001`       |
| Log error yang memuat path/host | Ganti host, user, dan path ke placeholder              |
| Struktur database               | Boleh — skema bukan data                               |
| Screenshot dashboard            | Sensor/blur nama & nomor sebelum diunggah              |

### 8.3 Daftar hijau (bebas)

Skema database, kode tanpa kredensial, dokumentasi publik, pesan error umum, data sintetis dari node simulator, isi PRD, standar & referensi teknis.

### 8.4 Aturan tambahan

- Gunakan akun AI **milik pribadi anggota tim**, bukan akun bersama yang password-nya beredar.
- Jika secara tidak sengaja kredensial masuk ke prompt: **rotasi kredensial itu dalam 24 jam** dan catat sebagai insiden. Menghapus percakapan saja tidak cukup.

---

## 9. Alur Kerja Wajib (6 Langkah)

Berlaku untuk setiap artefak Tier 1 ke atas yang dibantu AI.

```
1. RUMUSKAN     Tentukan sendiri dulu apa yang dibutuhkan dan batasannya.
                Jangan buka AI dengan pertanyaan kosong "bikinkan sistem EWS".

2. MINTA        Beri konteks lengkap: kendala hardware, versi library,
                gaya kode tim, hal yang TIDAK boleh dipakai.

3. PAHAMI       Baca hasilnya. Untuk setiap bagian yang tidak dipahami:
                tanya balik sampai paham, atau tulis ulang sendiri.
                ⛔ Dilarang lanjut ke langkah 4 kalau masih ada yang gelap.

4. VERIFIKASI   Jalankan checklist §10 sesuai jenis artefak.
                Cek dokumentasi resmi untuk setiap API/fungsi yang dipakai.

5. UJI          Jalankan di local → staging. Tulis/ jalankan test.
                Untuk T3: seluruh 12 skenario safety wajib hijau.

6. CATAT        Commit dengan trailer AI (§12), isi Log Penggunaan AI (§16),
                minta review sesuai tier.
```

**Aturan waktu:** jika langkah 3 (memahami) memakan waktu lebih lama daripada menulis sendiri dari awal — tulis sendiri. AI seharusnya menghemat waktu, bukan memindahkan beban ke waktu debugging.

---

## 10. Checklist Verifikasi per Jenis Artefak

### 10.1 Checklist Arsitektur

- [ ] Setiap komponen ada di daftar belanja / sudah dimiliki tim
- [ ] Muat dijalankan di Raspberry Pi 5 (8 GB) tanpa kehabisan RAM
- [ ] Tidak ada keputusan alert yang bergantung pada koneksi internet
- [ ] Ada jalur fail-safe saat listrik/internet mati
- [ ] Tidak menambah biaya berjalan di luar anggaran RW pasca-hibah
- [ ] Ada ADR tertulis dengan alasan yang mengacu ke kondisi lapangan nyata
- [ ] Ditinjau dan disetujui Technical Lead

### 10.2 Checklist Skema Database

- [ ] Semua kolom waktu `TIMESTAMPTZ`, default timezone `Asia/Jakarta` terdokumentasi
- [ ] Setiap tabel & kolom bisa dijelaskan Human Owner tanpa membuka AI
- [ ] Foreign key, UNIQUE, NOT NULL, CHECK sesuai aturan bisnis di PRD §7
- [ ] Index sesuai pola query nyata (dibuktikan dengan `EXPLAIN ANALYZE`)
- [ ] Diuji dengan ≥ 1 juta baris `readings` — insert 1 Hz tetap lancar
- [ ] Migrasi punya rollback yang sudah diuji
- [ ] Tidak ada perintah destruktif tanpa review dua orang
- [ ] Backup diambil sebelum migrasi apa pun di staging/produksi

### 10.3 Checklist Kode

- [ ] Setiap baris dipahami Human Owner
- [ ] Tidak ada API/fungsi halusinasi (dicek ke dokumentasi resmi)
- [ ] Tidak ada kredensial, token, atau URL internal ter-hardcode
- [ ] Input eksternal divalidasi eksplisit
- [ ] Error ditangani, tidak ada `catch` kosong yang menelan kesalahan diam-diam
- [ ] Penanganan waktu konsisten (UTC di penyimpanan, WIB di tampilan)
- [ ] Test ditulis dan benar-benar gagal saat kode dirusak sengaja
- [ ] Lint & format lulus di CI
- [ ] Dependency baru dicek lisensi & pemeliharaannya

### 10.4 Checklist Alert Engine (T3)

- [ ] Diagram state machine disetujui **sebelum** kode ditulis
- [ ] Implementasi final ditulis manusia
- [ ] 12 skenario §18.2 PRD lulus di CI
- [ ] Sustain, hysteresis, corroboration, cooldown semuanya aktif dan teruji
- [ ] Data `buffered:true` terbukti tidak memicu alert
- [ ] Perilaku saat semua node offline: alert ke operator, **bukan** status hijau
- [ ] Mode maintenance terbukti menahan notifikasi eksternal
- [ ] Dua reviewer menyetujui, salah satunya Technical Lead
- [ ] `CHANGELOG-SAFETY.md` diperbarui

### 10.5 Checklist Dokumen & Laporan

- [ ] Semua angka diverifikasi ke sumber (database/pengukuran/nota pembelian)
- [ ] Semua referensi dibuka satu per satu dan benar-benar ada
- [ ] Tidak ada klaim yang belum diuji
- [ ] Bagian tanpa data ditulis "belum tersedia", bukan diisi karangan
- [ ] Bahasa Indonesia wajar dan mudah dipahami non-teknis
- [ ] Untuk dokumen mitra: disetujui Pengurus RW sebelum disebar

---

## 11. Aturan Khusus Komponen Safety-Critical

Komponen berikut ditandai **safety-critical** dan tunduk pada aturan tambahan:

| Komponen                  | File/modul             | Aturan tambahan                                                   |
| ------------------------- | ---------------------- | ----------------------------------------------------------------- |
| Alert Engine              | `src/alert/*`          | Wajib 2 reviewer; PR diblokir CI bila skenario safety gagal       |
| Threshold Service         | `src/threshold/*`      | Perubahan nilai hanya lewat UI dengan audit log, bukan lewat kode |
| Notification Dispatcher   | `src/notify/*`         | Perubahan template pesan butuh persetujuan RW                     |
| Firmware fail-safe lokal  | `firmware/failsafe.*`  | Diuji di hardware; ambang lokal hanya boleh diubah Technical Lead |
| Kalkulasi TMA & kalibrasi | `src/ingest/convert.*` | Diverifikasi dengan ≥ 30 pengukuran manual                        |

**Aturan CODEOWNERS:** file di jalur di atas otomatis membutuhkan review Technical Lead melalui GitHub CODEOWNERS.

**Aturan "jangan sentuh saat musim hujan":** perubahan pada komponen safety-critical **tidak boleh di-deploy** saat prakiraan hujan lebat dalam 24 jam ke depan, kecuali untuk memperbaiki bug kritis. Deploy dijadwalkan pada cuaca cerah dengan tim siaga.

---

## 12. Konvensi Penandaan & Jejak Audit

### 12.1 Commit message

```
feat(ingest): tambah validasi rentang pembacaan sensor ultrasonik

Menolak nilai di luar rentang mount_height, menandai OUT_OF_RANGE
agar tidak masuk perhitungan fusion.

AI-Assisted: yes
AI-Tool: Claude Opus 5
AI-Scope: draft fungsi validasi + unit test
Human-Owner: <nama>
Reviewed-by: <nama reviewer>
Tier: T2
```

Untuk commit tanpa bantuan AI, cukup `AI-Assisted: no`.

### 12.2 Header file (untuk file yang sebagian besar dibantu AI)

```ts
/**
 * Modul: Validasi & normalisasi payload telemetri
 * Human Owner : <nama>
 * Tier        : T2
 * AI-Assisted : ya — draft awal struktur validasi (Claude),
 *               logika rentang & unit test ditulis & diverifikasi manusia
 * Terakhir diverifikasi: 2026-08-27 oleh <nama>
 */
```

### 12.3 Pull Request template (wajib diisi)

```markdown
## Ringkasan

<apa yang berubah dan kenapa>

## Tier risiko

- [ ] T0 - [ ] T1 - [ ] T2 - [ ] T3

## Penggunaan AI

- Dibantu AI? Ya / Tidak
- Alat:
- Bagian mana yang dibantu:
- Bagian mana yang ditulis manusia:

## Verifikasi

- [ ] Saya memahami setiap baris perubahan ini
- [ ] Checklist §10 yang relevan sudah dijalankan
- [ ] Test lulus di CI
- [ ] Tidak ada kredensial/data warga di dalam perubahan ini
- [ ] (T3) Skenario safety §18.2 lulus & 2 reviewer disetujui
```

---

## 13. Kepatuhan Disclosure EPICS in IEEE

### 13.1 Yang sudah dinyatakan tim di proposal

Tim menyatakan menggunakan AI sebagai **alat bantu terjemahan dan perapian bahasa** untuk bagian Problem Statement, Project Description, dan Student Learning Outcomes.

**Konsekuensi:** penggunaan AI di luar cakupan itu untuk naskah proposal akan membuat disclosure menjadi tidak akurat. Karena itu A-25 diklasifikasikan T4 (terlarang).

### 13.2 Aturan disclosure untuk laporan berikutnya

Setiap laporan 3 bulanan ke EPICS wajib menyertakan bagian:

```
Penggunaan AI dalam periode ini
- Alat yang digunakan: <daftar>
- Cakupan: <mis. boilerplate kode, dokumentasi teknis, terjemahan>
- Yang TIDAK dibantu AI: penentuan ambang batas, hasil pengujian lapangan,
  logika alert engine, klaim keselamatan, anggaran
- Mekanisme verifikasi: setiap artefak memiliki Human Owner dan melalui
  review sesuai tier risiko (lihat AI Usage Policy v1.0)
```

Angka dan cakupan diambil langsung dari **Log Penggunaan AI (§16)** — bukan dari ingatan.

### 13.3 Prinsip komunikasi ke pihak luar

- Jujur: jangan menyembunyikan penggunaan AI, jangan pula membesar-besarkannya.
- Konsisten: apa yang ditulis di disclosure harus cocok dengan commit log dan Log AI.
- Kalau ditanya penguji: setiap anggota tim harus bisa menjelaskan bagian yang menjadi tanggung jawabnya secara lisan, tanpa membuka catatan AI.

---

## 14. Lisensi, Hak Cipta, dan Orisinalitas

| Aturan | Detail                                                                                                                                                                                      |
| ------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| L-01   | Setiap potongan kode yang tampak berasal dari proyek open-source tertentu wajib dicari sumbernya dan dicantumkan atribusi + lisensinya                                                      |
| L-02   | Dilarang memasukkan kode berlisensi copyleft ketat (GPL/AGPL) ke basis kode ini tanpa persetujuan Technical Lead & pembimbing                                                               |
| L-03   | Semua dependency dicatat di `THIRD-PARTY-LICENSES.md` beserta lisensinya                                                                                                                    |
| L-04   | Gambar/ikon hasil generate AI tidak dipakai pada dokumen resmi IEEE bila status hak ciptanya tidak jelas; gunakan aset berlisensi jelas (mis. Lucide, Heroicons)                            |
| L-05   | Naskah akademik (skripsi, paper) tunduk pada aturan plagiarisme universitas — lebih ketat dari dokumen ini bila bertentangan                                                                |
| L-06   | Hak cipta kode proyek dipegang tim & Universitas Budi Luhur sesuai ketentuan hibah EPICS; lisensi rilis disepakati sebelum serah terima (usulan: MIT atau Apache-2.0 agar RW bebas memakai) |

---

## 15. Praktik Prompting yang Baik

### 15.1 Prinsip

1. **Beri konteks, bukan sekadar perintah.** Sebutkan: target hardware (Raspberry Pi 5, ESP-32), versi library, kendala (offline-capable, hemat daya), dan gaya kode tim.
2. **Minta alternatif dan trade-off**, bukan satu jawaban. "Beri 3 pendekatan dengan kelebihan-kekurangan" jauh lebih berguna daripada "buatkan".
3. **Minta AI mengkritik hasil kerja tim.** Ini pemakaian paling bernilai: "cari kelemahan di logika ini".
4. **Selalu minta penjelasan.** Kode tanpa penjelasan tidak bisa lolos langkah 3 alur kerja.
5. **Potong tugas kecil-kecil.** Satu fungsi per permintaan lebih mudah diverifikasi daripada satu modul utuh.

### 15.2 Template prompt yang disarankan

```
Konteks proyek:
- Sistem peringatan dini banjir, kompleks perumahan 1.300 jiwa, Tangerang
- Backend: NestJS + TypeScript, PostgreSQL 16 + TimescaleDB, MQTT (Mosquitto)
- Berjalan di Raspberry Pi 5, harus tetap berfungsi tanpa internet
- Kode dan komentar berbahasa Indonesia

Tugas:
<satu tugas spesifik>

Batasan:
- Jangan menambah dependency baru tanpa alasan kuat
- Jangan gunakan fitur yang butuh koneksi internet
- Sertakan penjelasan tiap bagian dan asumsi yang kamu ambil

Yang saya butuhkan:
- 2 pendekatan alternatif beserta trade-off-nya
- Daftar kasus tepi yang perlu saya uji
```

### 15.3 Pola yang harus dihindari

| ⛔ Prompt buruk                      | Kenapa                                                      | ✅ Ganti dengan                                                     |
| ------------------------------------ | ----------------------------------------------------------- | ------------------------------------------------------------------- |
| "Buatkan sistem EWS banjir lengkap"  | Hasil generik, tidak sesuai lapangan, mustahil diverifikasi | Pecah per modul dengan konteks nyata                                |
| "Berapa ambang banjir yang bagus?"   | Ambang harus dari lapangan, bukan model                     | "Jelaskan metode menentukan ambang dari data historis TMA"          |
| "Buatkan hasil pengujian latensi"    | Fabrikasi data                                              | "Buatkan skrip untuk mengukur latensi end-to-end"                   |
| "Perbaiki proposal saya biar menang" | Melanggar disclosure & rubrik EPICS                         | "Periksa tata bahasa Inggris paragraf ini tanpa mengubah substansi" |
| "Sederhanakan logika alert ini"      | Bisa menghapus pengaman                                     | "Apa risiko keselamatan bila bagian ini disederhanakan?"            |

---

## 16. Log Penggunaan AI

Disimpan di repo sebagai `docs/AI-USAGE-LOG.md`, diisi **saat commit**, bukan diingat-ingat menjelang laporan.

### 16.1 Format

| Tanggal    | Anggota  | Artefak (ID)       | Tier | Alat AI | Cakupan bantuan                        | Yang ditulis manusia                 | Reviewer           | Commit    |
| ---------- | -------- | ------------------ | :--: | ------- | -------------------------------------- | ------------------------------------ | ------------------ | --------- |
| 2026-09-03 | Abdullah | A-03 Skema DB      |  T2  | Claude  | Draft DDL 8 tabel                      | Constraint, index, kebijakan retensi | Alifah             | `a3f91c2` |
| 2026-09-05 | Ghevira  | A-13 UI kartu node |  T1  | Copilot | Boilerplate komponen                   | Layout kiosk, warna level, teks ID   | Wahyu              | `7b02e11` |
| 2026-09-10 | Abdullah | A-08 Alert engine  |  T3  | Claude  | **Review saja** — mencari celah logika | Seluruh implementasi                 | Alifah + Dr. Indra | `c14d8a0` |

### 16.2 Rekap kuartalan

Setiap akhir kuartal, Project Leader membuat rekap:

- Jumlah commit AI-assisted vs manual
- Distribusi per tier
- Artefak T3/T4 — konfirmasi tidak ada pelanggaran
- Insiden (kredensial bocor, halusinasi lolos review, dll.) dan tindak lanjutnya

Rekap ini menjadi lampiran laporan EPICS 3 bulanan.

---

## 17. Penanganan Pelanggaran

| Tingkat          | Contoh                                                                                    | Tindakan                                                                                                                           |
| ---------------- | ----------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------- |
| **Ringan**       | Lupa mengisi trailer AI di commit; Log AI telat diisi                                     | Diperbaiki dalam 3 hari, diingatkan di rapat mingguan                                                                              |
| **Sedang**       | Commit kode yang tidak dipahami Human Owner; melewati review tier                         | PR di-revert; artefak ditinjau ulang dari awal; dicatat di rekap kuartalan                                                         |
| **Berat**        | Kredensial/data warga masuk prompt; migrasi AI dijalankan langsung di produksi            | Rotasi kredensial ≤ 24 jam; insiden dilaporkan ke Project Leader & pembimbing; audit dampak; catatan insiden masuk laporan EPICS   |
| **Sangat berat** | Fabrikasi angka hasil pengujian; naskah inti proposal ditulis AI; pemalsuan dokumen mitra | Dihentikan dari peran teknis; dilaporkan ke pembimbing & institusi; koreksi resmi ke EPICS in IEEE bila sudah terlanjur diserahkan |

**Prinsip:** melaporkan kesalahan sendiri lebih awal selalu diperlakukan lebih ringan daripada kesalahan yang ditemukan orang lain kemudian.

---

## 18. Peninjauan Kebijakan

- Ditinjau setiap **3 bulan**, bersamaan dengan laporan EPICS.
- Wajib ditinjau ulang bila: ada insiden berat, ada perubahan besar arsitektur, atau ada anggota tim baru/mahasiswa magang bergabung.
- Setiap anggota baru wajib membaca dan menandatangani dokumen ini sebelum mendapat akses repo.
- Perubahan kebijakan disetujui Project Leader + Technical Lead + Faculty Advisor.

---

## 19. Lampiran

### 19.1 Ringkasan satu halaman (tempel di grup tim)

```
✅ AI BOLEH:
   boilerplate kode · draft skema DB · komponen UI · dokumentasi
   test · debugging · terjemahan · penjelasan konsep · review kode kita
   perbandingan opsi arsitektur · data dummy & simulator

⚠️ AI BOLEH TAPI HARUS DIVERIFIKASI KETAT:
   skema & migrasi DB · kode backend · query SQL · konfigurasi keamanan
   analisis data · skrip DevOps · manual pengguna

🔶 AI HANYA UNTUK EKSPLORASI / REVIEW (implementasi oleh manusia):
   alert engine · firmware sensor & aktuator · rumus kalibrasi
   pesan WA ke warga · migrasi di database berisi data nyata

⛔ AI DILARANG:
   nilai ambang banjir · angka hasil pengujian · klaim keselamatan
   naskah inti proposal · anggaran & harga · data pribadi warga
   kredensial · surat resmi bertanda tangan

📌 ATURAN NOMOR SATU:
   Tidak paham = tidak masuk repo.
```

### 19.2 Formulir persetujuan anggota tim

```
Saya, ______________________, menyatakan telah membaca dan memahami
AI Usage Policy TCE FloodGuard v1.0, dan bersedia mematuhinya
selama keterlibatan saya dalam proyek ini.

Peran        : ______________________
Tanggal      : ______________________
Tanda tangan : ______________________
```

### 19.3 Referensi internal

| Dokumen        | Isi terkait                                                                                  |
| -------------- | -------------------------------------------------------------------------------------------- |
| PRD v1.0 §9    | Aturan alert engine yang tidak boleh dilonggarkan                                            |
| PRD v1.0 §11   | Modul AI sebagai fitur produk (berbeda dari dokumen ini)                                     |
| PRD v1.0 §16   | Keamanan & privasi data warga                                                                |
| PRD v1.0 §18.2 | 12 skenario uji wajib untuk alert engine                                                     |
| PRD v1.0 §22   | Open questions yang **tidak boleh** dijawab oleh AI (OQ-07 datum, OQ-03 otomatisasi Level 3) |

---

## Riwayat Revisi

| Versi | Tanggal     | Perubahan  | Penulis |
| ----- | ----------- | ---------- | ------- |
| 1.0   | 27 Agu 2026 | Draft awal | —       |
