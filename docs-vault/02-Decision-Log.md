---
tags: [simado, decision-log, tech-debt]
---

# 02 - Decision Log

Kenapa technical debt di `TODOS.md` diputuskan seperti itu. Tulis tanggal + alasan agar tidak diutak-atik lagi.

## HIGH - Selesai
- `notifikasi_pdf` path traversal (`AdminController:202,267`) — validasi path di `unlink()`. ✅
- 419 CSRF di `doApprove()` — token static `{{ csrf_token() }}` diganti `window.csrfToken` dari meta tag. ✅
- Sanitasi nama file PDF (`AdminController:235`). ✅
- DB transaction untuk cascade delete upload (`AdminController:267-272`). ✅

## MEDIUM - Selesai
- Error feedback alert saat AJAX poin gagal (`dashboard.blade.js:607`). ✅
- `approved_at` & `revision_requested_at` di `casts()` (`Upload.php`) — assign langsung via properti, `$fillable` tidak dibutuhkan. ✅

## LOW - Selesai / Dilewati dengan alasan
- [x] Modal `aria-hidden` focus — `document.activeElement?.blur()` sebelum `.hide()`.
- [x] CSP `unsafe-eval` — TIDAK ditambahkan, tidak ada `eval()` di kode (lebih aman).
- [x] `initPoinTooltips()` — ganti `setTimeout(50)` ke `requestAnimationFrame`.
- [x] `uploadRevisi()` — bungkus DB + `updateFile()` dalam `DB::transaction()`, Drive di paling akhir.
- [ ] `upload_time` & `tanggal_kirim_awal` cast `datetime` — DILEWATI. Format `d-m-Y H:i` konsisten, ubah = risiko regresi tanpa manfaat.

## Aturan tambah log baru
`Tanggal | File:baris | Masalah | Keputusan | Alasan`
