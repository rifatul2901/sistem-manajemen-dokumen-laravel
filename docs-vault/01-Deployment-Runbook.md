---
tags: [simado, deployment, runbook]
---

# 01 - Deployment Runbook

Pindahan dari `TODOS.md` bagian Sebelum/Sesudah Hosting + Cloudflare.

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
- [ ] Tambahkan cron di panel hosting:
  `* * * * * cd /path/ke/project && php artisan schedule:run >> /dev/null 2>&1`

## Cloudflare (pasang saat production, bukan sekarang)
1. Daftar Cloudflare (gratis)
2. Masukkan domain
3. Ganti nameserver di registrar
4. Selesai — SSL, CDN, caching otomatis aktif

## Lihat juga
- [[02-Decision-Log]]
- [[03-Testing-Plan]]
