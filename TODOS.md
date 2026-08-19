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
