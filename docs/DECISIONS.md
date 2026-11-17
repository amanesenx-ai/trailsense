# Architecture Decision Records

## ADR-001 — Stack: plain PHP 8 + PostgreSQL, no framework
- Date: 2026-11-17 · Status: accepted
- Context: FEUP hosting runs Apache with PHP and PostgreSQL; the course teaches PHP + PDO + PostgreSQL; the app must be reachable online for later assignments. Frameworks (Laravel) need Composer and a console on the server.
- Decision: plain PHP 8 with PDO (pgsql), a single front controller, no runtime dependencies.
- Consequences: deploys by copying files; well represented in training data so agent output is reliable. We write our own small router, CSRF and auth helpers (kept in `src/Core`, each < 100 lines).

## ADR-002 — Layered architecture with a pure Domain layer
- Date: 2026-11-17 · Status: accepted
- Context: small app, one team member, simple domain except the risk engine, which is the product's differentiator.
- Decision: Controllers → Domain/Weather/Repositories; Domain has no I/O so it can be unit-tested without DB or network.
- Consequences: easy for the agent to follow; risk of blurred layers is controlled by CLAUDE.md rules and the security/UX subagents.

## ADR-003 — Risk rules stored as data (`risk_rule` table)
- Date: 2026-11-17 · Status: accepted
- Context: A1 promised "transparent rules" — users must see *which* rule fired. Clubs may want different thresholds.
- Decision: each rule is a row (metric, comparator, threshold, level, explanation, precaution). The engine is generic.
- Consequences: thresholds change without code; every result references a rule code, which gives traceability (assessment_result).

## ADR-004 — Never invent weather; incomplete beats wrong
- Date: 2026-11-17 · Status: accepted
- Context: Open-Meteo publishes forecasts up to 16 days ahead and may be unreachable.
- Decision: when data is missing the rule result is NOT EVALUATED and the assessment is marked incomplete (AC4.4). Daylight is computed locally (PHP `date_sun_info`) so it is always available. A `DemoWeatherProvider` exists only for local development/demo without internet and is labelled "Demo forecast" in the UI.
- Consequences: honest UI; production config must set `weather_provider = open-meteo`.

## ADR-005 — Store the forecast used (`weather_snapshot`)
- Date: 2026-11-17 · Status: accepted
- Context: forecasts change; a briefing must be explainable later ("why did it say CAUTION?").
- Decision: each assessment stores the values it used, the source and fetch time. Re-checking creates a new assessment.
- Consequences: small extra table; enables the K1 KPI and audit.

## ADR-006 — Route drawn as inline SVG, not a tile map
- Date: 2026-11-17 · Status: accepted
- Context: the A1 mockups show a route map. A tile map (Leaflet + OSM tiles) adds a CDN dependency and is heavy on phones.
- Decision: store the route as a list of lat/lon points and render a scaled SVG line with start/end markers, plus an "Open in OpenStreetMap" link.
- Consequences: works offline and on slow networks; interactive map moved to DEBT.md.

## ADR-007 — Own minimal test runner instead of PHPUnit
- Date: 2026-11-17 · Status: accepted
- Context: no Composer on FEUP; tests must run with one command anywhere PHP runs.
- Decision: `tests/run.php` with `assertSame/assertTrue/assertThrows`; three suites: unit (domain), integration (repositories on `trailsense_test`), http (real requests to `php -S`).
- Consequences: zero setup; fewer features than PHPUnit (no coverage report) — accepted for MVP.

## ADR-008 — Group plan as a 1-to-0..1 table
- Date: 2026-11-17 · Status: accepted
- Context: only organizer plans have group size and meeting point.
- Decision: `group_plan(plan_id PK/FK → hike_plan ON DELETE CASCADE)` instead of nullable columns in hike_plan.
- Consequences: no NULL-heavy rows; the relationship type (one-to-one partial) is explicit in the schema.

## ADR-009 — Cloud hosting: Docker on Render + Neon PostgreSQL; photos in the database
- Date: 2026-11-17 · Status: accepted
- Context: the owner chose not to use the FEUP servers. The app must stay online and reachable for the next assignments, at no cost. Free container hosts have an ephemeral disk (files written at runtime are lost on each deploy/restart).
- Decision: one Docker image (php:8.3-apache + pdo_pgsql, document root `public/`) on a Render free web service; database on Neon's free plan (permanent, not a trial). Configuration comes from environment variables (`DATABASE_URL`, `WEATHER_PROVIDER`, `APP_DEBUG`) when `config/config.php` is absent. Uploaded photos are stored as BYTEA in `activity_photo` and served by the `photo` route (login required). `bin/db-init.php` creates tables and demo data on first boot only.
- Consequences: no secrets in the repository; photos survive redeploys. Render's free service sleeps after 15 min idle and needs about a minute to wake — open the app before a demo. Neon free storage (1 GB) is far above our needs (photos ≤ 3 MB each).
- Update (2026-11-17, alwaysdata): hosting moved to **alwaysdata** (free plan: always on, PHP + PostgreSQL in one account, no Docker needed) because Render's free service sleeps after 15 minutes. The database quota on the free plan is about 1/10 of the 1 GB disk, so uploaded photos are shrunk to ≤ 1600 px JPEG before storing (`Services/PhotoResizer`, also strips EXIF/GPS metadata). The Dockerfile and render.yaml stay as an alternative deployment path.
