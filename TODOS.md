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

## Selanjutnya (Fitur)
- [ ] Button Download File Asli di Status Berkas
- [ ] Dashboard landing page untuk user (opsional)
- [x] Hapus command `cleanup:temp-files` dan `cleanup:orphan-notifications` — sudah dilakukan
