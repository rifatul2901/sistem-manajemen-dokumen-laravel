# TODOS - Hosting & Selanjutnya

## Hosting

Checklist deployment lengkap dan satu-satunya sumber ada di
`docs-vault/01-Deployment-Runbook.md` — mencakup `.env`, migrasi database,
cron, Cloudflare, dan uji setelah deploy.

Peta tahap 2 (deploy) dan 3 (setelah stabil) ada di `docs-vault/05-Tahap-Deploy.md`.

## Rencana Testing

Rencana lengkap (PSI, Clockwork, `EXPLAIN`, k6, keamanan/SEO): lihat
`docs-vault/03-Testing-Plan.md`. Pengukuran dasar sudah dilakukan pada 2026-11-25 (lihat
**Catatan Terkini** di bawah); item lain di rencana tersebut masih **ditunda**.

## Catatan Terkini (2026-11-25)

- **Tahap 1 selesai (belum deploy):** commit `cef3453` (kutipan bahasa Inggris) dan
  `ad4e30d` (runbook + `docs-vault/04-Production-Env-Example.md` sebagai placeholder).
  Perbaikan kode yang mendahului Tahap 1 ada di `e5be2da` (2026-11-09, lihat "Sudah Selesai").
  `.env` lokal dan kode aplikasi tidak diubah, dan belum di-push ke remote.
- **Dikerjakan di server saat deploy:** `APP_URL`, redirect URI Google OAuth, cookie
  secure, cron, dan backup. Jangan disetel di lokal sekarang.
- **Performa dashboard terukur** (Chrome DevTools pada `/admin/dashboard`, 2026-11-25,
  tanpa throttle, `php artisan serve` lokal): LCP 991 ms dingin / 832 ms hangat, CLS 0,01,
  TTFB 695 ms / 577 ms, HTML 110,8 kB tanpa kompresi, logo 35 kB tanpa cache TTL. Yang
  paling terasa adalah `/admin/list-subfolders` 1,7–1,9 dtk per panggilan. HTML perlu
  kompresi di produksi (Cloudflare). Trace mentah ada di luar repo.
- **Perbaikan keamanan selesai (commit `bc1c1cc`)**: OAuth `state` sekali pakai divalidasi
  sebelum memanggil Google; pesan error generate digenerik (detail tetap di `Log::error`);
  `public/robots.txt` kini `Disallow: /`; tes OAuth baru membuat total **11 tes / 31 assertion**.

## Audit Teknis & Prioritas PR (dicatat 2026-11-04)

Kesimpulan singkat: **tidak ada temuan P0 (kritis)**. Sistem masih layak jalan. Yang ada adalah
risiko integritas data, kinerja pada skala besar, dan celah tata kelola. Beberapa item justru
**tidak perlu dikerjakan sekarang** karena dampaknya kecil atau belum ada pemicunya.

Legenda prioritas:

- **P0** — kritis, perbaiki segera (kehilangan data / sistem mati / celah keamanan terbuka)
- **P1** — perlu sebelum produksi atau sebelum dipakai ramai; untuk PR-11/PR-16 berarti
  "verifikasi dulu, baru kerjakan kalau memang perlu"
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
| PR-10 | Tes alur kerja belum lengkap | `ScanFolderMetadataSyncTest` + `GoogleTokenServiceTest`; approve/revisi/generate belum | Regresi approve/revisi/generate tidak terdeteksi | **P2** | Tambah bertahap sebelum refactor |
| PR-11 | Cache daftar subfolder dashboard | `AdminController@listSubfolders` | ~1,8 dtk per buka dashboard (terukur 2026-11-25) | **P1 (opsional)** | Belum; lakukan dengan syarat scan tetap segar |
| PR-12 | Tidak ada API | `routes/web.php` hanya route web | Integrasi mobile/pihak ketiga terbatas | **P3** | **Tidak perlu** sekarang |
| PR-13 | Belum ada retensi & metadata standar | Tidak ada model terkait | Sulit diaudit secara kearsipan | **P2/P3** | Hanya jika domain kearsipan |
| PR-14 | Belum ada observability | Health `/up` sudah ada; metrik/alert belum | Diagnosa produksi lambat | **P3** | **Tidak perlu** untuk skala ini |
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
- **PR-11** jangan di-cache mentah-mentah. Kunci cache harus menyertakan `folder_id` dan
  TTL maksimal 60 detik; cache dibaca selama TTL masih valid, scan/buka folder wajib
  menyegarkan cache, dan ganti `folder_id` wajib `Cache::forget` cache lama. Tanpa ini,
  subfolder baru bisa tertunda atau tertukar.
- **PR-12/PR-14** hanya perlu jika ada rencana integrasi atau tim operasi. Untuk sekarang,
  tidak sepadan.
- **PR-16** belum ada bukti lambat. Uji 50 baris dulu; jika di atas 30 detik, baru
  pertimbangkan queue atau batasi ukuran batch.

### Urutan pengerjaan yang disarankan

1. **Ukur dulu** — Clockwork, `EXPLAIN`, dan k6 (lihat `docs-vault/03-Testing-Plan.md`).
2. **PR-02** — hanya jika hasil ukur menunjukkan >5 dtk atau payload terlalu besar.
3. **PR-10** — tes alur kerja untuk area yang akan disentuh.
4. **PR-11** — opsional, setelah PR-10; hanya bila daftar folder masih terasa lambat.
5. **PR-07** — audit trail, jika dilanjutkan menjadi skripsi.
6. Sisanya sesuai kebutuhan dan hasil pengukuran.

### PR yang sengaja tidak dikerjakan sekarang

`upload_time` cast, invalidasi cache abaikan, API publik, observability, dan `getFolderSize`
tidak dikerjakan karena pemicunya belum ada. Mencatatnya di sini cukup; jangan menambah
kompleksitas tanpa manfaat yang terukur.

## Sudah Selesai (2026-11-09, commit e5be2da)

- Scan folder menyimpan perubahan nama file dan folder dari Drive.
- Anti-duplikat: unique index `drive_file_id`, collation biner, transaksi, dan `firstOrCreate`.
- Token Google diperbaiki: refresh tidak lagi terjadi di setiap request dan token kosong
  tidak bisa tersimpan (`GoogleTokenService`). Ini penyebab error "Gagal memuat subfolder".
- Tes: `php artisan test` 11 lulus (31 assertion), memakai database tes terpisah (diperbarui 2026-11-25).
- Detail teknis tidak dicatat lagi di sini; lihat riwayat commit dan file migrasi.

## Tindak Lanjut Review Produksi (2026-11-25)

| Item | Kapan | Status |
|---|---|---|
| `.env` server: `APP_DEBUG=false`, `APP_URL=https://...`, `SESSION_SECURE_COOKIE=true`, `SESSION_DRIVER=database`, `LOG_LEVEL=error` | Saat deploy | Sudah ada di [[01-Deployment-Runbook]] / [[04-Production-Env-Example]]; tidak diulang di sini |
| OAuth `state`, pesan error generate, `robots.txt` | Selesai | Commit `bc1c1cc`, sudah ada tes |
| 2FA admin **atau** pembatasan akses (IP / Cloudflare Access) | Sebelum dipublikasikan ke internet | **Perlu keputusan** (PR-09). Tanpa salah satunya, jangan buka publik |
| `trustProxies('*')` dibatasi ke IP Cloudflare | Saat deploy / setelah stabil | Catatan saja, belum diubah |
| Uji COEP/CORP & CSP agar ikon/font CDN tidak terblokir | Saat deploy | Sudah ada di checklist "Setelah Hosting" di runbook |
| Validasi format `parent_id` folder Drive | Menyusul | Catatan saja, belum diubah |
| Sanitasi path preview & containment download history | Menyusul | Risiko rendah, catatan saja |
| Uji generate 50 baris (PR-16), tes approve/revisi/generate (PR-10), cache subfolder (PR-11) | Setelah stabil | Sudah ada di daftar PR; PR-11 opsional |
| Sitemap, canonical URL, queue, audit trail, hash dokumen | Tidak perlu sekarang | Sesuai putusan yang sudah ada |

Catatan: checklist deploy tetap satu-satunya checklist di [[01-Deployment-Runbook]]. Tabel ini hanya penanda waktu dan keputusan, bukan checklist kedua.

## Catatan Penting

- Jangan jalankan `php artisan test` di server produksi. `phpunit.xml` memaksa nama
  database tes (`sistem_manajemen_dokumen_test`), tetapi host/user/password tetap dibaca
  dari `.env`; kredensial produksi akan terpakai dan database tes bisa jadi tidak ada.
- Database tes lokal: `sistem_manajemen_dokumen_test`. Buat sekali jika belum ada:
  `CREATE DATABASE sistem_manajemen_dokumen_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
