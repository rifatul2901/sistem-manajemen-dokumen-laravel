---
tags: [simado, deployment, runbook]
---

# 01 - Deployment Runbook

Satu-satunya sumber checklist deployment (hosting + Cloudflare).
`TODOS.md` hanya menautkan ke dokumen ini.

## Sebelum Hosting
- [ ] Set `APP_ENV=production`, `APP_DEBUG=false`, dan `APP_TIMEZONE=Asia/Makassar` di `.env`
- [ ] Update `APP_URL` ke domain hosting
- [ ] Pastikan `APP_KEY` terisi; jika kosong jalankan `php artisan key:generate`
- [ ] Set `LOG_LEVEL=error` dan `SESSION_SECURE_COOKIE=true` + `SESSION_SAME_SITE=lax` — **khusus di server produksi** (jangan diterapkan di `.env` lokal; cookie secure akan membuat login `http://localhost` gagal)
- [ ] Update database config di `.env` (MySQL, bukan SQLite)
- [ ] Update redirect URI Google OAuth ke domain hosting
  - Nilai callback: `https://<domain>/admin/google/callback` (prefix `/admin` berasal dari route group)
  - Jika URI di Google Cloud Console diubah sebelum deploy sungguhan, OAuth Google di lokal akan berhenti berfungsi
- [ ] Upload `oauth_client_secret.json` via halaman Drive Config
- [ ] Pastikan PHP 8.2+ (disarankan 8.3), document root mengarah ke folder `public/`, dan `storage/` + `bootstrap/cache/` dapat ditulis
- [ ] Pastikan ekstensi PHP aktif: `pdo_mysql`, `curl`, `zip`, `dom`/`xml`, `mbstring`, `gd`, `fileinfo` (dibutuhkan PhpWord, PhpSpreadsheet, DomPDF)
- [ ] Pastikan `storage/app/hasil_generate/` dapat ditulis
- [ ] (Opsional) Impor data lama: `php artisan app:import-json-data`
- [ ] (Opsional) Jalankan `composer install --no-dev --optimize-autoloader` jika deploy dari Git
- [ ] Jalankan `php artisan optimize:clear`
- [ ] Jalankan `php artisan migrate --force` (termasuk unique index `drive_file_id`; aman diulang)
- [ ] Jalankan `php artisan migrate:status` — pastikan tidak ada status **Pending**
- [ ] Jalankan `php artisan route:cache`
- [ ] Jalankan `php artisan config:cache`
- [ ] Jalankan `php artisan view:cache`
- [ ] (Opsional) Jalankan `php artisan storage:link` — file diunduh lewat controller, bukan URL `/storage`

### Khusus generate PDF di Linux
- [ ] Pasang `libreoffice-writer` (`soffice`) — jalur utama konversi DOCX → PDF; tanpa ini aplikasi jatuh ke fallback DomPDF
- [ ] Pastikan path `/usr/bin/soffice` ada dan executable (kode memakai `is_executable('/usr/bin/soffice')`; jika hosting beda path, perlu disesuaikan)
- [ ] Pastikan `exec()` tidak masuk `disable_functions` dan perintah `timeout` tersedia (kode memakai `timeout 90`)
- [ ] (Disarankan) Pasang `ttf-mscorefonts-installer` agar PDF benar-benar memakai Times New Roman; tanpa ini Linux memakai substitusi Liberation Serif
- [ ] Pastikan `/tmp` writable untuk workdir konversi dan `storage/app/hasil_generate/` writable untuk file antara
- [ ] Uji generate sekali: ukuran kertas mengikuti template (A4/Letter), halaman 2 mulai di `IV. RINGKASAN PROJECT`, dan label `Catatan:` tidak terpisah dari paragrafnya

## Setelah Hosting
- [ ] Tambahkan cron di panel hosting:
  `* * * * * cd /path/ke/project && php artisan schedule:run >> /dev/null 2>&1`
- [ ] Buka `/login`, uji scan folder dan satu alur approval
- [ ] Uji generate dokumen/PDF — di Linux memakai LibreOffice bila tersedia, jika tidak baru fallback DomPDF
- [ ] Aktifkan backup database otomatis dan uji restore sekali
- [ ] Cek `curl -I https://<domain>/` — jangan biarkan Cloudflare/cache proxy menyimpan HTML dinamis; hormati `Cache-Control: no-cache, private` dari Laravel
- [ ] Uji ikon Bootstrap & Google Fonts tampil; jika hilang, periksa header COEP/CORP di `app/Http/Middleware/SecurityHeaders.php`
- [ ] Cek `storage/logs/laravel.log` setelah pemakaian pertama

## Sebelum Publikasi ke Internet
- [ ] Putuskan dan terapkan salah satu: **2FA untuk akun admin** (PR-09) **atau** pembatasan akses (Cloudflare Access / whitelist IP)
- [ ] Status saat ini: **belum diputuskan**. Tanpa salah satunya, jangan publikasikan.
- [ ] Peta status dan tahap terkait ada di [[05-Tahap-Deploy]]

## Cloudflare (pasang saat production, bukan sekarang)
1. Daftar Cloudflare (gratis)
2. Masukkan domain
3. Ganti nameserver di registrar
4. Selesai — SSL, CDN, caching otomatis aktif
5. Pastikan aturan cache tidak menyimpan HTML dinamis `/login`, `/admin/*`, dan halaman user; cache hanya aset statis

## Lihat juga
- [[02-Decision-Log]]
- [[03-Testing-Plan]]
- [[04-Production-Env-Example]] — contoh nilai `.env` produksi (placeholder saja, bukan checklist)
