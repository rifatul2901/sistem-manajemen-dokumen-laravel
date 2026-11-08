# TODOS - Hosting & Selanjutnya

## Hosting

Checklist deployment lengkap dan satu-satunya sumber ada di
`docs-vault/01-Deployment-Runbook.md` — mencakup `.env`, migrasi database,
cron, Cloudflare, dan uji setelah deploy.

## Rencana Testing

Rencana lengkap (PSI, Clockwork, `EXPLAIN`, k6, keamanan/SEO): lihat
`docs-vault/03-Testing-Plan.md`. Status: **ditunda**, belum dikerjakan.

## Audit Teknis & Prioritas PR (dicatat 2026-11-04)

Kesimpulan singkat: **tidak ada temuan P0 (kritis)**. Sistem masih layak jalan. Yang ada adalah
risiko integritas data, kinerja pada skala besar, dan celah tata kelola. Beberapa item justru
**tidak perlu dikerjakan sekarang** karena dampaknya kecil atau belum ada pemicunya.

Legenda prioritas:

- **P0** — kritis, perbaiki segera (kehilangan data / sistem mati / celah keamanan terbuka)
- **P1** — perlu sebelum produksi atau sebelum dipakai ramai
- **P2** — terjadwal, penting tapi tidak mendesak
- **P3** — tidak perlu sekarang, catat saja

### Daftar PR

| ID | PR | Bukti | Dampak jika dibiarkan | Prioritas | Putusan |
|---|---|---|---|---|---|
| PR-02 | Insert per file + kirim semua file tiap scan | `AdminController@scanFolder` | 1.000 file = 14,2 dtk (terukur, lihat Rencana Testing); payload JSON besar | **P1** | Perlu jika folder >300 file |
| PR-03 | Pencarian `LIKE '%kata%'` | `RiwayatController:33`, `RiwayatRevisiController:44` | Full table scan; melambat seiring data | **P2** | Ukur `EXPLAIN` dulu |
| PR-04 | `upload_time` berupa string `d-m-Y H:i` | `Upload.php:11`, `StatusController:36-52` | Rapuh, tapi sorting memakai `created_at` | **P3** | **Tidak perlu** (sudah diputuskan) |
| PR-05 | Cache `drive_ignored` tidak pernah di-`forget` | `AdminController` scan | Perubahan daftar abaikan tertunda ≤60 dtk | **P3** | **Tidak perlu** (belum ada UI abaikan) |
| PR-06 | Tidak ada queue job | Tidak ada `dispatch()` / `ShouldQueue` | Proses berat memblokir request; risiko timeout | **P2** | Hanya jika hosting VPS |
| PR-07 | Tidak ada audit trail | Tidak ada model `Audit`; hanya `Log::` | Tidak bisa membuktikan siapa mengubah apa | **P2** | Perlu untuk skripsi/tata kelola |
| PR-08 | Tidak ada hash/integritas dokumen | Tidak ada `hash()` pada file | Perubahan dokumen tidak terdeteksi | **P2/P3** | P2 jika jadi fitur skripsi |
| PR-09 | Belum ada 2FA | Tidak ada `two_factor` | Akun admin rentan jika password bocor | **P2** | Perlu sebelum dipublikasikan |
| PR-10 | Tes alur kerja belum lengkap | `tests/Feature` baru mencakup scan | Regresi approve/revisi/generate tidak terdeteksi | **P2** | Tambah bertahap sebelum refactor |
| PR-12 | Tidak ada API | `routes/web.php` hanya route web | Integrasi mobile/pihak ketiga terbatas | **P3** | **Tidak perlu** sekarang |
| PR-13 | Belum ada retensi & metadata standar | Tidak ada model terkait | Sulit diaudit secara kearsipan | **P2/P3** | Hanya jika domain kearsipan |
| PR-14 | Belum ada observability | Hanya `Log::`, tanpa metrik/health check | Diagnosa produksi lambat | **P3** | **Tidak perlu** untuk skala ini |
| PR-15 | `getFolderSize` rekursif tiap 60 dtk | `AdminController:27` | I/O berulang jika hasil generate banyak | **P3** | **Tidak perlu** |
| PR-16 | Generate massal 50 PDF berjalan sinkron | `AdminGenerateController@generateBulk` (belum diukur) | Berpotensi timeout di shared hosting | **P1 (verifikasi)** | Uji dulu 50 baris |

### Catatan per item

- **PR-02** batch insert/`upsert()` bisa memotong jumlah query, tetapi ukur dulu dengan
  Clockwork. Pada 1000 file, waktu terbesar biasanya di API Drive, bukan di database.
- **PR-03** jangan dioptimasi sebelum diukur. Jalankan `EXPLAIN` pada query riwayat; kalau
  `Using filesort`/`type=ALL` baru tambah index atau FULLTEXT.
- **PR-04** sudah diputuskan dilewati. Sorting memakai `created_at`, dan `StatusController`
  punya fallback saat parsing gagal. Tidak ada urgensi.
- **PR-05** tidak relevan selama belum ada halaman untuk menambah/menghapus daftar abaikan.
  Kalau nanti UI itu dibuat, tambahkan `Cache::forget('drive_ignored')` di titik simpan.
- **PR-06** queue default `database`, tetapi belum ada worker. Di shared hosting worker
  sulit dijalankan; jangan pindah ke queue sebelum hosting jelas (VPS).
- **PR-07/PR-08** bukan bug, melainkan celah tata kelola. Nilainya tinggi untuk skripsi,
  rendah untuk operasional harian saat ini.
- **PR-09** bisa ditunda, tetapi jangan dipublikasikan ke internet tanpa 2FA untuk akun admin.
- **PR-10** kerjakan bertahap: tulis tes untuk bagian yang akan diubah, bukan seluruh aplikasi.
- **PR-12/PR-14** hanya perlu jika ada rencana integrasi atau tim operasi. Untuk sekarang,
  tidak sepadan.
- **PR-16** belum ada bukti lambat. Uji 50 baris dulu; jika di atas 30 detik, baru
  pertimbangkan queue atau batasi ukuran batch.

### Urutan pengerjaan yang disarankan

1. **Ukur dulu** — Clockwork, `EXPLAIN`, dan k6 (lihat `docs-vault/03-Testing-Plan.md`).
2. **PR-02** — hanya jika hasil ukur menunjukkan >5 dtk atau payload terlalu besar.
3. **PR-10** — tes alur kerja untuk area yang akan disentuh.
4. **PR-07** — audit trail, jika dilanjutkan menjadi skripsi.
5. Sisanya sesuai kebutuhan dan hasil pengukuran.

### PR yang sengaja tidak dikerjakan sekarang

`upload_time` cast, invalidasi cache abaikan, API publik, observability, dan `getFolderSize`
tidak dikerjakan karena pemicunya belum ada. Mencatatnya di sini cukup; jangan menambah
kompleksitas tanpa manfaat yang terukur.

## Sudah Selesai (2026-11-06)

- Scan folder menyimpan perubahan nama file dan folder dari Drive.
- Anti-duplikat: unique index `drive_file_id`, collation biner, transaksi, dan `firstOrCreate`.
- Token Google diperbaiki: refresh tidak lagi terjadi di setiap request dan token kosong
  tidak bisa tersimpan (`GoogleTokenService`). Ini penyebab error "Gagal memuat subfolder".
- Tes: `php artisan test` 9 lulus (25 assertions), memakai database tes terpisah.
- Detail teknis tidak dicatat lagi di sini; lihat riwayat commit dan file migrasi.

## Catatan Penting

- Jangan jalankan `php artisan test` di server produksi; konfigurasi tes memakai kredensial
  database dari `.env`.
- Database tes lokal: `sistem_manajemen_dokumen_test`. Buat sekali jika belum ada:
  `CREATE DATABASE sistem_manajemen_dokumen_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
