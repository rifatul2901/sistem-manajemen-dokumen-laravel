---
tags: [simado, deployment, env, placeholder]
---

# 04 - Contoh Variabel Produksi (Placeholder)

> **Peringatan:** dokumen ini hanya **referensi nilai**, bukan checklist deploy.
> Checklist tetap di [[01-Deployment-Runbook]]. Jangan menyalin file ini menjadi `.env`
> lokal, dan jangan pernah mengisi secret asli di repo. `.env` produksi diisi langsung
> di server saat deploy.

```dotenv
APP_NAME=SIMADO
APP_ENV=production
APP_KEY=<HASIL_key_generate_DI_SERVER>
APP_DEBUG=false
APP_URL=https://<domain-anda>
APP_TIMEZONE=Asia/Makassar

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=<host-database>
DB_PORT=3306
DB_DATABASE=<nama_database>
DB_USERNAME=<user_database>
DB_PASSWORD=<password_database>

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

CACHE_STORE=database
QUEUE_CONNECTION=database

MAIL_MAILER=<smtp_atau_log>
MAIL_HOST=<host_smtp>
MAIL_PORT=<port_smtp>
MAIL_USERNAME=<user_smtp>
MAIL_PASSWORD=<password_smtp>
MAIL_FROM_ADDRESS=<alamat_pengirim>
MAIL_FROM_NAME="SIMADO"

BCRYPT_ROUNDS=12
```

Catatan penting:

- `SESSION_SECURE_COOKIE=true` hanya untuk server HTTPS. Di `http://localhost` cookie secure
  membuat login gagal, jadi jangan disetel di `.env` lokal.
- `APP_URL` harus sama dengan domain yang didaftarkan di Google Cloud Console.
  Redirect URI OAuth = `https://<domain-anda>/admin/google/callback`.
- `oauth_client_secret.json` di-upload lewat halaman Drive Config, bukan ditaruh di repo
  (file asli tidak masuk Git).

## Catatan penggunaan
Nilai di atas dipakai saat mengisi `.env` **di server**, bukan di repo lokal. Dua hal yang sering terlewat:

- `APP_KEY` harus diisi dari `php artisan key:generate` di server.
- Setelah cache Laravel aktif, setiap perubahan `.env` wajib diikuti `php artisan optimize:clear`;
  kalau tidak, nilai lama tetap terpakai.

Urutan lengkap deploy (migrasi, cache, cron, uji) ada di [[01-Deployment-Runbook]] — dokumen ini hanya referensi nilai.

## Lihat juga
- [[01-Deployment-Runbook]]
- [[02-Decision-Log]]
