---
tags: [simado, testing, psi, k6]
---

# 03 - Testing Plan (Ditunda, dicatat 2026-09-21)

Baseline lokal valid: Performa 86, Aksesibilitas 95, SEO 100, Best Practices 92 (127.0.0.1).
Hasil PSI via ngrok TIDAK VALID — yang diukur halaman error `cdn.ngrok.com`, bukan SIMADO.

- [ ] Langkah 2B Network: verifikasi `Cache-Control: max-age=31536000` untuk `logo.webp` (Disable cache OFF), cek TTFB (sebelumnya 855 ms)
- [ ] Langkah 3 Clockwork (sudah dipasang dev): cek Timeline/DB/Cache saat Scan Folder 50 file (2,6 dtk) & 1000 file (14,2 dtk); fokus `AdminController@scanFolder`, N+1 `dashboard.blade.php:405`, `WHERE IN drive_file_id`
- [ ] Langkah 4 DB & Index: `EXPLAIN` untuk `uploads` (`approved_at/revision_requested_at`, `user_id OR assigned_user_id`, `subfolder`, `filename LIKE`); jika `Using filesort` → migrasi index baru
- [ ] Langkah 5 Load & Rate Limit: script k6 20 VU ke `POST /admin/scan-folder` (scan_all 0/1) vs `RateLimitApi` 120 GET/60 POST; threshold p95 <3,5 dtk; pertimbangkan Queue Job jika gagal (butuh VPS, bukan shared hosting)
- [ ] Langkah 6 Security & SEO: `curl -I` cek HSTS/CSP/COOP/COEP (`SecurityHeaders.php`), tambah `Cross-Origin-Opener-Policy: same-origin`, duplikasi header statis di `.htaccess`, tambah `Sitemap:` di robots.txt
- [ ] Scan 1000 file: Opsi A (hapus `owners` + cache 60 dtk), Opsi B (progress bar — sudah dicoba lalu revert), Opsi C (Queue Job) — tergantung VPS vs shared & frekuensi file baru
- [ ] Regresi: `php artisan view:cache`, `php -l`, simpan 4 laporan PSI produksi sebagai baseline, regenerasi graphify-out
