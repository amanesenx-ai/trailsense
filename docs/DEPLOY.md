# DEPLOY.md — putting TrailSense online (FEUP)

1. **Database (PostgreSQL at FEUP).** Use the account the teachers gave you (host `db.fe.up.pt`, port 5432).
   Load the tables: `psql -h db.fe.up.pt -U <user> -d <db> -f database/schema.sql -f database/seed.sql`
   (if you were given a schema instead of a database, run `SET search_path TO <schema>;` first and put the schema name in config).
2. **Files.** Copy the whole `trailsense/` folder into `public_html/` of your FEUP account (SFTP to `gnomo.fe.up.pt` or the FEUP file share).
3. **Config.** On the server: `cp config/config.example.php config/config.php`, then set
   - `db.dsn` = `pgsql:host=db.fe.up.pt;port=5432;dbname=<db>`, `db.user`, `db.password`, `db.schema`
   - `base_url` = `/~<your-up-number>/trailsense/public/`
   - `weather_provider` = `open-meteo`, `debug` = `false`
4. **Permissions.** `chmod 755 public/uploads` (Apache must write photos there). `.htaccess` files already block `src/ config/ database/ tests/ docs/ views/ bin/` and stop PHP running in `uploads/`.
5. **Check.** Open `https://paginas.fe.up.pt/~<up>/trailsense/` → login page. Sign in as Joana, open a plan in the next 16 days and confirm the source line says *Forecast: Open-Meteo*. Try `.../trailsense/config/config.php` → must be 403/404.
6. If the server cannot reach `api.open-meteo.com`, the app shows "The weather service did not answer" and marks checks incomplete (by design, ADR-004). Ask the teachers about outbound HTTPS; for a rehearsal only you may set `weather_provider` to `demo` (screens then say *Demo forecast*).

Alternative hosting (own responsibility): any Apache/Nginx + PHP 8.1+ with `pdo_pgsql` and `curl`, document root `public/`.
