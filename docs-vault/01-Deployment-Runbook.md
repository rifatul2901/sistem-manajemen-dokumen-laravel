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
- [ ] Update database config di `.env` (MySQL, bukan SQLite)
- [ ] Update redirect URI Google OAuth ke domain hosting
- [ ] Upload `oauth_client_secret.json` via halaman Drive Config
- [ ] Pastikan PHP 8.3, document root mengarah ke folder `public/`, dan `storage/` + `bootstrap/cache/` dapat ditulis
- [ ] (Opsional) Impor data lama: `php artisan app:import-json-data`
- [ ] (Opsional) Jalankan `composer install --no-dev --optimize-autoloader` jika deploy dari Git
- [ ] Jalankan `php artisan optimize:clear`
- [ ] Jalankan `php artisan migrate --force` (termasuk unique index `drive_file_id`; aman diulang)
- [ ] Jalankan `php artisan migrate:status` — pastikan tidak ada status **Pending**
- [ ] Jalankan `php artisan route:cache`
- [ ] Jalankan `php artisan config:cache`
- [ ] (Opsional) Jalankan `php artisan storage:link` — file diunduh lewat controller, bukan URL `/storage`

## Setelah Hosting
- [ ] Tambahkan cron di panel hosting:
  `* * * * * cd /path/ke/project && php artisan schedule:run >> /dev/null 2>&1`
- [ ] Buka `/login`, uji scan folder dan satu alur approval
- [ ] Uji generate dokumen/PDF — di Linux memakai DomPDF, tata letak bisa berbeda dari Word
- [ ] Aktifkan backup database otomatis dan uji restore sekali

## Cloudflare (pasang saat production, bukan sekarang)
1. Daftar Cloudflare (gratis)
2. Masukkan domain
3. Ganti nameserver di registrar
4. Selesai — SSL, CDN, caching otomatis aktif

## Lihat juga
- [[02-Decision-Log]]
- [[03-Testing-Plan]]
