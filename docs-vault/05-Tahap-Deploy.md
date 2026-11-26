---
tags: [simado, deployment, roadmap, tahap]
---

# 05 - Tahap Deploy (2 & 3)

Catatan ini hanya **peta tahap dan status**, bukan checklist teknis. Checklist teknis tetap satu-satunya di [[01-Deployment-Runbook]]. `TODOS.md` di root tetap untuk technical debt dan PR; nilai `.env` produksi ada di [[04-Production-Env-Example]].

Diperbarui: 2026-11-26.

## Prasyarat

- [x] Tahap 1 selesai: kode, runbook, contoh `.env`, perbaikan keamanan kecil, dan tes (lihat `TODOS.md`).
- [ ] Pastikan commit terakhir sudah di-`push` ke `origin/main` sebelum mulai Tahap 2.

## Tahap 2 — Deploy ke server

**Tujuan:** memasang aplikasi di hosting sehingga bisa diakses lewat HTTPS dan alur dokumen inti berjalan.

**Status:** belum dimulai (2026-11-25).

**Lingkup ringkas** (detail teknis ada di [[01-Deployment-Runbook]], jangan diulang di sini):

1. Server & domain — document root ke `public/`, PHP dan ekstensi, folder writable, SSL/Cloudflare.
2. `.env` di server — mode produksi, HTTPS, database, cookie aman, `LOG_LEVEL=error`.
3. Google OAuth — upload `oauth_client_secret.json` lewat Drive Config, daftarkan redirect URI `https://<domain>/admin/google/callback`.
4. Pasang kode & database — `composer install`, `migrate --force`, `optimize:clear`, `route:cache`, `config:cache`, `view:cache`.
5. Operasional — cron tiap menit, backup + uji restore, kompresi gzip/Brotli, jangan cache HTML dinamis.
6. Uji pasca-deploy — `/up`, login, scan/subfolder, approve/revisi, generate DOCX + PDF, download/preview, ikon/font, log.

**Tanda selesai:** website jalan di HTTPS, alur inti berhasil, cron aktif, backup ada, log bersih.

**Tidak termasuk Tahap 2:** 2FA, uji 50 baris, tes alur lengkap, cache subfolder, dan perbaikan kode lanjutan.

## Gerbang sebelum publikasi

Sebelum website dibuka ke internet, pilih salah satu:

- [ ] 2FA untuk akun admin (PR-09), **atau**
- [ ] Pembatasan akses, misalnya Cloudflare Access / whitelist IP.

**Status:** keputusan belum diambil. Tanpa salah satunya, jangan publikasikan.
Detail checklist teknis produksi (termasuk LibreOffice dan fallback PDF) ada di [[01-Deployment-Runbook]].

## Tahap 3 — Setelah sistem stabil

**Tujuan:** memperkuat keamanan, pengujian, dan kerapian setelah Tahap 2 berjalan.

**Status:** belum dimulai.

- [ ] Putuskan dan terapkan gerbang publikasi di atas (2FA atau pembatasan akses).
- [ ] Uji generate massal 50 baris (PR-16) — waspadai timeout shared hosting.
- [ ] Tambah tes approve/revisi/generate (PR-10).
- [ ] Cache subfolder dashboard (PR-11) — opsional; kunci harus memakai `folder_id`, TTL ≤60 detik, scan tetap ambil data segar.
- [ ] Batasi `trustProxies('*')` ke IP Cloudflare.
- [ ] Validasi format `parent_id` folder Drive.
- [ ] Rapikan path preview & containment download history (risiko rendah).
- [ ] Tidak perlu: sitemap, canonical URL, queue, audit trail, hash dokumen.

Detail alasan dan prioritas ada di `TODOS.md` dan [[02-Decision-Log]]. Rencana pengukuran lanjutan ada di [[03-Testing-Plan]].

## Cara pakai

- Centang progres di dokumen ini bila ingin memantau tahap.
- Update tanggal bila status berubah.
- Jangan memindahkan checklist teknis dari [[01-Deployment-Runbook]] ke sini.
