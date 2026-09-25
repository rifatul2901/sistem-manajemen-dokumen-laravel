# TODOS - Hosting & Selanjutnya

## Sebelum Hosting
- [ ] Set `APP_ENV=production` dan `APP_DEBUG=false` di `.env`
- [ ] Update `APP_URL` ke domain hosting
- [ ] Update database config di `.env`
- [ ] Update redirect URI Google OAuth ke domain hosting
- [ ] Upload `oauth_client_secret.json` via halaman Drive Config
- [ ] Jalankan `php artisan route:cache`
- [ ] Jalankan `php artisan config:cache`
- [ ] Jalankan `php artisan storage:link`

## Setelah Hosting
- [ ] Tambahkan cron job di panel hosting:
      `* * * * * cd /path/ke/project && php artisan schedule:run >> /dev/null 2>&1`

## Selanjutnya mungkin di tambahkan (Fitur)
- [ ] Dashboard landing page untuk user (opsional)
- [x] Hapus command `cleanup:temp-files` dan `cleanup:orphan-notifications` — sudah dilakukan

### Technical Debt (Perbaikan Kode)
- [x] [[HIGH]] `notifikasi_pdf` path traversal: validasi path di `unlink()` — AdminController:202,267
- [x] [[HIGH]] 419 CSRF di `doApprove()` — token static `{{ csrf_token() }}` diganti dengan `window.csrfToken` dari meta tag
- [x] [[HIGH]] Sanitasi nama file sebelum dipakai di path PDF — AdminController:235
- [x] [[HIGH]] DB transaction untuk cascade delete upload — AdminController:267-272
- [x] [[MEDIUM]] Error feedback alert saat AJAX poin gagal — dashboard.blade.js:607
- [x] [[MEDIUM]] `approved_at` & `revision_requested_at` di `casts()` — Upload.php (di-assign langsung via properti, bukan mass assignment, jadi `$fillable` tidak dibutuhkan)
- [ ] [[LOW]] `upload_time` & `tanggal_kirim_awal` cast `datetime` — Upload.php (keputusan: DILEWATI, format 'd-m-Y H:i' konsisten di seluruh aplikasi; mengubahnya berisiko regresi tampilan tanpa manfaat)
- [x] [[LOW]] Modal `aria-hidden` focus — panggil `document.activeElement?.blur()` sebelum `.hide()` di `doApprove()`
- [x] [[LOW]] CSP `unsafe-eval` — tidak ada `eval()` ditemukan di seluruh kode, jadi `unsafe-eval` TIDAK ditambahkan (lebih aman)
- [x] [[LOW]] `initPoinTooltips()` ganti `setTimeout(50)` pakai requestAnimationFrame — dashboard.blade.php
- [x] [[LOW]] `uploadRevisi()`: bungkus operasi DB + `updateFile()` dalam `DB::transaction()` (DB dulu, Drive di paling akhir agar rollback konsisten) — DashboardController.php

## Catatan — Cloudflare

Cloudflare dipasang **saat deployment / production**, bukan sekarang.

| Langkah | Detail |
|---------|--------|
| 1 | Daftar Cloudflare (gratis) |
| 2 | Masukkan domain |
| 3 | Ganti nameserver di registrar domain |
| 4 | Selesai — SSL, CDN, caching otomatis aktif |

## Rencana Testing (Ditunda — untuk nanti, dicatat 2026-09-21)

Status sekarang: Performa 86, Aksesibilitas 95, SEO 100, Best Practices 92 (valid lokal 127.0.0.1, bukan ngrok).
Hasil PSI via ngrok (Performa/TBT/CLS bagus, Akses 96, Praktik 100, SEO 54) TIDAK VALID — yang diukur halaman error cdn.ngrok.com, bukan SIMADO.

- [ ] Langkah 2B Network: verifikasi `Cache-Control: max-age=31536000` untuk `logo.webp` (Disable cache OFF), cek TTFB (sebelumnya 855 ms)
- [ ] Langkah 3 Clockwork (sudah dipasang dev): cek Timeline/DB/Cache saat Scan Folder 50 file (2,6 dtk) & 1000 file (14,2 dtk); fokus `AdminController@scanFolder`, N+1 `dashboard.blade.php:405`, `WHERE IN drive_file_id`
- [ ] Langkah 4 DB & Index: jalankan `EXPLAIN` untuk `uploads` (`approved_at/revision_requested_at`, `user_id OR assigned_user_id`, `subfolder`, `filename LIKE`); jika `Using filesort` → migrasi index baru
- [ ] Langkah 5 Load & Rate Limit: script k6 20 VU ke `POST /admin/scan-folder` (scan_all 0/1) vs `RateLimitApi` 120 GET/60 POST; threshold p95 <3,5 dtk; pertimbangkan Queue Job jika gagal (butuh VPS, bukan shared hosting)
- [ ] Langkah 6 Security & SEO: `curl -I` cek HSTS/CSP/COOP/COEP (`SecurityHeaders.php`), tambah `Cross-Origin-Opener-Policy: same-origin`, duplikasi header statis di `.htaccess`, tambah `Sitemap:` di robots.txt
- [ ] Scan 1000 file: Opsi A (hapus `owners` + cache 60 dtk), Opsi B (progress bar — sudah dicoba lalu di-revert), Opsi C (Queue Job) — keputusan tergantung hosting VPS vs shared & frekuensi file baru
- [ ] Regresi: `php artisan view:cache`, `php -l`, simpan 4 laporan PSI produksi sebagai baseline, regenerasi graphify-out
