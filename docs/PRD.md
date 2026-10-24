# Product Requirements Document (PRD)

## Flood Early Warning System (FEWS) — Web Platform

### Taman Cipulir Estate, RW 08, Kel. Cipadu Jaya, Kec. Larangan, Kota Tangerang

|                     |                                                                                                                           |
| ------------------- | ------------------------------------------------------------------------------------------------------------------------- |
| **Nama produk**     | TCE FloodGuard — Web Platform for Hybrid IoT Flood Early Warning System                                                   |
| **Versi dokumen**   | 1.0 (Draft)                                                                                                               |
| **Tanggal**         | 27 Agustus 2026                                                                                                           |
| **Status**          | For review                                                                                                                |
| **Proyek induk**    | EPICS in IEEE — "Hybrid IoT and AI-Based Flood Early Warning System Using Multi-Sensor Fusion in Urban Residential Areas" |
| **Mitra komunitas** | Pengurus RW 08 Taman Cipulir Estate                                                                                       |
| **Cakupan dokumen** | **Software layer saja** (firmware node dibahas hanya sebatas kontrak data/interface)                                      |

---

## Daftar Isi

1. [Ringkasan Eksekutif](#1-ringkasan-eksekutif)
2. [Latar Belakang & Problem Statement](#2-latar-belakang--problem-statement)
3. [Tujuan, Success Metrics, dan Non-Goals](#3-tujuan-success-metrics-dan-non-goals)
4. [Pengguna, Persona, dan Peran](#4-pengguna-persona-dan-peran)
5. [Arsitektur Sistem](#5-arsitektur-sistem)
6. [Kontrak Data & Protokol Komunikasi](#6-kontrak-data--protokol-komunikasi)
7. [Model Data / Skema Database](#7-model-data--skema-database)
8. [Pipeline Pengolahan Data & Multi-Sensor Fusion](#8-pipeline-pengolahan-data--multi-sensor-fusion)
9. [Alert Engine (Mesin Keputusan Peringatan)](#9-alert-engine-mesin-keputusan-peringatan)
10. [Modul Notifikasi (WhatsApp, Sirine, Telegram)](#10-modul-notifikasi-whatsapp-sirine-telegram)
11. [Modul AI / Prediksi](#11-modul-ai--prediksi)
12. [Kebutuhan Fungsional (Functional Requirements)](#12-kebutuhan-fungsional-functional-requirements)
13. [Spesifikasi UI/UX per Halaman](#13-spesifikasi-uiux-per-halaman)
14. [Spesifikasi API](#14-spesifikasi-api)
15. [Kebutuhan Non-Fungsional](#15-kebutuhan-non-fungsional)
16. [Keamanan, Privasi, dan Kepatuhan](#16-keamanan-privasi-dan-kepatuhan)
17. [Tech Stack & Infrastruktur](#17-tech-stack--infrastruktur)
18. [Rencana Pengujian & Kriteria Penerimaan](#18-rencana-pengujian--kriteria-penerimaan)
19. [Roadmap & Milestone (12 Bulan)](#19-roadmap--milestone-12-bulan)
20. [Risiko & Mitigasi](#20-risiko--mitigasi)
21. [Serah Terima, Operasional, dan Pemeliharaan](#21-serah-terima-operasional-dan-pemeliharaan)
22. [Open Questions / Keputusan yang Belum Diambil](#22-open-questions--keputusan-yang-belum-diambil)
23. [Lampiran](#23-lampiran)

---

## 1. Ringkasan Eksekutif

TCE FloodGuard adalah platform web yang menjadi "otak lunak" dari sistem peringatan dini banjir berbasis IoT di Taman Cipulir Estate. Perangkat keras (node ESP-32 + sensor + solar) mengirim data mentah; platform ini yang bertugas **menerima, membersihkan, menyimpan, menilai risiko, memutuskan tingkat peringatan, menyebarkan notifikasi, dan menampilkan situasi secara real-time**.

Empat kapabilitas inti:

1. **Ingestion & Storage** — menerima telemetri multi-node via MQTT, memvalidasi, menyimpan ke time-series database, dan tetap berjalan saat internet putus (edge-first).
2. **Decision Engine** — mengubah angka sensor mentah menjadi level siaga 1/2/3 yang stabil (anti false alarm) menggunakan filter EWMA, hysteresis, dan konfirmasi durasi.
3. **Notification** — WhatsApp blast bertarget per-RT, aktivasi sirine/LED lokal, log pengiriman yang dapat diaudit.
4. **Situational Awareness** — dashboard peta + grafik real-time untuk satpam dan pengurus RW, plus halaman publik untuk warga.

Target utama yang dijanjikan proposal dan harus dibuktikan oleh software: **latensi notifikasi rata-rata < 1 detik (target 0,561 s)**, **akurasi pembacaan level air ≈ 98,86%**, dan **error margin flow sensor < 10%** terhadap metode manual.

---

## 2. Latar Belakang & Problem Statement

### 2.1 Kondisi saat ini

- Kompleks seluas 13 ha, 1.300 jiwa, 11 RT — **RT 06–11 langganan banjir parah**.
- Kompleks berfungsi sebagai daerah tangkapan air kiriman dari Jl. Amal Blok, Pasar Cipadu, dan Kompleks Deplu.
- 11 pompa otomatis hanya mampu menangani ±1/3 volume air masuk.
- Pemantauan tinggi muka air **100% manual** lewat tiang berwarna → butuh orang datang dan melihat langsung.
- Informasi menyebar lewat mulut ke mulut → **jeda komunikasi** yang mematikan untuk evakuasi warga dan kendaraan.
- Satpam **tidak punya data kedalaman akurat** untuk menentukan kapan perahu karet aman diturunkan (perahu rusak kalau diturunkan di air terlalu dangkal).

### 2.2 Rumusan masalah software

> Data sensor tidak berguna kalau tidak sampai ke orang yang tepat, dalam bentuk yang bisa langsung ditindaklanjuti, sebelum airnya naik.

Maka software harus menjawab tiga pertanyaan operasional:

| Pertanyaan                                                        | Dijawab oleh                      |
| ----------------------------------------------------------------- | --------------------------------- |
| "Sekarang air setinggi apa, di titik mana, naik secepat apa?"     | Dashboard real-time + peta        |
| "Apakah ini sudah saatnya warga dikabari / perahu diturunkan?"    | Alert Engine + threshold berlapis |
| "Kapan pola ini pernah terjadi, dan apa yang bisa kita pelajari?" | Historical analytics + export     |

---

## 3. Tujuan, Success Metrics, dan Non-Goals

### 3.1 Tujuan produk

| ID   | Tujuan                                                                                        |
| ---- | --------------------------------------------------------------------------------------------- |
| G-01 | Menghilangkan jeda komunikasi antara naiknya air dan warga mengetahuinya                      |
| G-02 | Memberi satpam angka kedalaman & kecepatan arus yang presisi untuk keputusan penurunan perahu |
| G-03 | Menyediakan arsip data banjir yang bisa dipakai RW untuk advokasi ke BPBD / Pemkot            |
| G-04 | Sistem tetap berguna saat listrik dan internet mati (kondisi paling sering saat banjir)       |
| G-05 | Bisa dioperasikan mandiri oleh satpam & pengurus RW tanpa tim mahasiswa                       |

### 3.2 Success metrics

| ID   | Metrik                                                | Target                               | Cara ukur                                  |
| ---- | ----------------------------------------------------- | ------------------------------------ | ------------------------------------------ |
| M-01 | Latensi ambang-terlampaui → pesan WA terkirim         | rata-rata < 1 s (target 0,561 s)     | timestamp end-to-end di `alert_deliveries` |
| M-02 | Akurasi level air vs meteran manual                   | ≥ 98,86% (MAPE ≤ 1,14%)              | uji kalibrasi lapangan 30 titik            |
| M-03 | Error margin flow sensor vs metode pelampung          | < 10%                                | uji banding 20 sesi                        |
| M-04 | Uptime platform (edge server)                         | ≥ 99% per bulan                      | heartbeat monitor                          |
| M-05 | False alarm rate Level 3                              | ≤ 1 per 3 bulan                      | review log alert                           |
| M-06 | Missed detection (banjir terjadi tanpa alert Level 3) | 0                                    | cross-check laporan RW                     |
| M-07 | Cakupan penerima WA                                   | 100% perwakilan KK RT 06–11 + satpam | daftar `recipients` aktif                  |
| M-08 | Data loss saat internet putus 6 jam                   | 0 record                             | uji cabut WAN                              |

### 3.3 Non-goals (v1)

- ❌ Aplikasi mobile native (Android/iOS) — cukup WhatsApp + web responsive.
- ❌ Kontrol otomatis pompa (hanya monitoring status, tanpa aktuasi pompa).
- ❌ Prediksi banjir skala kota / integrasi model hidrologi DAS.
- ❌ Fitur sosial (chat antar warga, forum) — WhatsApp Group sudah ada.
- ❌ Billing / iuran warga.

---

## 4. Pengguna, Persona, dan Peran

### 4.1 Persona

**P1 — Pak Slamet, Satpam / Petugas Pos (operator utama)**
Shift malam, HP Android kelas menengah, ada monitor Raspberry Pi di pos. Butuh: satu layar yang kelihatan dari jauh, angka besar, warna jelas, tombol darurat manual. Tidak mau baca grafik rumit jam 2 pagi.

**P2 — Pak Rudiyanto, Ketua RW 08 (pengambil keputusan)**
Butuh: gambaran seluruh kompleks, rekap kejadian untuk rapat dan surat ke kelurahan/BPBD, kontrol siapa yang menerima notifikasi.

**P3 — Bu Ratna, Warga RT 09 (penerima)**
Hanya berinteraksi lewat WhatsApp. Butuh: pesan singkat, jelas, ada angka kedalaman, ada instruksi ("pindahkan kendaraan ke titik X"). Anti pesan spam.

**P4 — Tim Mahasiswa Budi Luhur (admin teknis)**
Butuh: manajemen device, kalibrasi, log debugging, akses raw data untuk skripsi/penelitian.

**P5 — Kru Perahu Karet / Satgas Bencana**
Butuh: kedalaman air per titik dengan indikator "aman/tidak aman untuk perahu", plus sirine lokal.

### 4.2 Matriks peran & hak akses (RBAC)

| Kapabilitas                    | Super Admin | Admin Teknis |      Pengurus RW       | Operator (Satpam)  |     Viewer/Warga      |
| ------------------------------ | :---------: | :----------: | :--------------------: | :----------------: | :-------------------: |
| Lihat dashboard real-time      |     ✅      |      ✅      |           ✅           |         ✅         | ✅ (publik, terbatas) |
| Lihat data historis & export   |     ✅      |      ✅      |           ✅           |         ✅         |          ❌           |
| Trigger alert manual           |     ✅      |      ✅      |           ✅           |         ✅         |          ❌           |
| Acknowledge / tutup alert      |     ✅      |      ✅      |           ✅           |         ✅         |          ❌           |
| Ubah ambang batas (threshold)  |     ✅      |      ✅      | ✅ (dengan konfirmasi) |         ❌         |          ❌           |
| Kelola daftar penerima WA      |     ✅      |      ✅      |           ✅           |         ❌         |          ❌           |
| Kalibrasi & konfigurasi device |     ✅      |      ✅      |           ❌           |         ❌         |          ❌           |
| Kelola pengguna & peran        |     ✅      |      ❌      |           ❌           |         ❌         |          ❌           |
| Bisukan sirine (silence)       |     ✅      |      ✅      |           ✅           | ✅ (maks 15 menit) |          ❌           |
| Lihat audit log                |     ✅      |      ✅      |           ✅           |         ❌         |          ❌           |

---

## 5. Arsitektur Sistem

### 5.1 Prinsip arsitektur

1. **Edge-first, cloud-second.** Otak pengambil keputusan ada di on-site server (Raspberry Pi 5). Cloud hanya untuk replikasi, akses jarak jauh, dan arsip. Kalau internet putus, sirine lokal dan dashboard pos **tetap jalan**.
2. **Store-and-forward di setiap lapis.** Node buffer saat MQTT down; edge buffer saat cloud down; notification queue buffer saat WA API down.
3. **Deterministic alerting.** Keputusan level siaga dibuat oleh aturan yang bisa dijelaskan (rule-based), bukan model AI. AI hanya memberi _prediksi tambahan_, tidak pernah menjadi satu-satunya pemicu Level 3.
4. **Fail-safe, bukan fail-silent.** Sensor mati = alarm ke operator, bukan diam-diam dianggap "aman".

### 5.2 Diagram lapisan

```
┌──────────────────────────────────────────────────────────────────────┐
│ LAYER 1 — FIELD NODES (di luar scope PRD, kontrak data di §6)        │
│  Node WL-xx : ESP-32 + HC-SR05/JSN-SR04T (level air)                 │
│  Node WF-xx : ESP-32 + Water Flow Hall-effect                        │
│  Node RN-xx : ESP-32 + Tipping Bucket + Anemometer                   │
│  Semua: Solar Panel → SCC → Baterai 12V → LM2596 → ESP-32            │
│  Output lokal: Relay → Buzzer/Speaker + LED Indicator                │
└───────────────┬──────────────────────────────────────────────────────┘
                │ MQTT over WiFi/LoRa-gateway (QoS 1, TLS)
┌───────────────▼──────────────────────────────────────────────────────┐
│ LAYER 2 — EDGE SERVER (Raspberry Pi 5 @ Pos Satpam)                  │
│  • Mosquitto MQTT Broker (auth per-device, ACL per-topic)            │
│  • Ingestion Service (validasi, EWMA, konversi unit)                 │
│  • Alert Engine (rule evaluation, hysteresis, dedup)                 │
│  • Local TimescaleDB/PostgreSQL (buffer 90 hari)                     │
│  • Local Web Dashboard (mode kiosk di monitor pos)                   │
│  • Notification Dispatcher (WA, sirine, Telegram fallback)           │
│  • Sync Agent → Cloud                                                │
│  • UPS/baterai cadangan minimal 6 jam                                │
└───────────────┬──────────────────────────────────────────────────────┘
                │ HTTPS (sync batch + realtime relay)
┌───────────────▼──────────────────────────────────────────────────────┐
│ LAYER 3 — CLOUD (VPS Hostinger / Azure)                              │
│  • API Gateway (REST + WebSocket)                                    │
│  • PostgreSQL + TimescaleDB (arsip 3 tahun)                          │
│  • Object storage (backup, export, laporan)                          │
│  • Web App (dashboard admin, halaman publik)                         │
│  • AI/Forecast worker (batch, Fase 2)                                │
└───────────────┬──────────────────────────────────────────────────────┘
                │
┌───────────────▼──────────────────────────────────────────────────────┐
│ LAYER 4 — KANAL KELUARAN                                             │
│  WhatsApp Cloud API / Gateway → grup & personal                      │
│  Telegram Bot (fallback) │ Sirine & LED lokal │ Web Dashboard         │
└──────────────────────────────────────────────────────────────────────┘
```

### 5.3 Mode operasi

| Mode              | Kondisi                             | Perilaku sistem                                                                                                                                                   |
| ----------------- | ----------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **NORMAL**        | Listrik + internet OK               | Semua kanal aktif, sync realtime ke cloud                                                                                                                         |
| **DEGRADED-NET**  | Internet putus, listrik/solar OK    | Alert engine + sirine + dashboard lokal jalan; WA masuk antrean; auto-flush saat online. Banner merah di dashboard: "Mode offline — WhatsApp tertunda"            |
| **DEGRADED-NODE** | ≥1 node tidak kirim data > 10 menit | Node ditandai STALE, dikeluarkan dari fusion, notifikasi ke admin teknis; level siaga dihitung dari node tersisa                                                  |
| **BLACKOUT**      | Edge server mati                    | Node tetap menyalakan buzzer lokal berbasis ambang yang di-hardcode di firmware (fail-safe terakhir); tiang ukur manual tetap dipertahankan sebagai backup visual |
| **MAINTENANCE**   | Diaktifkan admin                    | Notifikasi ditahan, data tetap direkam, badge kuning di dashboard                                                                                                 |

---

## 6. Kontrak Data & Protokol Komunikasi

### 6.1 Skema topik MQTT

```
tce/v1/{node_id}/telemetry     ← node publish data sensor      (QoS 1, retain false)
tce/v1/{node_id}/status        ← node publish heartbeat/health (QoS 1, retain true)
tce/v1/{node_id}/lwt           ← Last Will Testament           (QoS 1, retain true)
tce/v1/{node_id}/cmd           → server publish perintah       (QoS 1)
tce/v1/{node_id}/ack           ← node konfirmasi perintah      (QoS 1)
tce/v1/system/alert            → broadcast level siaga ke semua node (QoS 1, retain true)
```

`node_id` format: `{TIPE}-{RT}-{URUT}` → contoh `WL-09-01`, `RN-07-01`, `WF-06-02`.

### 6.2 Payload telemetri (node → server)

```json
{
    "node_id": "WL-09-01",
    "ts": "2026-08-27T14:03:11.482+07:00",
    "seq": 184521,
    "fw": "1.4.2",
    "readings": {
        "distance_cm": 132.4,
        "water_level_cm": 87.6,
        "flow_lpm": null,
        "flow_velocity_ms": null,
        "rainfall_mm": null,
        "rain_rate_mmh": null,
        "wind_speed_ms": null
    },
    "quality": {
        "sample_count": 10,
        "stdev": 0.42,
        "status": "OK"
    },
    "power": {
        "battery_v": 12.61,
        "solar_v": 17.2,
        "charging": true
    },
    "net": { "rssi": -68 },
    "buffered": false
}
```

**Aturan:**

- Field sensor yang tidak dimiliki node dikirim `null` (bukan `0`) — `0` adalah nilai valid.
- `seq` naik monoton per node; dipakai mendeteksi gap dan duplikat.
- `buffered: true` menandai data hasil replay dari memori node saat koneksi pulih → tidak boleh memicu alert baru, hanya masuk arsip.
- `quality.status` ∈ `OK | NOISY | OUT_OF_RANGE | SENSOR_FAULT`.
- Ukuran payload dijaga < 512 byte.

### 6.3 Payload status/heartbeat

```json
{
    "node_id": "WL-09-01",
    "ts": "2026-08-27T14:03:00+07:00",
    "uptime_s": 918233,
    "free_heap": 142880,
    "rssi": -68,
    "battery_v": 12.61,
    "reboot_reason": "POWERON",
    "buffer_depth": 0
}
```

Interval heartbeat: **60 detik**. Node dianggap `STALE` setelah **3× interval (180 s)** tanpa heartbeat, `OFFLINE` setelah **10 menit**.

### 6.4 Perintah server → node

| Command         | Payload                                                | Fungsi                                                    |
| --------------- | ------------------------------------------------------ | --------------------------------------------------------- |
| `SET_INTERVAL`  | `{"telemetry_s": 5}`                                   | Ubah frekuensi kirim (normal 30 s, siaga 5 s, kritis 1 s) |
| `SET_THRESHOLD` | `{"l1":100,"l2":150}`                                  | Update ambang fail-safe lokal di firmware                 |
| `BUZZER`        | `{"state":"ON","pattern":"CRITICAL","duration_s":120}` | Aktifkan sirine                                           |
| `LED`           | `{"color":"RED","mode":"BLINK"}`                       | Indikator visual                                          |
| `CALIBRATE`     | `{"mount_height_cm":220,"offset_cm":-1.8}`             | Set parameter kalibrasi                                   |
| `REBOOT`        | `{}`                                                   | Restart node                                              |
| `PING`          | `{}`                                                   | Uji konektivitas & ukur RTT                               |

Setiap perintah wajib dibalas `ack` dengan `cmd_id` dalam ≤ 3 detik; kalau tidak, dispatcher retry maks 3× lalu tandai `COMMAND_FAILED`.

### 6.5 Adaptive sampling

| Kondisi                        | Interval telemetri       | Alasan                       |
| ------------------------------ | ------------------------ | ---------------------------- |
| Level NORMAL, tidak hujan      | 60 s                     | hemat daya baterai/solar     |
| Level NORMAL, hujan terdeteksi | 15 s                     | pantauan naik                |
| SIAGA (Level 2)                | 5 s                      | butuh resolusi laju kenaikan |
| KRITIS (Level 3)               | 1 s                      | butuh latensi minimum        |
| Baterai < 11,4 V               | 120 s + matikan LED idle | mode hemat daya              |

---

## 7. Model Data / Skema Database

### 7.1 Tabel inti

```sql
-- Lokasi / titik pantau
CREATE TABLE sites (
  id            SERIAL PRIMARY KEY,
  code          TEXT UNIQUE NOT NULL,        -- 'TCE'
  name          TEXT NOT NULL,
  address       TEXT,
  timezone      TEXT DEFAULT 'Asia/Jakarta'
);

CREATE TABLE zones (                          -- RT
  id            SERIAL PRIMARY KEY,
  site_id       INT REFERENCES sites(id),
  code          TEXT NOT NULL,               -- 'RT-09'
  name          TEXT,
  household_cnt INT,
  flood_prone   BOOLEAN DEFAULT TRUE
);

CREATE TABLE nodes (
  id                SERIAL PRIMARY KEY,
  node_id           TEXT UNIQUE NOT NULL,     -- 'WL-09-01'
  site_id           INT REFERENCES sites(id),
  zone_id           INT REFERENCES zones(id),
  node_type         TEXT NOT NULL,            -- WATER_LEVEL|FLOW|RAINFALL|GATEWAY
  label             TEXT,                     -- 'Gorong-gorong depan Masjid'
  latitude          NUMERIC(10,7),
  longitude         NUMERIC(10,7),
  mount_height_cm   NUMERIC(7,2),             -- jarak sensor ke dasar saluran
  ground_elev_cm    NUMERIC(7,2),             -- elevasi relatif thd datum kompleks
  calib_offset_cm   NUMERIC(7,2) DEFAULT 0,
  min_valid_cm      NUMERIC(7,2),
  max_valid_cm      NUMERIC(7,2),
  fusion_weight     NUMERIC(4,3) DEFAULT 1.0,
  is_critical       BOOLEAN DEFAULT FALSE,    -- node penentu utama alert
  status            TEXT DEFAULT 'ACTIVE',    -- ACTIVE|STALE|OFFLINE|MAINTENANCE|RETIRED
  firmware_version  TEXT,
  installed_at      TIMESTAMPTZ,
  last_seen_at      TIMESTAMPTZ
);

-- Time-series (hypertable TimescaleDB)
CREATE TABLE readings (
  ts                TIMESTAMPTZ NOT NULL,
  node_id           TEXT NOT NULL REFERENCES nodes(node_id),
  seq               BIGINT,
  water_level_cm    NUMERIC(7,2),
  water_level_ewma  NUMERIC(7,2),
  rise_rate_cmh     NUMERIC(7,2),
  flow_lpm          NUMERIC(8,2),
  flow_velocity_ms  NUMERIC(6,3),
  rainfall_mm       NUMERIC(6,2),
  rain_rate_mmh     NUMERIC(6,2),
  wind_speed_ms     NUMERIC(6,2),
  battery_v         NUMERIC(5,2),
  solar_v           NUMERIC(5,2),
  rssi              SMALLINT,
  quality           TEXT,
  is_buffered       BOOLEAN DEFAULT FALSE,
  PRIMARY KEY (node_id, ts)
);
SELECT create_hypertable('readings','ts');

CREATE TABLE node_health (
  ts            TIMESTAMPTZ NOT NULL,
  node_id       TEXT NOT NULL,
  uptime_s      BIGINT,
  free_heap     INT,
  reboot_reason TEXT,
  buffer_depth  INT,
  PRIMARY KEY (node_id, ts)
);

-- Ambang batas (versioned)
CREATE TABLE thresholds (
  id                SERIAL PRIMARY KEY,
  scope             TEXT NOT NULL,           -- GLOBAL|ZONE|NODE
  scope_ref         TEXT,
  level             SMALLINT NOT NULL,       -- 1|2|3
  metric            TEXT NOT NULL,           -- water_level_cm|rise_rate_cmh|rain_rate_mmh|flow_velocity_ms
  operator          TEXT NOT NULL,           -- GT|GTE|LT|LTE|BETWEEN
  value_low         NUMERIC(8,2),
  value_high        NUMERIC(8,2),
  sustain_seconds   INT DEFAULT 30,          -- harus bertahan sekian detik
  hysteresis_cm     NUMERIC(6,2) DEFAULT 10, -- syarat turun level
  cooldown_minutes  INT DEFAULT 15,
  is_active         BOOLEAN DEFAULT TRUE,
  effective_from    TIMESTAMPTZ DEFAULT now(),
  created_by        INT REFERENCES users(id)
);

-- Kejadian alert
CREATE TABLE alerts (
  id                BIGSERIAL PRIMARY KEY,
  site_id           INT,
  zone_id           INT,
  level             SMALLINT NOT NULL,
  trigger_type      TEXT NOT NULL,           -- AUTO_THRESHOLD|AUTO_RISE_RATE|AI_FORECAST|MANUAL
  trigger_node_id   TEXT,
  trigger_value     NUMERIC(8,2),
  trigger_metric    TEXT,
  snapshot          JSONB,                   -- kondisi semua node saat trigger
  message_text      TEXT,
  started_at        TIMESTAMPTZ NOT NULL,
  escalated_from    BIGINT REFERENCES alerts(id),
  acknowledged_at   TIMESTAMPTZ,
  acknowledged_by   INT REFERENCES users(id),
  resolved_at       TIMESTAMPTZ,
  resolution_note   TEXT,
  is_false_alarm    BOOLEAN DEFAULT FALSE
);

-- Penerima notifikasi
CREATE TABLE recipients (
  id            SERIAL PRIMARY KEY,
  full_name     TEXT NOT NULL,
  phone_e164    TEXT NOT NULL UNIQUE,        -- +62812xxxx
  zone_id       INT REFERENCES zones(id),
  role_tag      TEXT,                        -- WARGA|KETUA_RT|SATPAM|SATGAS|PENGURUS_RW
  min_level     SMALLINT DEFAULT 2,          -- level minimal yang diterima
  channel       TEXT DEFAULT 'WHATSAPP',
  consent_at    TIMESTAMPTZ,                 -- bukti persetujuan
  is_active     BOOLEAN DEFAULT TRUE
);

CREATE TABLE recipient_groups (
  id            SERIAL PRIMARY KEY,
  name          TEXT,                        -- 'Grup Warga RT 09'
  wa_group_id   TEXT,
  min_level     SMALLINT DEFAULT 3,
  is_active     BOOLEAN DEFAULT TRUE
);

-- Log pengiriman (untuk membuktikan KPI latensi)
CREATE TABLE alert_deliveries (
  id                BIGSERIAL PRIMARY KEY,
  alert_id          BIGINT REFERENCES alerts(id),
  channel           TEXT,                    -- WHATSAPP|TELEGRAM|SIREN|LED|WEB_PUSH
  target            TEXT,
  queued_at         TIMESTAMPTZ,
  sent_at           TIMESTAMPTZ,
  delivered_at      TIMESTAMPTZ,
  read_at           TIMESTAMPTZ,
  latency_ms        INT,                     -- trigger_at → sent_at
  status            TEXT,                    -- QUEUED|SENT|DELIVERED|READ|FAILED
  error_code        TEXT,
  retry_count       SMALLINT DEFAULT 0
);

CREATE TABLE users (
  id            SERIAL PRIMARY KEY,
  username      TEXT UNIQUE NOT NULL,
  full_name     TEXT,
  password_hash TEXT NOT NULL,
  role          TEXT NOT NULL,               -- SUPER_ADMIN|TECH_ADMIN|RW_BOARD|OPERATOR|VIEWER
  phone_e164    TEXT,
  is_active     BOOLEAN DEFAULT TRUE,
  last_login_at TIMESTAMPTZ
);

CREATE TABLE audit_logs (
  id            BIGSERIAL PRIMARY KEY,
  ts            TIMESTAMPTZ DEFAULT now(),
  user_id       INT REFERENCES users(id),
  action        TEXT,                        -- LOGIN|THRESHOLD_UPDATE|MANUAL_ALERT|SILENCE|CALIBRATE|...
  entity        TEXT,
  entity_id     TEXT,
  before_value  JSONB,
  after_value   JSONB,
  ip_address    INET
);

CREATE TABLE manual_observations (           -- untuk validasi akurasi sensor
  id            SERIAL PRIMARY KEY,
  ts            TIMESTAMPTZ,
  node_id       TEXT,
  manual_cm     NUMERIC(7,2),
  sensor_cm     NUMERIC(7,2),
  error_pct     NUMERIC(6,3),
  observer      TEXT,
  photo_url     TEXT,
  note          TEXT
);
```

### 7.2 Retensi & agregasi

| Data                          | Resolusi              | Retensi edge | Retensi cloud |
| ----------------------------- | --------------------- | ------------ | ------------- |
| `readings` raw                | per-sample            | 90 hari      | 12 bulan      |
| Continuous aggregate 1 menit  | avg/min/max           | 90 hari      | 3 tahun       |
| Continuous aggregate 1 jam    | avg/min/max/sum hujan | —            | selamanya     |
| `alerts` + `alert_deliveries` | per event             | selamanya    | selamanya     |
| `audit_logs`                  | per event             | 12 bulan     | 3 tahun       |

---

## 8. Pipeline Pengolahan Data & Multi-Sensor Fusion

### 8.1 Tahapan pipeline

```
MQTT msg
  │
  ├─▶ [1] SCHEMA VALIDATION  — payload sesuai kontrak? Kalau tidak → dead-letter queue
  ├─▶ [2] AUTH & IDENTITY    — node_id terdaftar & aktif?
  ├─▶ [3] DEDUP              — (node_id, seq) sudah pernah masuk? drop
  ├─▶ [4] RANGE CHECK        — nilai di antara min_valid_cm..max_valid_cm?
  │                            di luar → quality=OUT_OF_RANGE, tidak masuk fusion
  ├─▶ [5] SPIKE REJECTION    — |x(t) − x_ewma(t−1)| > 3σ dan tidak berulang 2× → drop
  ├─▶ [6] UNIT CONVERSION    — water_level = mount_height − distance + calib_offset
  ├─▶ [7] EWMA SMOOTHING     — x_ewma(t) = α·x(t) + (1−α)·x_ewma(t−1)
  ├─▶ [8] DERIVED METRICS    — rise_rate_cmh, rain accumulation 10/30/60 menit
  ├─▶ [9] PERSIST            — insert ke readings (edge) + queue sync ke cloud
  ├─▶ [10] FUSION            — hitung Flood Risk Index level-kompleks & per-RT
  ├─▶ [11] ALERT EVALUATION  — lihat §9
  └─▶ [12] BROADCAST         — push WebSocket ke semua dashboard aktif
```

### 8.2 Parameter EWMA

| Metrik             | α    | Alasan                                                |
| ------------------ | ---- | ----------------------------------------------------- |
| `water_level_cm`   | 0,30 | responsif tapi tetap halus (≈ time constant 3 sampel) |
| `flow_velocity_ms` | 0,20 | sinyal Hall-effect lebih berisik                      |
| `rain_rate_mmh`    | 0,50 | tipping bucket sudah diskrit, butuh cepat             |
| `wind_speed_ms`    | 0,25 | tidak dipakai untuk alert, hanya konteks              |

> α wajib bisa diubah dari halaman Settings tanpa deploy ulang, dan setiap perubahan dicatat di `audit_logs`.

### 8.3 Multi-sensor fusion → Flood Risk Index (FRI)

FRI adalah skor 0–100 per zona (RT), dihitung tiap siklus evaluasi:

```
FRI = 100 × ( w_level·S_level + w_rise·S_rise + w_rain·S_rain + w_flow·S_flow + w_upstream·S_upstream )

Default bobot:
  w_level    = 0,40   ← tinggi muka air titik terdekat (ternormalisasi thd ambang L3)
  w_rise     = 0,25   ← laju kenaikan (cm/jam), normalisasi 0..40 cm/jam
  w_rain     = 0,15   ← intensitas hujan 30 menit terakhir, normalisasi 0..50 mm/jam
  w_flow     = 0,10   ← kecepatan arus, normalisasi 0..2 m/s
  w_upstream = 0,10   ← level node hulu (kiriman Pasar Cipadu / Jl. Amal / Deplu)

Setiap S_x = clamp(nilai / nilai_referensi, 0, 1)
```

**Aturan degradasi:** kalau salah satu sensor `STALE`/`OUT_OF_RANGE`, bobotnya diredistribusi proporsional ke sensor yang tersisa, dan FRI diberi flag `degraded: true` yang ditampilkan sebagai ikon peringatan di dashboard.

**Penting:** FRI adalah alat bantu visual dan input prioritas. **Pemicu resmi Level 3 tetap ambang tinggi muka air**, bukan FRI, supaya keputusan bisa dijelaskan ke warga dan tidak menimbulkan sengketa.

---

## 9. Alert Engine (Mesin Keputusan Peringatan)

### 9.1 Definisi level

| Level | Nama    | Kondisi utama                                 | Kanal keluaran                                              | Penerima                  |
| :---: | ------- | --------------------------------------------- | ----------------------------------------------------------- | ------------------------- |
| **0** | AMAN    | TMA < 70 cm & tidak hujan                     | dashboard hijau                                             | —                         |
| **1** | WASPADA | TMA < 100 cm **atau** hujan ringan berlanjut  | dashboard kuning, log senyap, LED kuning                    | Operator (dashboard saja) |
| **2** | SIAGA   | TMA 100–150 cm **atau** laju naik ≥ 20 cm/jam | WA ke pengurus RW + ketua RT + satgas, LED oranye           | Pengurus & satgas         |
| **3** | KRITIS  | TMA > 150 cm **atau** laju naik ≥ 40 cm/jam   | **WA blast semua grup warga + sirine + LED merah berkedip** | Semua                     |

### 9.2 Aturan anti false alarm (wajib)

| Mekanisme                    | Aturan default                                                                                               | Tujuan                                               |
| ---------------------------- | ------------------------------------------------------------------------------------------------------------ | ---------------------------------------------------- |
| **Sustain / konfirmasi**     | Ambang harus terlampaui **≥ 30 detik berturut-turut** (min. 3 sampel valid)                                  | menolak riak/gelombang sesaat                        |
| **Hysteresis**               | Turun level hanya bila TMA turun **≥ 10 cm** di bawah ambang selama **≥ 5 menit**                            | mencegah alert berkedip-kedip di sekitar ambang      |
| **Multi-node corroboration** | Level 3 butuh konfirmasi **≥ 2 node** dalam zona yang sama, kecuali node berstatus `is_critical`             | menolak satu sensor rusak memicu panik               |
| **Cooldown**                 | Setelah kirim, tidak ada blast level sama selama **15 menit** kecuali ada eskalasi level                     | menghindari spam                                     |
| **Dedup**                    | Alert aktif yang belum resolved tidak membuat alert baru; hanya update                                       | 1 kejadian = 1 tiket                                 |
| **Buffered data**            | Data dengan `buffered:true` **tidak pernah** memicu alert                                                    | menghindari alert dari data lama saat node reconnect |
| **Quiet hours**              | Tidak berlaku untuk Level 3 (banjir tidak kenal jam); Level 1–2 ditahan 23.00–04.00 kecuali laju naik tinggi | menghormati istirahat warga                          |
| **Maintenance mode**         | Semua notifikasi eksternal ditahan                                                                           | supaya uji coba tidak bikin geger                    |

### 9.3 Pseudocode evaluasi

```python
def evaluate(zone, now):
    nodes = active_nodes(zone)               # exclude STALE/OFFLINE/MAINTENANCE
    if len(nodes) == 0:
        return raise_system_alert("NO_DATA", zone)   # ke operator, bukan warga

    candidate = LEVEL_0
    for rule in active_thresholds(zone):
        matched = [n for n in nodes if rule.matches(n.latest_ewma)]
        if not matched:
            continue
        if not sustained(rule, matched, seconds=rule.sustain_seconds):
            continue
        if rule.level == 3 and len(matched) < 2 and not any(n.is_critical for n in matched):
            log("level3_awaiting_corroboration", zone); continue
        candidate = max(candidate, rule.level)

    current = zone.current_level

    # eskalasi: langsung, tanpa penundaan
    if candidate > current:
        return open_or_escalate_alert(zone, candidate)

    # de-eskalasi: butuh hysteresis + durasi
    if candidate < current:
        if below_by_hysteresis(zone, current, margin_cm=10, minutes=5):
            return downgrade_alert(zone, candidate)

    return None    # tidak ada perubahan
```

### 9.4 Siklus hidup alert

```
TRIGGERED ──▶ NOTIFYING ──▶ ACTIVE ──▶ ACKNOWLEDGED ──▶ RESOLVED
     │                         │              │
     │                         └──▶ ESCALATED ─┘
     └──▶ CANCELLED (dibatalkan operator dalam 60 detik pertama = "batal kirim")
```

- **Grace window 60 detik**: setelah Level 3 terpicu, operator melihat countdown di dashboard dan bisa membatalkan **sebelum** blast ke grup warga terkirim. Notifikasi ke satgas & sirine tetap langsung jalan. _(Konfigurable; bisa di-set 0 detik kalau RW ingin sepenuhnya otomatis — lihat Open Question OQ-03.)_
- **Auto-resolve**: alert tertutup otomatis bila level kembali ke 0 dan bertahan 30 menit, dengan pesan "Air surut" ke penerima yang sama.
- **Manual resolve**: operator wajib mengisi catatan singkat (dropdown: banjir nyata / hujan lewat / sensor error / uji coba).

### 9.5 Alert sistem (non-banjir)

| Kode                | Pemicu                             | Penerima                | Prioritas  |
| ------------------- | ---------------------------------- | ----------------------- | ---------- |
| `SYS-NODE-OFFLINE`  | node tidak heartbeat > 10 menit    | Admin teknis + operator | Sedang     |
| `SYS-BATT-LOW`      | battery_v < 11,4 V selama 30 menit | Admin teknis            | Rendah     |
| `SYS-SENSOR-FAULT`  | quality=SENSOR_FAULT 5× berturut   | Admin teknis + operator | Tinggi     |
| `SYS-NO-DATA-ZONE`  | seluruh node satu zona offline     | Operator + pengurus RW  | **Kritis** |
| `SYS-WA-FAILED`     | > 20% pengiriman gagal             | Admin teknis            | Tinggi     |
| `SYS-INTERNET-DOWN` | sync ke cloud gagal 10 menit       | Operator (banner)       | Sedang     |

---

## 10. Modul Notifikasi (WhatsApp, Sirine, Telegram)

### 10.1 Kanal & prioritas

| Prioritas | Kanal                                 | Latensi target | Catatan                              |
| --------- | ------------------------------------- | -------------- | ------------------------------------ |
| 1         | Sirine + LED lokal (via MQTT ke node) | < 200 ms       | jalur tercepat, tidak butuh internet |
| 2         | WhatsApp (satgas & pengurus)          | < 1 s          | pesan personal                       |
| 3         | WhatsApp blast grup warga             | < 1 s          | hanya Level 3                        |
| 4         | Telegram bot                          | < 2 s          | fallback bila WA gagal               |
| 5         | Web push / dashboard banner           | < 500 ms       | untuk yang sedang buka dashboard     |

### 10.2 Template pesan

**Level 2 — SIAGA (ke pengurus/RT/satgas)**

```
⚠️ SIAGA BANJIR — Taman Cipulir Estate
Waktu   : 27 Agu 2026, 14.03 WIB
Lokasi  : {nama_titik} (RT {rt})
Tinggi air : {tma} cm (naik {laju} cm/jam)
Curah hujan: {hujan} mm/jam

Mohon pantau situasi & siapkan tim.
Detail: {url_dashboard}
```

**Level 3 — KRITIS (blast ke grup warga)**

```
🚨 PERINGATAN BANJIR — LEVEL KRITIS
Taman Cipulir Estate | 27 Agu 2026, 14.03 WIB

Tinggi air {tma} cm di {nama_titik} (RT {rt})
Kenaikan {laju} cm/jam — arus {arus} m/s

TINDAKAN:
1. Pindahkan kendaraan ke {titik_evakuasi}
2. Amankan barang & aliran listrik rumah
3. Prioritaskan lansia, anak, dan warga sakit
4. Ikuti arahan petugas / satgas

Perahu karet: {status_perahu}
Info: {url_publik}

Sistem otomatis TCE FloodGuard — jangan balas pesan ini.
```

**Air surut**

```
✅ Air surut — Taman Cipulir Estate
Tinggi air turun ke {tma} cm pada {jam} WIB.
Status kembali {level_nama}. Tetap waspada jika hujan berlanjut.
```

### 10.3 Aturan operasional notifikasi

- **Opt-in wajib.** Nomor hanya masuk `recipients` setelah warga mendaftar (form/QR saat sosialisasi) dan `consent_at` terisi. Ada instruksi berhenti (`STOP`) yang menonaktifkan nomor dan tercatat.
- **Segmentasi per RT.** Warga RT 09 tidak perlu diteror kabar RT 06. Level 3 kompleks-wide dikirim ke semua; Level 3 zona hanya ke zona terkait + pengurus.
- **Rate limiting & antrean.** Dispatcher pakai queue (BullMQ/Redis) dengan concurrency terbatas agar tidak kena rate limit WA API. Prioritas: SATGAS > KETUA_RT > PENGURUS > WARGA.
- **Retry**: 3× dengan backoff 2 s / 10 s / 60 s; setelah itu fallback Telegram; setelah itu tandai `FAILED` dan tampilkan di dashboard.
- **Semua pengiriman dicatat** di `alert_deliveries` lengkap dengan `latency_ms` — inilah bukti KPI M-01 untuk laporan EPICS 3 bulanan.
- **Kill switch**: tombol "Hentikan semua notifikasi" untuk super admin bila terjadi loop/spam, dengan alasan wajib diisi.

### 10.4 Status perahu karet (fitur khusus P5)

Halaman/kartu khusus menghitung `boat_status` per titik akses:

| Kondisi TMA di titik            | Status                                               | Warna  |
| ------------------------------- | ---------------------------------------------------- | ------ |
| < 40 cm                         | **JANGAN TURUNKAN** — risiko lambung/propeller rusak | merah  |
| 40–60 cm                        | **HATI-HATI** — cukup untuk perahu kosong            | kuning |
| > 60 cm & arus < 1,5 m/s        | **AMAN DITURUNKAN**                                  | hijau  |
| arus ≥ 1,5 m/s (berapa pun TMA) | **BAHAYA ARUS**                                      | merah  |

Ambang ini dapat dikonfigurasi dan harus divalidasi bersama satgas saat pelatihan bulan 10–12.

---

## 11. Modul AI / Prediksi

> **Catatan penting untuk tim:** judul proposal menyebut "AI-Based", tetapi deskripsi teknis di proposal hanya berisi filter EWMA + rule threshold — itu _signal processing_, bukan AI. Bagian ini menutup celah tersebut agar klaim di proposal terpenuhi. Modul ini **Fase 2** dan tidak boleh menjadi blocker rilis Fase 1.

### 11.1 AI-01 — Nowcasting tinggi muka air (30–60 menit ke depan)

- **Input**: TMA 6 jam terakhir (multi-node), curah hujan 6 jam, laju kenaikan, kecepatan arus, level node hulu, jam & musim.
- **Model**: baseline **Gradient Boosting (XGBoost/LightGBM)** untuk horizon 30 & 60 menit; upgrade ke **LSTM/GRU** setelah ≥ 1 musim hujan data terkumpul.
- **Output**: `predicted_level_cm` + interval keyakinan, ditampilkan sebagai garis putus-putus di grafik dashboard.
- **Peran dalam alerting**: hanya menaikkan level **maksimal ke Level 2 (SIAGA)** dengan label eksplisit "PREDIKSI". **Tidak pernah** memicu Level 3 sendirian.
- **Metrik**: MAE ≤ 8 cm untuk horizon 30 menit; dievaluasi mingguan, dilaporkan di halaman Model Performance.

### 11.2 AI-02 — Deteksi anomali sensor

- **Model**: Isolation Forest / z-score adaptif per node.
- **Fungsi**: mendeteksi sensor yang "diam" (nilai konstan mencurigakan), drift, atau lompatan tidak wajar, lalu menurunkan `fusion_weight` otomatis dan memunculkan `SYS-SENSOR-FAULT`.
- Ini bernilai tinggi dan **murah** — direkomendasikan masuk lebih awal (bulan 8–9).

### 11.3 AI-03 — Klasifikasi pola kejadian (analitik pasca-kejadian)

Clustering kejadian banjir historis (durasi, puncak, korelasi hujan) untuk laporan RW ke BPBD. Batch, bukan real-time.

### 11.4 Guardrail

- Semua output AI diberi label jelas di UI: **"Prediksi — bukan pengukuran"**.
- Model dan versinya tercatat (`model_registry`), setiap prediksi menyimpan `model_version` agar bisa diaudit.
- Ada tombol untuk menonaktifkan modul AI sepenuhnya tanpa mengganggu alert rule-based.

---

## 12. Kebutuhan Fungsional (Functional Requirements)

Prioritas: **P0** = wajib rilis 1, **P1** = penting, **P2** = nice-to-have.

### 12.1 Ingestion & Device Management

| ID     | Requirement                                                             | Prioritas | Acceptance Criteria                                                       |
| ------ | ----------------------------------------------------------------------- | :-------: | ------------------------------------------------------------------------- |
| FR-101 | Sistem menerima telemetri MQTT dari ≥ 20 node bersamaan                 |    P0     | Load test 20 node × 1 msg/detik selama 1 jam, 0 message loss              |
| FR-102 | Payload tidak valid masuk dead-letter queue, tidak crash service        |    P0     | Kirim 100 payload rusak → service tetap up, semua tercatat                |
| FR-103 | Deteksi duplikat berbasis `(node_id, seq)`                              |    P0     | Kirim ulang 50 pesan → 0 baris duplikat di DB                             |
| FR-104 | Node offline terdeteksi ≤ 10 menit dan status berubah otomatis          |    P0     | Matikan node → status `OFFLINE` dalam ≤ 10 menit + alert sistem           |
| FR-105 | CRUD node: tambah, edit, nonaktifkan, pensiunkan                        |    P0     | Admin bisa menambah node baru dan langsung menerima datanya tanpa restart |
| FR-106 | Form kalibrasi: mount height, offset, batas valid, bobot fusion         |    P0     | Ubah offset → nilai TMA berubah sesuai rumus di reading berikutnya        |
| FR-107 | Kirim perintah ke node (buzzer, LED, interval, reboot, ping) dengan ack |    P0     | Tekan "Uji Sirine" → buzzer bunyi ≤ 3 s, ack tercatat                     |
| FR-108 | Buffer lokal saat DB/cloud tidak tersedia, auto-flush saat pulih        |    P0     | Cabut WAN 6 jam → 0 data hilang setelah reconnect                         |
| FR-109 | OTA firmware update via dashboard                                       |    P2     | Upload .bin → node update & lapor versi baru                              |
| FR-110 | Halaman peta penempatan node dengan status warna                        |    P1     | 20 node tampil sesuai koordinat, warna sesuai status                      |

### 12.2 Monitoring & Visualisasi

| ID     | Requirement                                                        | Prioritas | Acceptance Criteria                                                             |
| ------ | ------------------------------------------------------------------ | :-------: | ------------------------------------------------------------------------------- |
| FR-201 | Dashboard real-time update ≤ 2 detik tanpa refresh manual          |    P0     | Ubah nilai sensor → angka di layar berubah ≤ 2 s (WebSocket)                    |
| FR-202 | Kartu status per titik: TMA, laju, arus, hujan, baterai, last seen |    P0     | Semua field tampil; `last seen` relatif ("12 detik lalu")                       |
| FR-203 | Peta kompleks dengan marker node & warna level                     |    P0     | Klik marker → panel detail node                                                 |
| FR-204 | Grafik time-series dengan rentang 1 j / 6 j / 24 j / 7 h / custom  |    P0     | Ganti rentang → data termuat ≤ 3 s untuk 24 jam                                 |
| FR-205 | Overlay garis ambang L1/L2/L3 pada grafik                          |    P0     | Garis putus-putus tampil sesuai threshold aktif                                 |
| FR-206 | Mode Kiosk: layar penuh, font besar, auto-reconnect                |    P0     | Jalan 24 jam tanpa intervensi; auto-reload saat koneksi pulih                   |
| FR-207 | Indikator kualitas data (degraded/stale) yang jelas                |    P0     | Node stale → nilai abu-abu + ikon peringatan, bukan nilai lama yang menyesatkan |
| FR-208 | Halaman publik read-only tanpa login untuk warga                   |    P1     | Akses via URL/QR, hanya menampilkan level & TMA agregat                         |
| FR-209 | Perbandingan multi-node dalam satu grafik                          |    P1     | Pilih ≤ 5 node → tergambar dalam 1 chart                                        |
| FR-210 | Export CSV/XLSX untuk rentang & node terpilih                      |    P1     | Export 30 hari data → file terunduh ≤ 30 s                                      |
| FR-211 | Laporan PDF bulanan otomatis (kejadian, grafik, KPI)               |    P2     | Tersedia tiap tanggal 1, bisa diunduh pengurus RW                               |

### 12.3 Alerting

| ID     | Requirement                                                  | Prioritas | Acceptance Criteria                                                          |
| ------ | ------------------------------------------------------------ | :-------: | ---------------------------------------------------------------------------- |
| FR-301 | Evaluasi ambang tiap reading masuk, latensi proses < 100 ms  |    P0     | Profiling: p95 waktu evaluasi < 100 ms                                       |
| FR-302 | Sustain check sebelum eskalasi                               |    P0     | Spike 1 sampel → tidak ada alert; 3 sampel berturut → alert                  |
| FR-303 | Hysteresis pada de-eskalasi                                  |    P0     | Osilasi ±5 cm di sekitar ambang → level tidak berubah-ubah                   |
| FR-304 | Multi-node corroboration untuk Level 3                       |    P0     | 1 node non-critical melewati L3 → tidak blast; 2 node → blast                |
| FR-305 | Trigger manual Level 1/2/3 oleh operator                     |    P0     | Tombol + konfirmasi 2 langkah + alasan wajib → alert terkirim                |
| FR-306 | Grace window pembatalan Level 3 (default 60 s, konfigurable) |    P1     | Countdown tampil; klik batal → blast warga tidak terkirim; log tercatat      |
| FR-307 | Acknowledge & resolve alert dengan catatan                   |    P0     | Alert tidak bisa ditutup tanpa memilih alasan                                |
| FR-308 | Editor threshold dengan versioning & audit                   |    P0     | Ubah ambang → versi lama tersimpan, tercatat siapa & kapan                   |
| FR-309 | Simulasi/dry-run threshold terhadap data historis            |    P1     | Pilih ambang baru + rentang tanggal → tampil berapa alert yang akan tercipta |
| FR-310 | Mode maintenance (tahan notifikasi eksternal)                |    P0     | Aktifkan → uji coba tidak mengirim WA, banner kuning muncul                  |
| FR-311 | Alert sistem (node offline, baterai lemah, sensor fault)     |    P0     | Semua kode di §9.5 terpicu sesuai kondisi                                    |
| FR-312 | Silence sirine sementara (maks 15 menit untuk operator)      |    P1     | Sirine mati, otomatis aktif lagi setelah timer atau bila level naik          |

### 12.4 Notifikasi

| ID     | Requirement                                              | Prioritas | Acceptance Criteria                                                                |
| ------ | -------------------------------------------------------- | :-------: | ---------------------------------------------------------------------------------- |
| FR-401 | Integrasi WhatsApp (Cloud API atau gateway)              |    P0     | Pesan uji terkirim ke 5 nomor & 1 grup                                             |
| FR-402 | Blast tersegmentasi per RT & per role                    |    P0     | Alert RT 09 → hanya penerima RT 09 + pengurus                                      |
| FR-403 | Template pesan berisi angka konkret & instruksi tindakan |    P0     | Pesan sesuai template §10.2, semua placeholder terisi                              |
| FR-404 | Log pengiriman lengkap dengan latensi per pesan          |    P0     | Tabel `alert_deliveries` terisi; dashboard menampilkan rata-rata latensi           |
| FR-405 | Retry + fallback Telegram                                |    P1     | Matikan WA API → pesan terkirim via Telegram ≤ 2 s                                 |
| FR-406 | CRUD penerima + import CSV + opt-in/opt-out              |    P0     | Import 200 nomor; kirim `STOP` → nonaktif otomatis                                 |
| FR-407 | Validasi & normalisasi nomor ke format E.164             |    P0     | `08123...` → `+628123...`; nomor invalid ditolak dengan pesan jelas                |
| FR-408 | Antrean saat offline & auto-flush saat online            |    P0     | Alert saat internet mati → terkirim otomatis saat pulih, dengan penanda waktu asli |
| FR-409 | Kill switch notifikasi (super admin)                     |    P1     | Aktifkan → 0 pesan keluar, banner merah tampil                                     |
| FR-410 | Kartu status perahu karet                                |    P1     | Status berubah sesuai matriks §10.4                                                |

### 12.5 Administrasi & Audit

| ID     | Requirement                                               | Prioritas | Acceptance Criteria                                                     |
| ------ | --------------------------------------------------------- | :-------: | ----------------------------------------------------------------------- |
| FR-501 | Login username/password + JWT, session timeout 8 jam      |    P0     | Salah password 5× → akun terkunci 15 menit                              |
| FR-502 | RBAC sesuai matriks §4.2                                  |    P0     | Operator tidak bisa mengakses endpoint threshold (403)                  |
| FR-503 | Audit log semua aksi sensitif                             |    P0     | Ubah threshold → tercatat before/after + user + IP                      |
| FR-504 | Halaman kesehatan sistem (broker, DB, WA API, sync, disk) |    P0     | Semua komponen tampil hijau/merah dengan waktu cek terakhir             |
| FR-505 | Backup DB otomatis harian + restore teruji                |    P0     | Restore ke server kosong berhasil dalam ≤ 1 jam                         |
| FR-506 | Halaman validasi akurasi (input pengukuran manual)        |    P1     | Input 30 pengukuran → sistem menghitung MAPE otomatis                   |
| FR-507 | Multi-bahasa: Indonesia (default) & Inggris               |    P1     | Toggle bahasa mengubah seluruh UI; laporan EPICS bisa berbahasa Inggris |
| FR-508 | Halaman KPI EPICS (latensi, akurasi, cakupan) siap-lapor  |    P1     | Angka M-01..M-08 tampil untuk rentang tanggal pilihan                   |

---

## 13. Spesifikasi UI/UX per Halaman

### 13.1 Prinsip desain

- **Legibility first.** Dashboard pos satpam harus terbaca dari 3 meter: angka TMA minimal 72 px, kontras tinggi.
- **Warna + bentuk + teks.** Jangan hanya bergantung pada warna (ada warga/petugas dengan buta warna). Setiap level punya ikon dan label teks.
- **Satu tindakan utama per layar.** Saat Level 3, tombol yang menonjol hanya: Acknowledge, Batalkan, Uji Sirine.
- **Mobile-first untuk pengurus RW**, **desktop/kiosk-first untuk pos satpam**.
- **Bahasa Indonesia sehari-hari**, hindari jargon ("tinggi muka air", bukan "water level"; "kecepatan arus", bukan "flow velocity").

### 13.2 Peta warna level

| Level           | Warna  | Hex       | Ikon | Label               |
| --------------- | ------ | --------- | ---- | ------------------- |
| 0 Aman          | Hijau  | `#16A34A` | ✓    | AMAN                |
| 1 Waspada       | Kuning | `#EAB308` | !    | WASPADA             |
| 2 Siaga         | Oranye | `#F97316` | ⚠    | SIAGA               |
| 3 Kritis        | Merah  | `#DC2626` | ⛔   | KRITIS              |
| Data bermasalah | Abu    | `#6B7280` | ⊘    | DATA TIDAK TERSEDIA |

### 13.3 Halaman

#### H-01 Login

Logo RW 08 + EPICS/IEEE, form sederhana, link "Lihat status publik" tanpa login.

#### H-02 Dashboard Utama (default setelah login)

```
┌─────────────────────────────────────────────────────────────┐
│  STATUS KOMPLEKS:  [ SIAGA ]   ⏱ diperbarui 3 detik lalu     │
│  Sejak 14.03 WIB · Dipicu WL-09-01 · 118 cm                  │
├──────────────────────────┬──────────────────────────────────┤
│  PETA KOMPLEKS           │  TINGGI AIR TERTINGGI            │
│  (marker node berwarna,  │        118 cm                    │
│   klik → detail)         │   ▲ naik 22 cm/jam               │
│                          │   RT 09 · Depan Masjid           │
│                          ├──────────────────────────────────┤
│                          │  HUJAN 15 mm/jam · ARUS 0,9 m/s  │
│                          │  PERAHU: ⛔ JANGAN DITURUNKAN     │
├──────────────────────────┴──────────────────────────────────┤
│  GRAFIK 6 JAM TERAKHIR (TMA + hujan + garis ambang)         │
├──────────────────────────────────────────────────────────────┤
│  KARTU NODE (grid): WL-06-01 · WL-07-01 · WL-09-01 · ...     │
│  tiap kartu: nilai, tren ▲▼, baterai, last seen              │
├──────────────────────────────────────────────────────────────┤
│  [ ACKNOWLEDGE ]  [ KIRIM PERINGATAN MANUAL ]  [ UJI SIRINE ]│
└──────────────────────────────────────────────────────────────┘
```

#### H-03 Mode Kiosk (layar pos satpam)

Tanpa navigasi, tanpa scroll. Angka TMA raksasa, warna latar mengikuti level (merah penuh saat Level 3), jam besar, daftar 3 node teratas, indikator koneksi. Auto-reconnect + auto-reload tiap 6 jam untuk mencegah memory leak browser.

#### H-04 Detail Node

Grafik per metrik, riwayat kesehatan (baterai/RSSI/reboot), parameter kalibrasi, tombol perintah (ping/buzzer/LED/reboot), riwayat alert yang dipicu node ini, catatan pemeliharaan.

#### H-05 Riwayat Alert

Tabel: waktu, level, zona, pemicu, nilai, siapa acknowledge, durasi, hasil (banjir nyata / false alarm). Filter tanggal & level. Klik baris → timeline detail + daftar pengiriman + snapshot kondisi.

#### H-06 Pengaturan Ambang

Editor per level per zona, dengan **preview**: "Dengan ambang ini, dalam 90 hari terakhir akan tercipta 14 alert Level 2 dan 2 alert Level 3." Perubahan butuh konfirmasi ketik ulang kata "SIMPAN".

#### H-07 Penerima Notifikasi

Tabel penerima, filter per RT/role, import CSV, tombol "Kirim pesan uji" per orang, statistik cakupan per RT ("RT 09: 42/45 KK terdaftar — 93%").

#### H-08 Analitik & Laporan

Rekap bulanan, durasi banjir kumulatif, korelasi hujan-TMA, peta panas titik rawan, tombol export laporan PDF/XLSX untuk laporan EPICS 3 bulanan.

#### H-09 Kesehatan Sistem

Status komponen, uptime, antrean notifikasi, penggunaan disk, waktu sync terakhir, log error 24 jam terakhir.

#### H-10 Manajemen Pengguna & Audit Log

CRUD user, reset password, tabel audit yang bisa dicari.

#### H-11 Halaman Publik (tanpa login)

Alamat `/` (admin dipindah ke `/admin`). Satu halaman mobile-first: status kompleks, TMA tertinggi, waktu update, 3 langkah tindakan sesuai level, tombol gabung grup WhatsApp, kontak pengurus RW 08, kondisi per RT, riwayat kejadian, dan info kawasan TCE. Data sensor lebih dari 15 menit ditampilkan sebagai "Data belum tersedia", tidak pernah "Aman". **Tidak menampilkan** nomor telepon warga atau detail node individual.

### 13.4 Aksesibilitas

- Kontras minimum WCAG AA (4.5:1).
- Target sentuh ≥ 44×44 px.
- Bisa dioperasikan penuh dengan keyboard.
- Alert kritis disertai suara di browser + notifikasi web push.

---

## 14. Spesifikasi API

Base URL: `https://{host}/api/v1` · Auth: `Authorization: Bearer <JWT>` · Format: JSON, waktu ISO-8601 dengan offset `+07:00`.

### 14.1 Endpoint utama

| Method                | Path                        | Peran      | Deskripsi                                                                |
| --------------------- | --------------------------- | ---------- | ------------------------------------------------------------------------ |
| POST                  | `/auth/login`               | publik     | Login, mengembalikan access + refresh token                              |
| POST                  | `/auth/refresh`             | publik     | Perpanjang token                                                         |
| GET                   | `/status/current`           | semua      | Ringkasan level kompleks + per zona                                      |
| GET                   | `/public/status`            | publik     | Versi terbatas untuk halaman warga                                       |
| GET                   | `/nodes`                    | viewer+    | Daftar node + status terkini                                             |
| POST                  | `/nodes`                    | tech admin | Daftarkan node baru                                                      |
| PATCH                 | `/nodes/{node_id}`          | tech admin | Ubah metadata/kalibrasi                                                  |
| POST                  | `/nodes/{node_id}/commands` | tech admin | Kirim perintah (buzzer, ping, dst.)                                      |
| GET                   | `/readings`                 | viewer+    | Query time-series (`node_id`, `from`, `to`, `metric`, `agg`, `interval`) |
| GET                   | `/readings/latest`          | viewer+    | Nilai terakhir semua node                                                |
| GET                   | `/alerts`                   | viewer+    | Daftar alert (filter level, status, tanggal)                             |
| GET                   | `/alerts/{id}`              | viewer+    | Detail + timeline + deliveries                                           |
| POST                  | `/alerts/manual`            | operator+  | Buat alert manual                                                        |
| POST                  | `/alerts/{id}/acknowledge`  | operator+  | Acknowledge                                                              |
| POST                  | `/alerts/{id}/cancel`       | operator+  | Batalkan dalam grace window                                              |
| POST                  | `/alerts/{id}/resolve`      | operator+  | Tutup + catatan                                                          |
| GET/PUT               | `/thresholds`               | RW board+  | Baca / ubah ambang                                                       |
| POST                  | `/thresholds/simulate`      | RW board+  | Dry-run terhadap data historis                                           |
| GET/POST/PATCH/DELETE | `/recipients`               | RW board+  | Kelola penerima                                                          |
| POST                  | `/recipients/import`        | RW board+  | Import CSV                                                               |
| POST                  | `/notifications/test`       | RW board+  | Kirim pesan uji                                                          |
| GET                   | `/health`                   | publik     | Health check komponen                                                    |
| GET                   | `/metrics/kpi`              | RW board+  | KPI M-01..M-08 untuk rentang tanggal                                     |
| GET                   | `/audit-logs`               | RW board+  | Audit trail                                                              |
| GET                   | `/export`                   | operator+  | Export CSV/XLSX                                                          |

### 14.2 WebSocket

```
wss://{host}/ws?token=<JWT>

Server → client events:
  reading.new        { node_id, ts, metrics }
  status.changed     { zone, from_level, to_level, alert_id }
  alert.created      { alert }
  alert.updated      { alert }
  node.status        { node_id, status }
  system.mode        { mode }
```

Heartbeat ping tiap 30 detik; klien auto-reconnect dengan exponential backoff (1 s → 30 s).

### 14.3 Contoh respons `/status/current`

```json
{
    "site": "TCE",
    "level": 2,
    "level_label": "SIAGA",
    "since": "2026-08-27T14:03:11+07:00",
    "active_alert_id": 4127,
    "updated_at": "2026-08-27T14:06:02+07:00",
    "highest": {
        "node_id": "WL-09-01",
        "label": "Depan Masjid",
        "zone": "RT-09",
        "water_level_cm": 118.4,
        "rise_rate_cmh": 22.1
    },
    "rain_rate_mmh": 15.2,
    "max_flow_velocity_ms": 0.9,
    "boat_status": "UNSAFE_SHALLOW",
    "zones": [
        {
            "zone": "RT-06",
            "level": 1,
            "water_level_cm": 74.2,
            "degraded": false
        },
        {
            "zone": "RT-09",
            "level": 2,
            "water_level_cm": 118.4,
            "degraded": false
        },
        {
            "zone": "RT-11",
            "level": 0,
            "water_level_cm": null,
            "degraded": true,
            "note": "1 dari 2 sensor offline"
        }
    ],
    "system": { "mode": "NORMAL", "nodes_online": 18, "nodes_total": 20 }
}
```

### 14.4 Format error

```json
{
    "error": {
        "code": "THRESHOLD_INVALID",
        "message": "Ambang Level 2 harus lebih kecil dari Level 3.",
        "field": "value_low",
        "request_id": "req_01J9X2..."
    }
}
```

---

## 15. Kebutuhan Non-Fungsional

### 15.1 Kinerja & latensi

Anggaran latensi end-to-end untuk memenuhi target < 1 detik:

| Tahap                                 | Anggaran       |
| ------------------------------------- | -------------- |
| Pembacaan sensor & agregasi di ESP-32 | ≤ 150 ms       |
| Publish MQTT → broker (WiFi lokal)    | ≤ 80 ms        |
| Validasi + EWMA + persist             | ≤ 60 ms        |
| Evaluasi alert engine                 | ≤ 100 ms       |
| Enqueue + panggilan WhatsApp API      | ≤ 400 ms       |
| **Total (p50)**                       | **≈ 790 ms**   |
| **Total (p95)**                       | **≤ 1.500 ms** |

Persyaratan tambahan:

- NFR-01: Dashboard TTI ≤ 3 detik pada koneksi 3G.
- NFR-02: Query grafik 24 jam × 5 node ≤ 1,5 detik (p95).
- NFR-03: Sistem menangani 20 node × 1 Hz tanpa backlog.
- NFR-04: WebSocket mendukung ≥ 50 klien bersamaan.

### 15.2 Ketersediaan & ketahanan

- NFR-05: Uptime edge server ≥ 99%/bulan (excl. maintenance terjadwal).
- NFR-06: RPO ≤ 5 menit, RTO ≤ 2 jam.
- NFR-07: Edge tetap beroperasi penuh ≥ 24 jam tanpa internet.
- NFR-08: Edge server bertahan ≥ 6 jam saat listrik padam (UPS).
- NFR-09: Semua service dijalankan sebagai container dengan `restart: unless-stopped` + watchdog.
- NFR-10: Node menyimpan ≥ 6 jam data di memori saat MQTT putus.

### 15.3 Skalabilitas

- NFR-11: Arsitektur mendukung penambahan hingga 50 node dan 5 zona tambahan tanpa perubahan skema.
- NFR-12: Multi-site ready (kolom `site_id` sudah ada) untuk replikasi ke kompleks lain.

### 15.4 Kompatibilitas

- NFR-13: Browser: Chrome/Edge/Firefox 2 versi terakhir, Safari iOS 15+.
- NFR-14: Responsif 360 px – 1920 px + mode kiosk 1080p.
- NFR-15: Berfungsi pada Raspberry Pi 5 (8 GB RAM) sebagai host penuh.

### 15.5 Observability

- NFR-16: Structured logging (JSON) dengan `request_id` dan `node_id`.
- NFR-17: Metrik Prometheus: `readings_ingested_total`, `alert_latency_ms`, `wa_delivery_failures_total`, `node_offline_count`.
- NFR-18: Retensi log aplikasi 30 hari di edge, 90 hari di cloud.

---

## 16. Keamanan, Privasi, dan Kepatuhan

### 16.1 Keamanan teknis

| ID     | Kontrol                                                                                                                        |
| ------ | ------------------------------------------------------------------------------------------------------------------------------ |
| SEC-01 | MQTT dengan autentikasi per-device (username + password unik atau sertifikat), ACL membatasi tiap node hanya ke topik miliknya |
| SEC-02 | TLS untuk semua koneksi eksternal (HTTPS, MQTTS); di dalam LAN minimal MQTT + auth                                             |
| SEC-03 | Password di-hash dengan bcrypt/argon2, tanpa password default yang bertahan setelah setup                                      |
| SEC-04 | JWT umur pendek (15 menit) + refresh token; revoke saat logout                                                                 |
| SEC-05 | Rate limiting: 10 percobaan login / 15 menit / IP; API 100 req/menit/user                                                      |
| SEC-06 | Proteksi OWASP Top 10: parameterized query, sanitasi input, CSP, CSRF token, secure cookie                                     |
| SEC-07 | Kredensial disimpan sebagai environment variable/secret manager, **tidak pernah** di repo                                      |
| SEC-08 | Perintah destruktif (reboot, ubah ambang, blast manual) butuh konfirmasi 2 langkah dan tercatat di audit                       |
| SEC-09 | Backup terenkripsi, uji restore tiap kuartal                                                                                   |
| SEC-10 | Repo Git privat, `.env.example` tanpa nilai nyata                                                                              |

### 16.2 Privasi data warga

- Data pribadi yang disimpan **hanya**: nama, nomor WhatsApp, RT, peran. Tidak ada NIK, alamat rumah detail, atau data keluarga.
- Persetujuan tercatat (`consent_at`) dengan sumber pendaftaran.
- Mekanisme berhenti (`STOP`) berlaku permanen sampai warga mendaftar ulang.
- Nomor telepon **tidak pernah** tampil di halaman publik dan disamarkan sebagian (`+62812****789`) di UI selain halaman Penerima.
- Export data yang memuat nomor hanya bisa diunduh Super Admin & Pengurus RW, dan aksinya tercatat di audit log.
- Sesuai semangat UU PDP No. 27/2022: pembatasan tujuan, minimalisasi data, hak penghapusan.

### 16.3 Kepatuhan & etika (EPICS/IEEE)

- Data lingkungan (level air, hujan, arus) bukan data pribadi → sesuai pernyataan proposal bahwa IRB tidak diperlukan. Namun **daftar penerima notifikasi adalah data pribadi** dan diperlakukan sesuai §16.2.
- Disclaimer wajib muncul di halaman publik dan di pesan pertama saat pendaftaran: _"Sistem ini adalah alat bantu kesiapsiagaan, bukan jaminan mutlak. Tetap ikuti arahan petugas."_
- Tiang ukur manual berwarna dipertahankan sebagai cadangan visual selama masa transisi.

---

## 17. Tech Stack & Infrastruktur

### 17.1 Rekomendasi stack

| Lapis        | Pilihan utama                                                          | Alternatif                | Alasan                                                                                    |
| ------------ | ---------------------------------------------------------------------- | ------------------------- | ----------------------------------------------------------------------------------------- |
| MQTT Broker  | **Eclipse Mosquitto 2.x**                                              | EMQX                      | ringan, cukup untuk ≤ 50 node, jalan mulus di Pi 5                                        |
| Backend      | **Node.js 20 + NestJS (TypeScript)**                                   | Python FastAPI            | ekosistem MQTT/WebSocket kuat, satu bahasa dengan frontend                                |
| Rules/flow   | **Alert Engine kustom di backend** + Node-RED opsional untuk prototipe | Node-RED penuh            | Node-RED bagus untuk eksperimen, tapi hysteresis/audit/versioning lebih terkelola di kode |
| Database     | **PostgreSQL 16 + TimescaleDB**                                        | InfluxDB                  | relasional + time-series dalam satu sistem, continuous aggregate                          |
| Cache/Queue  | **Redis + BullMQ**                                                     | RabbitMQ                  | antrean notifikasi & retry                                                                |
| Frontend     | **React 18 + TypeScript + Vite**                                       | Next.js                   | SPA cukup; kiosk butuh kontrol penuh render                                               |
| UI           | **Tailwind CSS + shadcn/ui**                                           | MUI                       | cepat, mudah bikin tampilan kontras tinggi                                                |
| Chart        | **Recharts** / uPlot untuk data padat                                  | Chart.js                  | uPlot jauh lebih ringan untuk ribuan titik di Pi                                          |
| Peta         | **Leaflet + OpenStreetMap** + overlay siteplan kompleks                | Mapbox                    | gratis, bisa pakai gambar siteplan sebagai image overlay                                  |
| WhatsApp     | **WhatsApp Cloud API (Meta)**                                          | Fonnte / Wablas / Baileys | Cloud API resmi & stabil; gateway lokal lebih murah tapi berisiko banned — lihat OQ-01    |
| Realtime web | **Socket.IO**                                                          | SSE                       | reconnect otomatis bawaan                                                                 |
| Deploy       | **Docker Compose** di Pi 5 & VPS                                       | k3s                       | sederhana, mudah diserahterimakan                                                         |
| Monitoring   | **Prometheus + Grafana** (opsional)                                    | Uptime Kuma               | Uptime Kuma cukup untuk kebutuhan RW                                                      |
| CI/CD        | GitHub Actions                                                         | GitLab CI                 | lint + test + build image                                                                 |

### 17.2 Topologi deployment

```
[Pi 5 @ Pos Satpam]  docker compose:
   mosquitto · api · worker-notifier · postgres+timescale · redis · web · uptime-kuma
   + UPS + tampilan kiosk (Chromium --kiosk ke localhost)

[VPS Hostinger/Azure] docker compose:
   api-cloud · postgres+timescale (arsip) · web (dashboard publik & remote) · backup job

Sinkronisasi: sync-agent di Pi → HTTPS batch tiap 30 detik (atau realtime saat alert),
idempotent berbasis (node_id, seq).
```

### 17.3 Environment

| Env          | Tujuan              | Data                                        |
| ------------ | ------------------- | ------------------------------------------- |
| `local`      | pengembangan        | simulator node (script Python publish MQTT) |
| `staging`    | uji integrasi & UAT | data replay dari rekaman lapangan           |
| `production` | operasional         | data nyata                                  |

**Wajib**: tersedia **node simulator** yang bisa memutar ulang skenario banjir (naik perlahan, naik mendadak, sensor rusak, node offline) agar tim bisa menguji tanpa menunggu hujan.

---

## 18. Rencana Pengujian & Kriteria Penerimaan

### 18.1 Level pengujian

| Level             | Cakupan                                                | Alat                | Target                                             |
| ----------------- | ------------------------------------------------------ | ------------------- | -------------------------------------------------- |
| Unit              | Fungsi EWMA, konversi unit, evaluasi rule, hysteresis  | Jest/Vitest         | coverage ≥ 80% pada modul alert engine             |
| Integrasi         | MQTT → DB → alert → notifikasi                         | testcontainers      | seluruh alur lulus                                 |
| Simulasi (HIL)    | Node simulator memutar 12 skenario                     | script custom       | 0 false alarm pada skenario noise                  |
| Beban             | 20 node × 1 Hz × 1 jam                                 | k6 / mqtt-bench     | 0 message loss, p95 latency < 1,5 s                |
| Ketahanan         | Cabut WAN, cabut listrik, matikan broker, matikan node | manual              | sesuai matriks mode §5.3                           |
| Keamanan          | OWASP ZAP + review manual                              | ZAP                 | 0 temuan High                                      |
| UAT               | Skenario operasional bersama satpam & pengurus         | checklist           | ≥ 90% tugas selesai tanpa bantuan                  |
| Validasi lapangan | Banding sensor vs meteran manual & metode pelampung    | pengukuran lapangan | M-02 & M-03 tercapai                               |
| Drill             | Simulasi evakuasi penuh                                | observasi           | waktu respons komunitas terukur sebelum vs sesudah |

### 18.2 Skenario uji wajib (alert engine)

| #    | Skenario                                       | Ekspektasi                                                                     |
| ---- | ---------------------------------------------- | ------------------------------------------------------------------------------ |
| S-01 | Air naik perlahan 60→160 cm dalam 2 jam        | L1 → L2 → L3 berurutan, masing-masing sekali                                   |
| S-02 | Spike tunggal 200 cm lalu kembali normal       | **tidak ada alert** (ditolak spike rejection)                                  |
| S-03 | Osilasi 98–102 cm selama 30 menit              | maksimal 1 kali transisi level (hysteresis bekerja)                            |
| S-04 | Satu node melapor 160 cm, node tetangga 70 cm  | **tidak blast** ke warga; muncul `SYS-SENSOR-FAULT`                            |
| S-05 | Node kritis melapor 160 cm sendirian           | blast (karena `is_critical`)                                                   |
| S-06 | Internet mati saat Level 3 terpicu             | sirine bunyi, WA masuk antrean, terkirim saat online dengan penanda waktu asli |
| S-07 | Node reconnect mengirim 300 data buffered lama | data masuk arsip, **0 alert** tercipta                                         |
| S-08 | Semua node satu zona offline                   | `SYS-NO-DATA-ZONE` ke operator, zona ditandai abu-abu, bukan hijau             |
| S-09 | Naik mendadak 45 cm/jam pada TMA 90 cm         | L3 terpicu oleh rule laju kenaikan meski TMA < 150 cm                          |
| S-10 | Mode maintenance aktif, ambang terlampaui      | alert tercatat, **0 WA terkirim**, banner tampil                               |
| S-11 | Operator membatalkan dalam grace window        | blast warga batal, satgas & sirine tetap jalan, tercatat di audit              |
| S-12 | Baterai node turun < 11,4 V                    | interval melambat, `SYS-BATT-LOW` ke admin teknis                              |

### 18.3 Definition of Done (per fitur)

- [ ] Kriteria penerimaan di tabel FR terpenuhi
- [ ] Unit + integration test lulus di CI
- [ ] Ditinjau minimal 1 anggota tim lain
- [ ] Terdokumentasi di README/API docs (OpenAPI)
- [ ] Diuji di staging dengan node simulator
- [ ] Tidak menambah temuan keamanan High
- [ ] Teks UI berbahasa Indonesia dan sudah dibaca oleh minimal 1 orang non-teknis

---

## 19. Roadmap & Milestone (12 Bulan)

Selaras dengan timeline proposal EPICS (Bulan 1–12).

| Fase                              | Bulan | Fokus software                                                                                                                                     | Deliverable                                                       |
| --------------------------------- | ----- | -------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------- |
| **F0 — Discovery & Desain**       | 1–2   | Survei lapangan bersama RW, finalisasi titik node & zona, kontrak data, wireframe, skema DB, setup repo & CI                                       | PRD final, ERD, wireframe, kontrak MQTT, repo + CI hijau          |
| **F1 — Fondasi Data**             | 3–4   | Broker + ingestion + validasi + EWMA + persist + node simulator + halaman device management                                                        | Data dari simulator tersimpan & tampil; dashboard v0              |
| **F2 — Dashboard & Alert Engine** | 5–7   | Dashboard real-time, peta, grafik, alert engine lengkap (sustain, hysteresis, corroboration), mode kiosk, threshold editor                         | Alert Level 1–3 berjalan otomatis dengan sirine lokal             |
| **F3 — Notifikasi & Integrasi**   | 8–9   | WhatsApp API, segmentasi penerima, log latensi, fallback Telegram, halaman publik, deteksi anomali sensor (AI-02)                                  | Pesan uji terkirim; uji beban & ketahanan lulus; KPI M-01 terukur |
| **F4 — Hardening & Serah Terima** | 10–12 | UAT dengan satpam & pengurus, drill evakuasi, dokumentasi, pelatihan, laporan KPI, backup/restore teruji, modul nowcasting AI-01 (jika data cukup) | Sistem diserahkan, SOP tertulis, manual pengguna, laporan EPICS   |

**Checkpoint pelaporan EPICS (tiap 3 bulan):** Bulan 3, 6, 9, 12 — halaman KPI (FR-508) menghasilkan angka untuk laporan tanpa kerja manual.

### 19.1 Prioritas kalau waktu/anggaran menipis

Urutan yang dipertahankan (paling penting di atas):

1. Ingestion + penyimpanan andal (tanpa ini semua sia-sia)
2. Alert engine + sirine lokal (bekerja tanpa internet)
3. Dashboard kiosk untuk satpam
4. WhatsApp Level 3 saja
5. WhatsApp Level 2 + segmentasi RT
6. Analitik & laporan
7. Modul AI

---

## 20. Risiko & Mitigasi

| ID   | Risiko                                                    |      Dampak       |   Prob.    | Mitigasi                                                                                                                                  |
| ---- | --------------------------------------------------------- | :---------------: | :--------: | ----------------------------------------------------------------------------------------------------------------------------------------- |
| R-01 | Nomor WhatsApp gateway diblokir Meta karena blast massal  |      Tinggi       |   Sedang   | Pakai Cloud API resmi + template pre-approved; opt-in eksplisit; fallback Telegram + sirine; uji dengan volume kecil dulu                 |
| R-02 | False alarm menurunkan kepercayaan warga ("cry wolf")     | **Sangat tinggi** |   Sedang   | Sustain + hysteresis + corroboration + grace window; mulai dengan Level 3 semi-manual selama 1 musim hujan                                |
| R-03 | Internet/listrik mati justru saat banjir                  |      Tinggi       | **Tinggi** | Edge-first, UPS 6 jam, solar di node, sirine lokal independen, antrean notifikasi                                                         |
| R-04 | Sensor ultrasonik meleset karena sampah/eceng gondok/busa |      Tinggi       |   Tinggi   | Multi-node corroboration, deteksi anomali, jadwal pembersihan di SOP, pertimbangkan sensor redundan (pressure/capacitive) di titik kritis |
| R-05 | Data historis kurang untuk melatih model AI               |      Sedang       |   Tinggi   | AI sebagai Fase 2; sistem tetap fungsional penuh tanpa AI                                                                                 |
| R-06 | Vandalisme/pencurian node & panel surya                   |      Sedang       |   Sedang   | Alert `SYS-NODE-OFFLINE` cepat, enclosure terkunci, penempatan di area terpantau, keterlibatan satpam                                     |
| R-07 | Tim mahasiswa lulus/berganti, tidak ada yang meneruskan   |    **Tinggi**     |   Tinggi   | Dokumentasi menyeluruh, Docker Compose 1 perintah, kode berkomentar bahasa Indonesia, regenerasi lewat magang/skripsi tiap semester       |
| R-08 | Biaya cloud/hosting habis setelah dana hibah selesai      |      Tinggi       |   Sedang   | Edge server sebagai sumber kebenaran; cloud opsional; estimasi biaya tahunan disampaikan ke RW sejak awal                                 |
| R-09 | Satpam tidak nyaman dengan dashboard                      |      Sedang       |   Sedang   | Mode kiosk sangat sederhana, UAT langsung dengan mereka, pelatihan berulang, manual bergambar                                             |
| R-10 | Jam sistem node tidak sinkron → urutan data kacau         |      Sedang       |   Sedang   | NTP di node + server timestamp sebagai otoritas, `seq` untuk urutan                                                                       |
| R-11 | Warga menganggap sistem = jaminan aman                    |      Tinggi       |   Sedang   | Disclaimer di setiap kanal, tiang manual dipertahankan, materi sosialisasi menekankan keterbatasan                                        |

---

## 21. Serah Terima, Operasional, dan Pemeliharaan

### 21.1 Paket serah terima

1. **Source code** di repo Git (privat) + tag rilis v1.0
2. **Docker Compose** + `.env.example` + skrip `install.sh` satu perintah
3. **Manual Operator** (bahasa Indonesia, bergambar): cara membaca dashboard, acknowledge alert, uji sirine, apa yang harus dilakukan tiap level
4. **Manual Admin Teknis**: kalibrasi, tambah node, restore backup, troubleshooting 10 masalah tersering
5. **Dokumentasi API** (OpenAPI/Swagger) + diagram arsitektur & wiring
6. **SOP tertulis** yang diintegrasikan ke prosedur harian satpam
7. **Rekaman video pelatihan** (agar satpam baru bisa belajar mandiri)
8. **Daftar biaya berjalan** (hosting, WA API, penggantian baterai) untuk anggaran RW

### 21.2 Jadwal pemeliharaan rutin

| Frekuensi | Kegiatan                                                                 | Penanggung jawab         |
| --------- | ------------------------------------------------------------------------ | ------------------------ |
| Harian    | Cek dashboard hijau, cek node offline                                    | Satpam                   |
| Mingguan  | Uji sirine + kirim WA uji ke 1 nomor                                     | Satpam                   |
| Bulanan   | Bersihkan sensor & panel surya, cek baterai, review alert bulan lalu     | Satgas + admin teknis    |
| Kuartalan | Uji restore backup, update dependensi keamanan, banding sensor vs manual | Mahasiswa magang         |
| Tahunan   | Tinjau ulang ambang berdasarkan data musim hujan terakhir                | Pengurus RW + tim teknis |

### 21.3 Keberlanjutan

Peran universitas dijaga minimal (sesuai syarat EPICS): mahasiswa magang/skripsi tiap semester hanya mengerjakan pengembangan tambahan, sementara **operasi harian sepenuhnya di tangan pengurus RW dan satpam**. Semua kredensial admin dipegang RW; tim mahasiswa memakai akun terpisah yang bisa dicabut.

---

## 22. Open Questions / Keputusan yang Belum Diambil

| ID    | Pertanyaan                                                                                                                                                              | Dampak                                 | Perlu diputuskan sebelum    |
| ----- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------- | --------------------------- |
| OQ-01 | **Diputuskan sementara:** gateway Fonnte (terpasang, diuji ke satu nomor dan satu grup). Meta Cloud API tetap opsi bila risiko banned atau tulisan "sent via fonnte.com" tidak bisa diterima. WhatsApp: Cloud API resmi (berbayar, stabil) atau gateway lokal (murah, risiko banned)?                                                                                 | Biaya & keandalan kanal utama          | Bulan 7                     |
| OQ-02 | Konektivitas node: WiFi kompleks, LoRa + gateway, atau SIM/GSM per node? Infografis menyebut LoRa, proposal menyebut WiFi ESP-32 — perlu diselaraskan                   | Arsitektur ingestion & biaya           | Bulan 2                     |
| OQ-03 | Level 3 sepenuhnya otomatis, atau selalu melewati konfirmasi manusia (grace window)?                                                                                    | Kecepatan vs risiko false alarm        | Bulan 6                     |
| OQ-04 | Sensor ultrasonik: HC-SR05 (tidak tahan air) vs JSN-SR04T/A02YYUW (waterproof)? Proposal menyebut HC-SR05 untuk pemakaian outdoor di atas saluran                       | Akurasi & umur perangkat               | Bulan 3 (sebelum pembelian) |
| OQ-05 | Bagian keselamatan proposal menyebut "dual sensors (ultrasonic dan capacitive)", tapi sensor kapasitif tidak ada di anggaran. Apakah redundansi tetap dilakukan?        | Klaim redundansi & keandalan           | Bulan 3                     |
| OQ-06 | Berapa titik node persisnya per tipe? Anggaran menyebut 11 unit solar/baterai/panel box, 5 HC-SR05, 5 water flow, 2 tipping bucket, 2 anemometer — perlu peta definitif | Skema `nodes` & fusion                 | Bulan 2                     |
| OQ-07 | Ambang 100/150 cm diukur dari mana — dasar saluran, permukaan jalan, atau lantai rumah? Datum harus disepakati tertulis dengan RW                                       | **Kritis** — salah datum = alert salah | Bulan 1                     |
| OQ-08 | Cloud: Azure (disebut proposal) atau VPS Hostinger (juga di anggaran)? Keduanya dianggarkan                                                                             | Biaya berjalan setelah hibah           | Bulan 4                     |
| OQ-09 | Apakah status pompa (11 pompa otomatis) akan dimonitor juga? Sangat bernilai untuk konteks, butuh sensor arus/status tambahan                                           | Cakupan v2                             | Bulan 5                     |
| OQ-10 | Siapa pemegang akun Super Admin setelah serah terima, dan bagaimana suksesi bila pengurus RW berganti?                                                                  | Tata kelola                            | Bulan 10                    |

---

## 23. Lampiran

### 23.1 Glosarium

| Istilah           | Arti                                                             |
| ----------------- | ---------------------------------------------------------------- |
| **TMA**           | Tinggi Muka Air (cm) di atas dasar saluran/datum yang disepakati |
| **EWMA**          | Exponentially Weighted Moving Average — filter perataan data     |
| **Hysteresis**    | Selisih ambang naik vs turun agar status tidak berkedip          |
| **Sustain**       | Durasi minimum ambang harus terlampaui sebelum alert             |
| **Corroboration** | Konfirmasi dari lebih dari satu sensor                           |
| **FRI**           | Flood Risk Index, skor gabungan 0–100                            |
| **Edge server**   | Server lokal di lokasi (Raspberry Pi 5)                          |
| **LWT**           | Last Will Testament — pesan MQTT otomatis saat klien terputus    |
| **Nowcasting**    | Prediksi jangka sangat pendek (< 2 jam)                          |
| **Datum**         | Titik referensi nol pengukuran ketinggian                        |

### 23.2 Tabel ambang default (draft — wajib divalidasi lapangan)

| Level | Metrik             | Operator | Nilai | Sustain | Hysteresis | Cooldown |
| :---: | ------------------ | -------- | ----- | ------- | ---------- | -------- |
|   1   | `water_level_cm`   | ≥        | 70    | 60 s    | 5 cm       | 30 mnt   |
|   1   | `rain_rate_mmh`    | ≥        | 10    | 300 s   | —          | 30 mnt   |
|   2   | `water_level_cm`   | ≥        | 100   | 30 s    | 10 cm      | 15 mnt   |
|   2   | `rise_rate_cmh`    | ≥        | 20    | 60 s    | 5 cm/j     | 15 mnt   |
|   3   | `water_level_cm`   | ≥        | 150   | 30 s    | 10 cm      | 15 mnt   |
|   3   | `rise_rate_cmh`    | ≥        | 40    | 60 s    | 10 cm/j    | 15 mnt   |
|   3   | `flow_velocity_ms` | ≥        | 1,5   | 60 s    | 0,3 m/s    | 15 mnt   |

> Angka 100 cm dan 150 cm diambil dari proposal. Ambang lain adalah usulan awal yang **harus dikalibrasi ulang** setelah satu musim hujan data terkumpul, dan setelah OQ-07 (datum) diputuskan.

### 23.3 Rumus konversi level air

```
water_level_cm = mount_height_cm − distance_cm + calib_offset_cm

Contoh: sensor dipasang 220 cm di atas dasar saluran,
        distance_cm terbaca 132,4 cm, offset −1,8 cm
        → TMA = 220 − 132,4 + (−1,8) = 85,8 cm
```

Validasi: jika `distance_cm` di luar rentang `[10, mount_height_cm + 20]`, tandai `OUT_OF_RANGE`.

### 23.4 Perhitungan MAPE untuk KPI akurasi

```
MAPE = (1/n) × Σ | (manual_cm − sensor_cm) / manual_cm | × 100%
Akurasi = 100% − MAPE      → target ≥ 98,86% (MAPE ≤ 1,14%)
```

Minimal 30 pasang pengukuran, mencakup rentang rendah (< 50 cm), sedang (50–120 cm), dan tinggi (> 120 cm).

### 23.5 Checklist kesiapan rilis (Go-Live)

- [ ] Semua FR berprioritas P0 lulus acceptance criteria
- [ ] 12 skenario uji alert engine (§18.2) lulus
- [ ] Uji ketahanan: cabut WAN 6 jam, cabut listrik 2 jam — lulus
- [ ] Daftar penerima terisi & terverifikasi ≥ 90% KK RT 06–11
- [ ] Pesan uji terkirim ke semua ketua RT & satgas
- [ ] Backup otomatis berjalan & restore sudah pernah diuji
- [ ] Manual operator sudah dibaca dan dipahami minimal 3 satpam
- [ ] SOP sudah ditandatangani pengurus RW
- [ ] Ambang batas disetujui tertulis oleh RW (dengan datum yang jelas)
- [ ] Disclaimer tampil di halaman publik dan pesan pendaftaran
- [ ] Kredensial Super Admin diserahkan ke RW, akun mahasiswa terpisah
- [ ] Tiang ukur manual masih terpasang sebagai cadangan

---

## Riwayat Revisi

| Versi | Tanggal     | Perubahan                                              | Penulis |
| ----- | ----------- | ------------------------------------------------------ | ------- |
| 1.0   | 27 Agu 2026 | Draft awal berdasarkan proposal EPICS in IEEE Mei 2026 | —       |
| 1.1   | 24 Okt 2026 | Landing publik di `/` dan admin di `/admin`; integrasi WhatsApp nyata via Fonnte + queue + retry (OQ-01); fakta kawasan disamakan dengan proposal EPICS (6 dari 11 RT, ±1.300 jiwa, 13 ha, 11 pompa); KPI M07 tanpa angka KK sampai RW mendata; pesan Kritis menghitung status perahu dari data; ambang Waspada 70 cm (sistem) berbeda dari proposal (Level 1 di bawah 100 cm), belum diputuskan | —       |
