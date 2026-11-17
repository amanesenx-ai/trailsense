# DATA_MODEL.md — data tables

Authoritative SQL: `database/schema.sql`. Diagram: `er-diagram.svg`. 10 tables, PostgreSQL 16.

| Table | Primary key | Foreign keys (on delete) | Main constraints | Why it exists |
|---|---|---|---|---|
| app_user | id | — | email UNIQUE; role ∈ {hiker, organizer} | F8 sign-in and roles |
| trail | id | — | distance > 0; difficulty ∈ {easy, moderate, hard}; lat/lon ranges; (name, region) UNIQUE | F1 curated trails |
| hike_plan | id | user_id → app_user (CASCADE); trail_id → trail (RESTRICT) | duration 30–720 | F2 plans |
| group_plan | plan_id | plan_id → hike_plan (CASCADE) | size 2–40; meeting point ≥ 3 chars | F9; **one-to-zero-or-one** (ADR-008) |
| risk_rule | code | — | metric, comparator, level from fixed lists | F4 rules as data (ADR-003) |
| weather_snapshot | id | plan_id → hike_plan (CASCADE) | if available, all four values NOT NULL | F3 forecast used (ADR-005) |
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
