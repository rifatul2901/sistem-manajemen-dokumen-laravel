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

## Mungkin
- [ ] Cache hasil scan terakhir di session (agar tidak hilang saat pindah menu lalu balik)

### Technical Debt (Perbaikan Kode)
- [ ] [[HIGH]] Ganti `url("admin/approve")` dkk dengan named route (`route('admin.approve')`) — dashboard.blade.js
- [ ] [[HIGH]] `notifikasi_pdf` path traversal: validasi path di `unlink()` — AdminController:202,267
- [ ] [[HIGH]] Sanitasi nama file sebelum dipakai di path PDF — AdminController:235
- [ ] [[HIGH]] DB transaction untuk cascade delete upload — AdminController:267-272
- [ ] [[MEDIUM]] Error feedback toast saat AJAX poin gagal — dashboard.blade.js:607
- [ ] [[MEDIUM]] `approved_at` & `revision_requested_at` di `$fillable` — Upload.php
- [ ] [[LOW]] `upload_time` & `tanggal_kirim_awal` cast `datetime` — Upload.php
- [ ] [[LOW]] `initPoinTooltips()` ganti `setTimeout(50)` pakai requestAnimationFrame
- [ ] [[LOW]] `uploadRevisi()`: bungkus updateFile() + DB ops dalam DB::transaction() untuk cegah desinkron Drive-DB jika ada exception di tengah — DashboardController.php:71-93

## Catatan — Cloudflare

Cloudflare dipasang **saat deployment / production**, bukan sekarang.

| Langkah | Detail |
|---------|--------|
| 1 | Daftar Cloudflare (gratis) |
| 2 | Masukkan domain |
| 3 | Ganti nameserver di registrar domain |
| 4 | Selesai — SSL, CDN, caching otomatis aktif |

Tidak perlu mengubah kode. CDN library (jsdelivr) juga tidak wajib dipindah — bisa tetap dipakai.