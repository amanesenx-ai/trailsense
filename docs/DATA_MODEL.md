# DATA_MODEL.md — data tables

Authoritative SQL: `database/schema.sql` (+ `database/migrations/`). Diagram: `er-diagram.svg`. 16 tables, PostgreSQL 16/17.

| Table | Primary key | Foreign keys (on delete) | Main constraints | Why it exists |
|---|---|---|---|---|
| app_user | id | — | email UNIQUE; role ∈ {hiker, organizer} | F8 sign-in and roles |
| trail_photo | id | trail_id → trail (CASCADE) | (trail_id, position) UNIQUE; URLs only from upload.wikimedia.org | F1.3 licensed photos with author/licence |
| hike_participant | (plan_id, user_id) | plan_id → hike_plan (CASCADE); user_id → app_user (CASCADE) | composite PK = one join per user | F9.2 **many-to-many** users × group hikes |
| trail_report | id | trail_id → trail (CASCADE); user_id → app_user (CASCADE) | condition ∈ {good, muddy, obstacle, overgrown, closed} | F10 community reports |
| activity_like | (activity_id, user_id) | → activity, → app_user (CASCADE) | composite PK = one like per user | F6.2 likes (many-to-many) |
| activity_comment | id | → activity, → app_user (CASCADE) | body 1–500 chars | F6.2 comments |
| schema_migration | version | — | — | ADR-014 applied migrations |
| trail | id | — | distance > 0; difficulty ∈ {easy, moderate, hard}; lat/lon ranges; (name, region) UNIQUE; osm_relation_id UNIQUE; ipma_area 3 letters | F1 real OSM trails (ADR-011) |
| hike_plan | id | user_id → app_user (CASCADE); trail_id → trail (RESTRICT) | duration 30–720 | F2 plans |
| group_plan | plan_id | plan_id → hike_plan (CASCADE) | size 2–40; meeting point ≥ 3 chars | F9; **one-to-zero-or-one** (ADR-008) |
| risk_rule | code | — | metric, comparator, level from fixed lists; `required` (advisory rules) | F4 rules as data (ADR-003, ADR-012) |
| weather_snapshot | id | plan_id → hike_plan (CASCADE) | if available, all four values NOT NULL; warning_level 0–3, fire_risk 1–5 | F3/F4.2 forecast + IPMA values used (ADR-005); `hourly` JSONB = hike window ± 2 h (time, temp, precip, gust, WMO code, in_window) for the visual check (ADR-017, migration 003) |
| risk_assessment | id | plan_id → hike_plan (CASCADE); snapshot_id → weather_snapshot UNIQUE (CASCADE) | level ∈ {ok, caution, warning} | F4 one evaluation |
| assessment_result | (assessment_id, rule_code) | assessment_id → risk_assessment (CASCADE); rule_code → risk_rule (RESTRICT, ON UPDATE CASCADE) | `evaluated OR NOT triggered` | F4/F5 **many-to-many association class** with attributes |
| activity | id | user_id → app_user (CASCADE); trail_id → trail (RESTRICT); plan_id → hike_plan (**SET NULL**) | distance 0–200; duration 10–1440 | F6/F7 published hikes |
| activity_photo | id | activity_id → activity (CASCADE) | file_name UNIQUE; mime_type ∈ {jpeg, png, webp}; content BYTEA ≤ 3 MB | F7 photos (1:N), bytes stored in the DB (ADR-009) |

## Relationship patterns used (Class 4)
- Many-to-one total: hike_plan → app_user, hike_plan → trail (NOT NULL FK).
- Many-to-one partial: activity → hike_plan (nullable FK, SET NULL keeps the post if the plan is deleted).
- One-to-one partial: group_plan → hike_plan (FK is also the PK).
- One-to-one total: risk_assessment ↔ weather_snapshot (NOT NULL + UNIQUE FK).
- Many-to-many with attributes: risk_assessment × risk_rule through assessment_result (composite PK).

## Referential action choices
- CASCADE where the child has no meaning without the parent (a group without its plan, results without their assessment).
- RESTRICT on trail and risk_rule: curated reference data must not disappear while plans/results point to it.
- SET NULL on activity.plan_id: a published hike stays in the feed if its plan is deleted.

## Normalisation notes
- No derived values stored except the forecast snapshot, which is stored on purpose (the forecast changes; we must explain past advice).
- Route points are JSONB (a single, always-read-together value, not queried by point) — ADR-006.
- Time values are DATE/TIME/TIMESTAMPTZ, never text.

## Trail data pipeline (ADR-011)
`database/source/routes.json` (OSM geometry, encoded and decoded once) + `database/source/photos.json` (Wikimedia selection)
→ `bin/gen-trail-data.py` → `database/seed.sql` (fresh installs) and `database/migrations/002_release2.sql` (upgrade).
Geometry: OpenStreetMap API `relation/{id}/full`, ways joined (ordered or nearest-endpoint), Douglas–Peucker 5 m.
Elevation gain: 100 samples from the Open-Meteo Elevation API. Duration: Naismith (4 km/h + 10 min per 100 m up).

**Release 4 (migration 004):** `trail.elevation_profile JSONB NOT NULL DEFAULT '[]'` = 81 `[km, m]` samples from the Open-Meteo Elevation API (ADR-019); `trail.elevation_gain_m` recomputed from that profile so lists and climb notes agree.

**Release 5 (migration 005):** `trail_favourite (user_id FK→app_user ON DELETE CASCADE, trail_id FK→trail ON DELETE CASCADE, created_at, PRIMARY KEY (user_id, trail_id))` — saved trails, a many-to-many between users and trails. Total: **17 tables**.
