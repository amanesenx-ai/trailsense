# Architecture Decision Records

Each decision: the context, what I decided, the alternatives I set aside and why, and the consequences. Later decisions that replace an earlier one say so in its status line.

## ADR-001 — Stack: plain PHP 8 + PostgreSQL, no framework
- Date: 2026-11-17 · Status: accepted
- Context: I work with PHP + PDO + PostgreSQL; the app must be online, always on and reachable by other applications, on a simple free host (Apache + PHP + PostgreSQL). Frameworks (Laravel) need Composer and a console on the server.
- Decision: plain PHP 8 with PDO (pgsql), a single front controller, no runtime dependencies.
- Alternatives: Laravel or Symfony (need Composer and a console on the host, much more code than the MVP needs); Node.js or Python (the host's simplest free plan and my own skills are PHP + PostgreSQL); MySQL (PostgreSQL gives CHECK constraints, JSONB and BYTEA used here).
- Consequences: deploys by copying files; well represented in training data so agent output is reliable. We write our own small router, CSRF and auth helpers (kept in `src/Core`, each < 100 lines).

## ADR-002 — Layered architecture with a pure Domain layer
- Date: 2026-11-17 · Status: accepted
- Context: small app, one team member, simple domain except the risk engine, which is the product's differentiator.
- Decision: Controllers → Domain/Weather/Repositories; Domain has no I/O so it can be unit-tested without DB or network.
- Alternatives: MVC with SQL inside the controllers (faster to start, but rules could not be unit-tested without a database); a separate API + front-end framework (two deployments for one small app).
- Consequences: easy for the agent to follow; risk of blurred layers is controlled by CLAUDE.md rules and the security/UX subagents.

## ADR-003 — Risk rules stored as data (`risk_rule` table)
- Date: 2026-11-17 · Status: accepted
- Context: A1 promised "transparent rules" — users must see *which* rule fired. Clubs may want different thresholds.
- Decision: each rule is a row (metric, comparator, threshold, level, explanation, precaution). The engine is generic.
- Alternatives: thresholds as constants in PHP (every change needs a deploy and the user cannot see the rule); a rules engine library (a dependency for a dozen rules).
- Consequences: thresholds change without code; every result references a rule code, which gives traceability (assessment_result).

## ADR-004 — Never invent weather; incomplete beats wrong
- Date: 2026-11-17 · Status: accepted
- Context: Open-Meteo publishes forecasts up to 16 days ahead and may be unreachable.
- Decision: when data is missing the rule result is NOT EVALUATED and the assessment is marked incomplete (AC4.4). Daylight is computed locally (PHP `date_sun_info`) so it is always available. A `DemoWeatherProvider` exists only for local development/demo without internet and is labelled "Demo forecast" in the UI.
- Alternatives: fall back to demo or average weather when the API fails (looks complete but could tell someone it is safe when nobody checked); hide the factor (the user would not know it was not checked).
- Consequences: honest UI; production config must set `weather_provider = open-meteo`.

## ADR-005 — Store the forecast used (`weather_snapshot`)
- Date: 2026-11-17 · Status: accepted
- Context: forecasts change; a briefing must be explainable later ("why did it say CAUTION?").
- Decision: each assessment stores the values it used, the source and fetch time. Re-checking creates a new assessment.
- Alternatives: recompute from the API every time the page opens (the advice could change without the user knowing why, and the briefing could not be explained later).
- Consequences: small extra table; enables the K1 KPI and audit.

## ADR-006 — Route drawn as inline SVG, not a tile map
- Date: 2026-11-17 · Status: superseded — trail page by ADR-010 (interactive map), cards by ADR-018 (static map); the SVG drawing stays as the fallback when the map cannot load
- Context: the A1 mockups show a route map. A tile map (Leaflet + OSM tiles) adds a CDN dependency and is heavy on phones.
- Decision: store the route as a list of lat/lon points and render a scaled SVG line with start/end markers, plus an "Open in OpenStreetMap" link.
- Alternatives: Leaflet map from the start (CDN dependency and heavier pages — adopted later in ADR-010 once the core worked).
- Consequences: works offline and on slow networks; interactive map left for later (done in ADR-010).

## ADR-007 — Own minimal test runner instead of PHPUnit
- Date: 2026-11-17 · Status: accepted
- Context: no Composer at runtime on the host; tests must run with one command anywhere PHP runs.
- Decision: `tests/run.php` with `assertSame/assertTrue/assertThrows`; three suites: unit (domain), integration (repositories on `trailsense_test`), http (real requests to `php -S`).
- Alternatives: PHPUnit via Composer (needs Composer on every machine and in CI; our needs are assert, setup and an HTTP client).
- Consequences: zero setup; fewer features than PHPUnit (no coverage report) — accepted for MVP.

## ADR-008 — Group plan as a 1-to-0..1 table
- Date: 2026-11-17 · Status: accepted
- Context: only organizer plans have group size and meeting point.
- Decision: `group_plan(plan_id PK/FK → hike_plan ON DELETE CASCADE)` instead of nullable columns in hike_plan.
- Alternatives: nullable group columns in hike_plan (many NULL rows and no way to say “only organizers have these” in the schema); a separate event table unrelated to plans (duplicates trail, date and time).
- Consequences: no NULL-heavy rows; the relationship type (one-to-one partial) is explicit in the schema.

## ADR-009 — Hosting on alwaysdata (Apache + PHP + PostgreSQL); photos in the database
- Date: 2026-11-17 · Status: accepted
- Context: the app must stay online, always on and reachable by other applications, at no cost. Free hosts often sleep when idle or have a disk that is wiped on every deploy.
- Decision: alwaysdata free plan — Apache + PHP 8.3 + PostgreSQL in one account, HTTPS, always on, document root `public/`, deployed from GitHub by a scheduled `git pull` every 5 minutes. Configuration from environment variables (`DATABASE_URL`, `DB_PASSWORD`, `WEATHER_PROVIDER`, `APP_DEBUG`), so no secrets in the repository. Uploaded photos are stored as BYTEA in `activity_photo` and served by the `photo` route (login required), shrunk to ≤ 1600 px JPEG first (`Services/PhotoResizer`, which also strips EXIF/GPS metadata) because the free database quota is about 100 MB.
- Alternatives: Render + Neon (tried first — the free service sleeps after 15 minutes); a paid VPS (cost); storing photos on disk (lost on redeploys on container hosts).
- Consequences: one place for code and data; photos survive redeploys. My first attempt (Docker on Render + Neon PostgreSQL) works but Render's free service sleeps after 15 minutes, so it was dropped; `Dockerfile` and `render.yaml` stay as an alternative deployment path (docs/DEPLOY.md).

## ADR-010 — Interactive map with Leaflet + OpenStreetMap tiles (supersedes ADR-006 for the trail page)
- Date: 2026-11-17 · Status: accepted
- Context: my feedback — the "Open in OpenStreetMap" link showed only the start point; A1 mockups show a route map with start and end.
- Decision: Leaflet 1.9.4 loaded from unpkg with Subresource Integrity hashes, only on pages that show a map; OSM standard tiles with the required attribution. Route geometry is real OpenStreetMap data (hiking route relations) stored in `trail.route_points`. The SVG drawing stays as the no-JS / tile-failure fallback.
- Alternatives: Google Maps (API key, billing account and usage limits); MapLibre with vector tiles (heavier library, needs a tile provider); keep only the SVG drawing (users asked for a real map).
- Consequences: one CDN dependency (pinned version + SRI, so a tampered file is refused by the browser). Tile usage follows the OSM tile policy (low traffic, attribution, no bulk download).

## ADR-011 — Real trail catalogue from OpenStreetMap + elevation from Open-Meteo
- Date: 2026-11-17 · Status: accepted
- Context: release 1 used simplified shapes generated around real start points; on a real map they would be wrong.
- Decision: the 12 curated trails are real OSM hiking relations (official PR/GR codes). Geometry was downloaded once from the Overpass API, stitched into one line and simplified (Douglas–Peucker, ~5 m tolerance); distance is computed from the geometry; elevation gain from the Open-Meteo Elevation API; duration estimated with Naismith's rule (4 km/h + 10 min per 100 m of ascent); difficulty from distance and ascent. Stored in seed/migration SQL with the OSM relation id for attribution and the "View on OpenStreetMap" link.
- Alternatives: type trails in by hand (error-prone, no real geometry); the Overpass API (overloaded during the import — 502s and timeouts — so the OSM API relation/{id}/full.json was used).
- Consequences: data is static (no runtime dependency on Overpass); refreshing it is a documented script (tools/README in docs/DATA_MODEL.md).

## ADR-012 — IPMA official warnings and fire risk as advisory rules
- Date: 2026-11-17 · Status: accepted
- Context: I asked for a second API; A1 promised trustworthy risk advice. IPMA (Portuguese met office) publishes district warnings (~3 days ahead) and municipal rural fire risk RCM 1–5 (today and tomorrow) as open JSON.
- Decision: `IpmaProvider` behind an interface, called with a 6 s timeout and cached per request; new metrics `ipma_warning_level` (0 none, 1 yellow, 2 orange, 3 red) and `fire_risk` (1–5) evaluated by rows in `risk_rule`. A new column `risk_rule.required` marks rules whose missing data makes the check incomplete; IPMA rules are `required = false` because the data is legitimately not published yet for later dates.
- Alternatives: show IPMA warnings only as a link (the verdict would ignore official warnings); treat a yellow warning as a blocker (warnings cover a whole district, so a yellow warning only raises the verdict to CAUTION; orange or red gives WARNING).
- Consequences: two independent sources (Open-Meteo forecast, IPMA official); failure of one degrades gracefully.

## ADR-013 — Public read-only REST API `/api/v1`
- Date: 2026-11-17 · Status: accepted
- Context: the app must be reachable by other applications, so that other services (a club or municipality site, future integrations) can reuse trails and the risk check.
- Decision: JSON endpoints in `src/Api/` dispatched by the same front controller (`/api/v1/...` via `.htaccess` rewrite, `?r=api&path=` fallback). Read-only, no authentication, CORS `*`, no personal data. The conditions endpoint runs the same AssessmentService logic without storing anything. Documented in `docs/API.md` + `docs/openapi.yaml`.
- Alternatives: no API (the app would not be reachable by other applications); GraphQL (more machinery than five read-only endpoints need); a writable API (would need tokens and rate limits — kept read-only).
- Consequences: other systems can reuse TrailSense's risk check; write operations stay in the web UI where CSRF and sessions protect them.

## ADR-014 — Versioned SQL migrations
- Date: 2026-11-17 · Status: accepted
- Context: the live database already has data; release 2 changes the schema.
- Decision: `database/schema.sql` is always the full current schema (fresh installs); `database/migrations/NNN_*.sql` upgrade existing databases; table `schema_migration(version)` records what ran. `Core\Installer` applies pending migrations on the first request after a deploy (marker file holds the version).
- Alternatives: re-run schema.sql on deploy (would wipe user data); a migration library (needs Composer).
- Consequences: deploy = push; the live database upgrades itself once, in a transaction per migration.

## ADR-015 — Hike mode runs in the browser; nothing tracked on the server
- Date: 2026-11-18 · Status: accepted
- Context: I asked for on-trail feedback (map, compass, waymark legend). GPS tracking was A1 Priority 1.
- Decision: a dedicated page `?r=hike&id=` using the Geolocation API (watchPosition), DeviceOrientation (compass, iOS permission), Vibration and Screen Wake Lock. Route maths in `public/assets/hike-core.js` (pure functions, tested with Node). The track lives in memory; "Finish" passes only distance and duration to the publish form.
- Alternatives: record the track on the server (location history of users is personal data we do not need); a native mobile app (out of scope; the browser has Geolocation, compass and wake lock).
- Consequences: privacy by design (no location history stored); needs HTTPS (we have it) and permission prompts; accuracy depends on the phone.

## ADR-016 — Map layers: OSM, OpenTopoMap, Waymarked Trails; Referrer-Policy
- Date: 2026-11-18 · Status: accepted
- Context: OSM tiles were blocked ("Access blocked") because our `Referrer-Policy: same-origin` sent no Referer; hikers need contour lines and waymarked routes.
- Decision: `Referrer-Policy: strict-origin-when-cross-origin` (other sites see only our origin, never paths/ids). Leaflet layer control with OSM standard, OpenTopoMap and the Waymarked Trails hiking overlay, each with its attribution.
- Alternatives: Referrer-Policy: same-origin (OSM blocked our tiles without a Referer — see LESSONS); a single base layer (topographic and waymarked layers help on the trail).
- Consequences: we depend on three volunteer tile services — low traffic, attribution shown, no bulk downloads, as their policies ask.

## ADR-017 — Store hourly forecast with the snapshot; visual risk check
- Date: 2026-11-18 · Status: accepted
- Context: I want a visual check and a direct weather link; aggregates alone cannot draw an hour-by-hour view.
- Decision: Open-Meteo request adds `weather_code`; the provider returns hourly rows for the hike window ± 2 h, stored in `weather_snapshot.hourly` (JSONB, migration 003). Charts are server-side SVG (no chart library, works without JS). Links point to the exact API request, the Open-Meteo page for the location, and the IPMA district warnings page.
- Alternatives: store only daily aggregates (cannot draw the hour-by-hour chart or explain which hour triggered a rule).
- Consequences: snapshots are ~1 KB larger; old snapshots without hourly data simply show no chart.

## ADR-018 — Static map thumbnails built on the server
- Date: 2026-11-18 · Status: accepted
- Context: I want a real map image on every card; one Leaflet map per card would be heavy.
- Decision: `Core\StaticMap` picks the zoom that fits the route, and outputs an SVG with the needed OSM tiles (`<image>`) and the route on top. It needs no JS and scales with the card. The tiles are the same public tiles the browser caches.
- Alternatives: one Leaflet map per card (dozens of live maps on one page, slow on phones); a third-party static-map API (key and limits).
- Consequences: about 4–6 tiles per card. Lists are short (≤ 20 cards), within the OSM tile policy for light use. If tiles fail, the route drawing still shows on a plain background.

## ADR-019 — Elevation profiles stored, fetched once from Open-Meteo Elevation
- Date: 2026-11-18 · Status: accepted
- Context: climb notes need altitude along the route; calling an API on every page view is fragile.
- Decision: sample each route every ~1/80 of its length and query the Open-Meteo Elevation API (Copernicus DEM 90 m) once. Store `[km, m]` pairs in `trail.elevation_profile` (JSONB, migration 004). The climb analysis is pure PHP (`Domain\Climb`), and the same data feeds the hike GPS ("next climb").
- Alternatives: fetch elevation on every page view (slow and depends on the API at view time); use the elevation in the OSM data (missing on most routes).
- Consequences: 90 m DEM smooths small bumps, so totals are estimates. The UI says so.

## ADR-020 — Offline trail GPS with a service worker
- Date: 2026-11-20 · Status: accepted
- Context: on the trail there is often no signal; I want real directions offline. Geolocation works without internet, but the page, scripts and map tiles do not.
- Decision: `public/sw.js` caches the app shell (assets + Leaflet), hike pages the user explicitly downloads, and OSM tiles for that route only. Tile list is computed in `hike-core.js` (`tilesForRoute`, tested): zoom 13–16 within a one-tile corridor, max ~600 tiles, fetched 4 at a time. The OSM tile policy forbids bulk downloads and offline caching of large areas or zoom ≥ 17, so we stay small and user-triggered. Signed-out pages clear the page cache.
- Alternatives: a native app with offline maps (out of scope); no offline mode (the trail often has no signal); caching whole regions (forbidden by the OSM tile policy).
- Consequences: works in Chrome/Android and Safari/iOS (service workers + Cache Storage). Offline zoom is capped at 16. Publishing needs a connection; the finished hike waits on the phone.

## ADR-021 — Keep the demo data in the future with a data-only migration
- Date: 2026-11-22 · Status: accepted
- Context: the seed creates plans relative to the day the database was filled; on the live site they slid into the past and the organizer dashboard emptied before the demo.
- Decision: migration `006_refresh_demo_dates.sql` moves past, never-hiked plans of the four demo accounts forward by whole weeks (same weekday) and drops their old weather check; `bin/demo-refresh.php` runs the same SQL again before a demo. Real users' data is never touched.
- Alternatives: reset the whole database before the demo (would delete real users' accounts and posts); edit dates by hand in phpPgAdmin (easy to forget, not repeatable).
- Consequences: no schema change (version row only); tested in `tests/Integration/DemoRefreshTest.php`.

## ADR-022 — Report screenshots are captured on CI
- Date: 2026-11-22 · Status: accepted
- Context: the development sandbox cannot reach the live site or the external photos, maps and weather, so local screenshots showed demo weather and no images.
- Decision: `.github/workflows/screenshots.yml` starts the app on a GitHub runner (throwaway database from `seed.sql`, real Open-Meteo/IPMA, real tiles and photos) and `bin/screenshots.py` captures every screen; the images are committed to the `screenshots` branch and copied to `docs/screenshots/a2/`.
- Alternatives: take screenshots by hand (slow, not repeatable, easy to miss a screen); local screenshots from the development machine (no access to the real weather, maps and photos).
- Consequences: screenshots are reproducible and show real data sources; no live credentials are used.
