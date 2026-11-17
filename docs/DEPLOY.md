# DEPLOY.md — putting TrailSense online (Render + Neon, free)

Architecture: Docker web service on **Render** (free) + PostgreSQL on **Neon** (free, permanent). See ADR-009.

1. **GitHub** — push this repository (it contains `Dockerfile` and `render.yaml`; `config/config.php` is git-ignored).
2. **Neon** — sign up at neon.com → New project `trailsense`, region *Europe (Frankfurt)*, Postgres 16+ → copy the connection string
   (`postgresql://USER:PASSWORD@ep-…eu-central-1.aws.neon.tech/neondb?sslmode=require`). No need to create tables.
3. **Render** — sign up at render.com with GitHub → New → Blueprint → choose the repository (it reads `render.yaml`)
   → paste the Neon string as `DATABASE_URL` → Apply. First build takes ~5 min.
   On first boot the container runs `bin/db-init.php`, which creates the tables and demo data (only if the database is empty).
4. **Check** — open `https://trailsense-XXXX.onrender.com` → login page. Sign in as `joana@trailsense.pt` / `trailsense123`,
   open a plan in the next 16 days: the source line must say *Forecast: Open-Meteo*.
   `https://…/config/config.php` must give 404 (only `public/` is served).
5. **Updates** — every push to `main` redeploys automatically. Data stays in Neon.

Notes
- Free Render services sleep after 15 min idle; the first request then takes ~1 min. **Open the app a minute before the demo.**
- To reset demo data: in Neon's SQL editor run `database/schema.sql` then `database/seed.sql`.
- Local development is unchanged: `config/config.php` + `php -S localhost:8000 -t public`.
