# DEPLOY.md — TrailSense online (alwaysdata, free)

**Live app:** https://amanesenx.alwaysdata.net · **Repository:** https://github.com/amanesenx-ai/trailsense

## How it is set up (ADR-009)
| Piece | Setting |
|---|---|
| Host | alwaysdata free plan (Paris): Apache + PHP 8.3 + PostgreSQL 17, always on, HTTPS |
| Site | `amanesenx.alwaysdata.net`, type PHP 8.3, root directory `www/trailsense/public/`, *Force HTTPS* on |
| Code | scheduled task every 5 min: `git clone` the first time, then `git fetch origin && git reset --hard origin/main` (the server always matches GitHub, also after a history rewrite) → **every push to `main` is live within 5 minutes** |
| Database | `amanesenx_trailsense` on `postgresql-amanesenx.alwaysdata.net`, user `amanesenx` |
| Config | site *environment variables*: `DATABASE_URL` (no password), `DB_PASSWORD`, `WEATHER_PROVIDER=open-meteo`, `APP_DEBUG=0`. No secrets in Git. |
| First run | `Core\Installer` creates tables + demo data on the first request, only if the database is empty; never touches existing data |

Only `public/` is web-reachable; `src/`, `config/`, `database/`, `tests/`, `docs/` are outside the document root (checked: 404).

## Operations
- **Deploy a change:** commit + push to `main`; wait ≤ 5 min. Task log: alwaysdata admin → Advanced → Scheduled tasks → Logs.
- **Keep the demo plans upcoming:** migration 006 already ran on the live database (ADR-021); to repeat, run `php bin/demo-refresh.php` over SSH.
- **Reset the demo data:** in phpPgAdmin (alwaysdata → Databases → PostgreSQL) run `database/schema.sql` then `database/seed.sql`.
- **CI:** `.github/workflows/tests.yml` runs the whole suite on every push; `screenshots.yml` captures the report screenshots (ADR-022).
- **Debug:** temporarily set `APP_DEBUG=1` in the site environment; PHP errors are in alwaysdata → Advanced → Logs.

## Alternative path (not in use)
`Dockerfile` + `render.yaml` deploy the same app to Render + Neon. Not used because Render's free service sleeps after 15 minutes.
