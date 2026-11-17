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

## ADR-010 — Interactive map with Leaflet + OpenStreetMap tiles (supersedes ADR-006 for the trail page)
- Date: 2026-11-17 · Status: accepted
- Context: owner feedback — the "Open in OpenStreetMap" link showed only the start point; A1 mockups show a route map with start and end.
- Decision: Leaflet 1.9.4 loaded from unpkg with Subresource Integrity hashes, only on pages that show a map; OSM standard tiles with the required attribution. Route geometry is real OpenStreetMap data (hiking route relations) stored in `trail.route_points`. The SVG drawing stays as the no-JS / tile-failure fallback.
- Consequences: one CDN dependency (pinned version + SRI, so a tampered file is refused by the browser). Tile usage follows the OSM tile policy (low traffic, attribution, no bulk download).

## ADR-011 — Real trail catalogue from OpenStreetMap + elevation from Open-Meteo
- Date: 2026-11-17 · Status: accepted
- Context: release 1 used simplified shapes generated around real start points; on a real map they would be wrong.
- Decision: the 12 curated trails are real OSM hiking relations (official PR/GR codes). Geometry was downloaded once from the Overpass API, stitched into one line and simplified (Douglas–Peucker, ~5 m tolerance); distance is computed from the geometry; elevation gain from the Open-Meteo Elevation API; duration estimated with Naismith's rule (4 km/h + 10 min per 100 m of ascent); difficulty from distance and ascent. Stored in seed/migration SQL with the OSM relation id for attribution and the "View on OpenStreetMap" link.
- Consequences: data is static (no runtime dependency on Overpass); refreshing it is a documented script (tools/README in docs/DATA_MODEL.md).

## ADR-012 — IPMA official warnings and fire risk as advisory rules
- Date: 2026-11-17 · Status: accepted
- Context: owner asked for a second API; A1 promised trustworthy risk advice. IPMA (Portuguese met office) publishes district warnings (~3 days ahead) and municipal rural fire risk RCM 1–5 (today and tomorrow) as open JSON.
- Decision: `IpmaProvider` behind an interface, called with a 6 s timeout and cached per request; new metrics `ipma_warning_level` (0 none, 1 yellow, 2 orange, 3 red) and `fire_risk` (1–5) evaluated by rows in `risk_rule`. A new column `risk_rule.required` marks rules whose missing data makes the check incomplete; IPMA rules are `required = false` because the data is legitimately not published yet for later dates.
- Consequences: two independent sources (Open-Meteo forecast, IPMA official); failure of one degrades gracefully.

## ADR-013 — Public read-only REST API `/api/v1`
- Date: 2026-11-17 · Status: accepted
- Context: Assignment 2 §3 — the app must be reachable by other applications in the next assignments.
- Decision: JSON endpoints in `src/Api/` dispatched by the same front controller (`/api/v1/...` via `.htaccess` rewrite, `?r=api&path=` fallback). Read-only, no authentication, CORS `*`, no personal data. The conditions endpoint runs the same AssessmentService logic without storing anything. Documented in `docs/API.md` + `docs/openapi.yaml`.
- Consequences: other systems can reuse TrailSense's risk check; write operations stay in the web UI where CSRF and sessions protect them.

## ADR-014 — Versioned SQL migrations
- Date: 2026-11-17 · Status: accepted
- Context: the live database already has data; release 2 changes the schema.
- Decision: `database/schema.sql` is always the full current schema (fresh installs); `database/migrations/NNN_*.sql` upgrade existing databases; table `schema_migration(version)` records what ran. `Core\Installer` applies pending migrations on the first request after a deploy (marker file holds the version).
- Consequences: deploy = push; the live database upgrades itself once, in a transaction per migration.
