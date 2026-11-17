# CLAUDE.md — TrailSense project rules

Read SPEC.md (what to build) and docs/ARCHITECTURE.md (where code goes) before changing anything.
The owner decides scope and architecture; you implement against them. If a request conflicts with SPEC.md, stop and ask.

## Stack (fixed — see docs/DECISIONS.md ADR-001)
PHP 8.x (no framework, no Composer at runtime) · PostgreSQL 16/17 · HTML + one CSS file + minimal vanilla JS · Apache (alwaysdata) · Leaflet 1.9.4 via CDN with SRI (ADR-010).

## Commands
- Start DB (local): `pg_ctl -D /tmp/pgdata -o "-k /tmp" start`
- Reset DB: `php bin/db-reset.php` (runs database/schema.sql + database/seed.sql; uses config/config.php)
- Run app: `php -S localhost:8000 -t public`
- Run all tests: `php tests/run.php`  (must print `FAILED: 0`)  · one suite: `php tests/run.php unit|integration|http`
- Test DB is `trailsense_test`, set in config/config.php `db_test`; tests reset it themselves.
- Schema change = edit `database/schema.sql` **and** add `database/migrations/NNN_name.sql` (+ bump the version row at the end of schema.sql). The live DB upgrades itself on the next request (Core/Installer).
- Public API lives in `src/Api` (read-only JSON, no personal data); document every endpoint in docs/API.md + docs/openapi.yaml.

## Architecture rules (layered)
- `public/index.php` is the only entry point. Routes are declared in `src/routes.php` (`?r=name`).
- Controllers (`src/Controllers`): read request, call services/repositories, pick a view. No SQL, no HTML.
- Domain (`src/Domain`): pure PHP, no DB, no globals, no I/O — this is what unit tests cover (RiskEngine, validators, briefing).
- Repositories (`src/Repositories`): the only place with SQL. Always PDO prepared statements with placeholders.
- Weather (`src/Weather`): behind the `WeatherProvider` interface. Never invent forecast data in production (`weather_provider = open-meteo`).
- Views (`views/`): templates only; every dynamic value printed with `e()`.
- Keep files under ~250 lines. A new concern gets a new file, not a bigger one.

## Coding rules
- `declare(strict_types=1);` at the top of every PHP file in src/.
- Validation happens on the server even if the HTML has `required`.
- Every POST handler: `Csrf::check()` first, then `Auth::require…()`, then validate, then act, then redirect (PRG).
- Ownership/role checks in the controller before any write (a hiker cannot touch another user's plan).
- Risk thresholds live in the `risk_rule` table, never hard-coded in PHP.
- Dates/times: timezone `Europe/Lisbon`; DB columns are DATE/TIME/TIMESTAMPTZ, not text.
- Secrets only in `config/config.php` (git-ignored). Never print exceptions to users; log them.

## Definition of done for any change
1. Acceptance criterion in SPEC.md identified (write its ID in the commit message, e.g. `F4 AC4.4`).
2. Test written or updated first; `php tests/run.php` passes with `FAILED: 0`.
3. Screen checked at 360 px and desktop; docs/TRACEABILITY.md updated if a requirement maps to new code/tests.
4. Show the test output as evidence — do not just say "it works".

## Do not
- Add features from SPEC.md §3 "Out of scope".
- Add dependencies, frameworks or CDNs without an ADR in docs/DECISIONS.md.
- Edit database/schema.sql without updating docs/DATA_MODEL.md and the integration tests.
