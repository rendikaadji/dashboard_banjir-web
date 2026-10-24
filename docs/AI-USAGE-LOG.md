# Log Penggunaan AI

Format dan aturan: [AI-USAGE-POLICY.md §16](AI-USAGE-POLICY.md). Kolom **Anggota**, **Reviewer**, dan **Commit** diisi manusia saat commit; AI tidak mengisinya.

| Tanggal    | Anggota | Artefak (ID)                         | Tier | Alat AI     | Cakupan bantuan                                                                                                                                      | Yang ditulis manusia                                                                 | Reviewer | Commit |
| ---------- | ------- | ------------------------------------ | :--: | ----------- | ---------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------ | -------- | ------ |
| 2026-10-24 | [isi]   | A-07 Integrasi WhatsApp (Fonnte)     |  T2  | Claude Code | Job kirim + retry 2/10/60 s, ubah `WhatsAppDispatcher` dari simulasi ke kirim nyata, tes dengan `Http::fake`                                         | Pilihan provider (OQ-01), token, nomor dan grup uji, uji kirim nyata                 | [isi]    | [isi]  |
| 2026-10-24 | [isi]   | A-15 Isi pesan WhatsApp              |  T3  | Claude Code | Status perahu pada pesan Kritis kini dihitung dari data (sebelumnya teks tetap); titik evakuasi dibaca dari `.env`                                   | **Persetujuan tertulis Pengurus RW 08 atas isi pesan: BELUM ADA**                    | [isi]    | [isi]  |
| 2026-10-24 | [isi]   | A-13 UI landing publik dan admin     |  T1  | Claude Code | Landing `/`, tema admin, peta, favicon; admin dipindah ke `/admin`                                                                                   | Gambar referensi desain, keputusan arah visual                                       | [isi]    | [isi]  |
| 2026-10-24 | [isi]   | A-14 Teks antarmuka bahasa Indonesia |  T1  | Claude Code | Teks landing disusun dari proposal EPICS (PDF) dan teks kawasan dari pemilik                                                                         | Teks kawasan dan PDF proposal sebagai sumber fakta; **belum dibaca orang non-teknis** | [isi]    | [isi]  |
| 2026-10-24 | [isi]   | A-08 Alert engine                    |  T3  | Claude Code | **Tidak diubah.** Ambang Waspada 70 cm tetap; hanya tampilan publik disamakan dengan proposal. `CHANGELOG-SAFETY.md` tidak perlu diperbarui          | -                                                                                    | -        | -      |

## Catatan insiden (untuk rekap kuartalan, §16.2)

1. **Nama karangan lolos ke tampilan publik.** Nama "Masjid Al-Ikhlas" sebagai titik evakuasi tidak ada di PRD maupun proposal, tetapi sempat tampil di landing dan pesan WhatsApp. Sudah dihapus; titik evakuasi kini kosong sampai pengurus RW menetapkannya.
2. **Angka karangan di data contoh.** Jumlah KK per RT (total 240) dan deskripsi sektor tiap RT berasal dari seeder, bukan dari sumber. Sudah dibuang dari tampilan dan KPI M07.
3. **Data pribadi masuk sesi AI.** PDF proposal yang ditempel berisi nomor telepon dan surel tim serta kontak mitra, dan satu nomor WhatsApp uji diketik langsung. Kebijakan §6 melarang ini. Perlu ditinjau Project Leader. Dua kontak mitra (telepon dan surel pengurus RW) kini tampil di landing publik atas permintaan pemilik; tinjau apakah boleh.
4. **Ambang tidak sama dengan proposal.** Proposal: Level 1 di bawah 100 cm; sistem: Waspada mulai 70 cm. Keputusan ada pada Technical Lead dan RW (A-09, T4).
