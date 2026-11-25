# docs-vault — Vault Obsidian untuk SIMADO

Folder ini = vault Obsidian. Buka via Obsidian > Open folder as vault > pilih `docs-vault`.

## File
- `00-Index.md` — peta isi
- `01-Deployment-Runbook.md` — satu-satunya sumber checklist deployment
- `02-Decision-Log.md` — dari Technical Debt
- `03-Testing-Plan.md` — dari Rencana Testing
- `04-Production-Env-Example.md` — contoh nilai `.env` produksi (placeholder, bukan checklist)
- `05-Tahap-Deploy.md` — peta tahap deploy 2 & 3 (status, bukan checklist teknis)

## Aturan
1. Jangan taruh `.env`, `*.json` secret, `vendor/`, `storage/` di sini.
2. `TODOS.md` di root hanya untuk technical debt/PR; checklist deployment ada di vault ini.
3. Plugin disarankan: `Git`, `Tasks`.

## Git
Vault ini ikut ke-commit dengan repo Laravel (markdown saja).
Cache Obsidian (`.obsidian/workspace*`, `.obsidian/cache`) di-ignore, lihat `.gitignore`.
