-- TrailSense — PostgreSQL schema (authoritative data model; see docs/DATA_MODEL.md)
SET client_min_messages = warning;
-- Re-runnable: drops and recreates everything.

DROP TABLE IF EXISTS trail_favourite, schema_migration, activity_comment, activity_like, trail_report, hike_participant, trail_photo,
    activity_photo, activity, assessment_result, risk_assessment,
    weather_snapshot, risk_rule, group_plan, hike_plan, trail, app_user CASCADE;

-- Users and roles (F8)
CREATE TABLE app_user (
    id            SERIAL PRIMARY KEY,
    name          VARCHAR(80)  NOT NULL CHECK (char_length(trim(name)) >= 2),
    email         VARCHAR(254) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          VARCHAR(10)  NOT NULL CHECK (role IN ('hiker', 'organizer')),
    created_at    TIMESTAMPTZ  NOT NULL DEFAULT now()
);

-- Curated trails (F1)
CREATE TABLE trail (
    id               SERIAL PRIMARY KEY,
    name             VARCHAR(120) NOT NULL,
    region           VARCHAR(80)  NOT NULL,
    distance_km      NUMERIC(5,1) NOT NULL CHECK (distance_km > 0),
    elevation_gain_m INTEGER      NOT NULL CHECK (elevation_gain_m >= 0),
    est_duration_min INTEGER      NOT NULL CHECK (est_duration_min > 0),
    difficulty       VARCHAR(10)  NOT NULL CHECK (difficulty IN ('easy', 'moderate', 'hard')),
    start_lat        NUMERIC(8,5) NOT NULL CHECK (start_lat BETWEEN -90 AND 90),
    start_lon        NUMERIC(8,5) NOT NULL CHECK (start_lon BETWEEN -180 AND 180),
    route_points     JSONB        NOT NULL,           -- [[lat, lon], ...] real OSM geometry (ADR-011)
    description      TEXT         NOT NULL DEFAULT '',
    ref              VARCHAR(20)  NOT NULL DEFAULT '', -- official code, e.g. PR2 VLG
    osm_relation_id  BIGINT       UNIQUE,              -- OpenStreetMap hiking relation (attribution, map link)
    is_loop          BOOLEAN      NOT NULL DEFAULT FALSE,
    ipma_area        CHAR(3)      NOT NULL DEFAULT 'PTO' CHECK (ipma_area ~ '^[A-Z]{3}$'),  -- IPMA warning district
    coastal          BOOLEAN      NOT NULL DEFAULT FALSE,
    elevation_profile JSONB       NOT NULL DEFAULT '[]', -- [[km, m], ...] Open-Meteo Elevation, 81 samples (ADR-019)
    UNIQUE (name, region)
);

-- Licensed trail photos (F1.3): hot-linked from Wikimedia Commons with attribution
CREATE TABLE trail_photo (
    id          SERIAL PRIMARY KEY,
    trail_id    INTEGER      NOT NULL REFERENCES trail(id) ON DELETE CASCADE,
    position    SMALLINT     NOT NULL DEFAULT 1 CHECK (position >= 1),
    image_url   VARCHAR(500) NOT NULL CHECK (image_url LIKE 'https://upload.wikimedia.org/%'),
    thumb_url   VARCHAR(500) NOT NULL CHECK (thumb_url LIKE 'https://upload.wikimedia.org/%'),
    source_url  VARCHAR(500) NOT NULL,
    author      VARCHAR(200) NOT NULL,
    license     VARCHAR(80)  NOT NULL,
    alt_text    VARCHAR(200) NOT NULL,
    UNIQUE (trail_id, position)
);
CREATE INDEX idx_trail_difficulty ON trail (difficulty);

-- Hiking plans (F2)
CREATE TABLE hike_plan (
    id           SERIAL PRIMARY KEY,
    user_id      INTEGER  NOT NULL REFERENCES app_user(id) ON DELETE CASCADE,
    trail_id     INTEGER  NOT NULL REFERENCES trail(id)    ON DELETE RESTRICT,
    planned_date DATE     NOT NULL,
    start_time   TIME     NOT NULL,
    duration_min INTEGER  NOT NULL CHECK (duration_min BETWEEN 30 AND 720),
    notes        VARCHAR(500) NOT NULL DEFAULT '',
    created_at   TIMESTAMPTZ  NOT NULL DEFAULT now()
);
CREATE INDEX idx_plan_user_date ON hike_plan (user_id, planned_date);

-- Group details, one-to-(zero or one) with hike_plan (F9, ADR-008)
CREATE TABLE group_plan (
    plan_id       INTEGER PRIMARY KEY REFERENCES hike_plan(id) ON DELETE CASCADE,
    group_size    INTEGER      NOT NULL CHECK (group_size BETWEEN 2 AND 40),
    meeting_point VARCHAR(120) NOT NULL CHECK (char_length(trim(meeting_point)) >= 3),
    meeting_time  TIME         NOT NULL
);

-- Participants of organizer group hikes: many-to-many hike_plan × app_user (F9.2)
CREATE TABLE hike_participant (
    plan_id   INTEGER     NOT NULL REFERENCES hike_plan(id) ON DELETE CASCADE,
    user_id   INTEGER     NOT NULL REFERENCES app_user(id)  ON DELETE CASCADE,
    joined_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    PRIMARY KEY (plan_id, user_id)
);

-- Predefined, editable risk rules (F4, ADR-003, ADR-012)
CREATE TABLE risk_rule (
    code        VARCHAR(10)  PRIMARY KEY,
    factor      VARCHAR(10)  NOT NULL CHECK (factor IN ('rain', 'wind', 'heat', 'cold', 'daylight', 'official', 'fire', 'trail')),
    metric      VARCHAR(30)  NOT NULL CHECK (metric IN ('precip_total_mm', 'gust_max_kmh', 'temp_max_c',
                                                         'temp_min_c', 'daylight_margin_min',
                                                         'ipma_warning_level', 'fire_risk', 'trail_closed')),
    comparator  VARCHAR(2)   NOT NULL CHECK (comparator IN ('>=', '<=', '<')),
    threshold   NUMERIC(6,1) NOT NULL,
    level       VARCHAR(7)   NOT NULL CHECK (level IN ('caution', 'warning')),
    name        VARCHAR(80)  NOT NULL,
    precaution  VARCHAR(200) NOT NULL,
    active      BOOLEAN      NOT NULL DEFAULT TRUE,
    required    BOOLEAN      NOT NULL DEFAULT TRUE    -- false = advisory: missing data does not make the check incomplete
);

-- Forecast values actually used by an assessment (F3, ADR-005)
CREATE TABLE weather_snapshot (
    id              SERIAL PRIMARY KEY,
    plan_id         INTEGER     NOT NULL REFERENCES hike_plan(id) ON DELETE CASCADE,
    source          VARCHAR(20) NOT NULL CHECK (source IN ('open-meteo', 'demo', 'none')),
    fetched_at      TIMESTAMPTZ NOT NULL DEFAULT now(),
    available       BOOLEAN     NOT NULL,
    message         VARCHAR(200) NOT NULL DEFAULT '',
    precip_total_mm NUMERIC(5,1),
    gust_max_kmh    NUMERIC(5,1),
    temp_min_c      NUMERIC(4,1),
    temp_max_c      NUMERIC(4,1),
    sunset          TIME,
    alerts_source   VARCHAR(10) NOT NULL DEFAULT 'none' CHECK (alerts_source IN ('ipma', 'demo', 'none')),
    warning_level   SMALLINT    CHECK (warning_level BETWEEN 0 AND 3),     -- IPMA: 0 none … 3 red; NULL not published
    warnings        JSONB       NOT NULL DEFAULT '[]',
    fire_risk       SMALLINT    CHECK (fire_risk BETWEEN 1 AND 5),         -- IPMA RCM; NULL not published
    alerts_message  VARCHAR(300) NOT NULL DEFAULT '',
    hourly          JSONB       NOT NULL DEFAULT '[]',                    -- hike window ± 2 h (ADR-017)
    CHECK (available = FALSE OR (precip_total_mm IS NOT NULL AND gust_max_kmh IS NOT NULL
                                 AND temp_min_c IS NOT NULL AND temp_max_c IS NOT NULL))
);

-- One evaluation of a plan against all active rules (F4)
CREATE TABLE risk_assessment (
    id            SERIAL PRIMARY KEY,
    plan_id       INTEGER    NOT NULL REFERENCES hike_plan(id)        ON DELETE CASCADE,
    snapshot_id   INTEGER    NOT NULL UNIQUE REFERENCES weather_snapshot(id) ON DELETE CASCADE,
    overall_level VARCHAR(7) NOT NULL CHECK (overall_level IN ('ok', 'caution', 'warning')),
    is_complete   BOOLEAN    NOT NULL,
    created_at    TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE INDEX idx_assessment_plan ON risk_assessment (plan_id, created_at DESC);

-- Many-to-many association class: assessment × rule, with attributes (F4/F5)
CREATE TABLE assessment_result (
    assessment_id  INTEGER     NOT NULL REFERENCES risk_assessment(id) ON DELETE CASCADE,
    rule_code      VARCHAR(10) NOT NULL REFERENCES risk_rule(code)    ON DELETE RESTRICT ON UPDATE CASCADE,
    observed_value NUMERIC(6,1),
    evaluated      BOOLEAN     NOT NULL,
    triggered      BOOLEAN     NOT NULL,
    PRIMARY KEY (assessment_id, rule_code),
    CHECK (evaluated OR NOT triggered)
);

-- Published hikes (F6/F7)
CREATE TABLE activity (
    id           SERIAL PRIMARY KEY,
    user_id      INTEGER NOT NULL REFERENCES app_user(id)  ON DELETE CASCADE,
    trail_id     INTEGER NOT NULL REFERENCES trail(id)     ON DELETE RESTRICT,
    plan_id      INTEGER          REFERENCES hike_plan(id) ON DELETE SET NULL,
    hiked_on     DATE    NOT NULL,
    distance_km  NUMERIC(5,1) NOT NULL CHECK (distance_km > 0 AND distance_km <= 200),
    duration_min INTEGER NOT NULL CHECK (duration_min BETWEEN 10 AND 1440),
    notes        VARCHAR(1000) NOT NULL DEFAULT '',
    created_at   TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE INDEX idx_activity_created ON activity (created_at DESC);

-- Community trail condition reports (F10)
CREATE TABLE trail_report (
    id         SERIAL PRIMARY KEY,
    trail_id   INTEGER     NOT NULL REFERENCES trail(id)    ON DELETE CASCADE,
    user_id    INTEGER     NOT NULL REFERENCES app_user(id) ON DELETE CASCADE,
    condition  VARCHAR(10) NOT NULL CHECK (condition IN ('good', 'muddy', 'obstacle', 'overgrown', 'closed')),
    note       VARCHAR(300) NOT NULL DEFAULT '',
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
CREATE INDEX idx_report_trail_date ON trail_report (trail_id, created_at DESC);

-- Likes (many-to-many) and comments on published hikes (F6.2)
CREATE TABLE activity_like (
    activity_id INTEGER     NOT NULL REFERENCES activity(id) ON DELETE CASCADE,
    user_id     INTEGER     NOT NULL REFERENCES app_user(id) ON DELETE CASCADE,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT now(),
    PRIMARY KEY (activity_id, user_id)
);
CREATE TABLE activity_comment (
    id          SERIAL PRIMARY KEY,
    activity_id INTEGER      NOT NULL REFERENCES activity(id) ON DELETE CASCADE,
    user_id     INTEGER      NOT NULL REFERENCES app_user(id) ON DELETE CASCADE,
    body        VARCHAR(500) NOT NULL CHECK (char_length(trim(body)) >= 1),
    created_at  TIMESTAMPTZ  NOT NULL DEFAULT now()
);
CREATE INDEX idx_comment_activity ON activity_comment (activity_id, created_at);

CREATE TABLE activity_photo (
    id          SERIAL PRIMARY KEY,
    activity_id INTEGER      NOT NULL REFERENCES activity(id) ON DELETE CASCADE,
    file_name   VARCHAR(80)  NOT NULL UNIQUE,                -- random public name, never the user's
    mime_type   VARCHAR(20)  NOT NULL CHECK (mime_type IN ('image/jpeg', 'image/png', 'image/webp')),
    content     BYTEA        NOT NULL CHECK (octet_length(content) <= 3145728),   -- stored in DB (ADR-009)
    alt_text    VARCHAR(150) NOT NULL DEFAULT ''
);

-- Applied migrations (ADR-014). A fresh install from this file is at the latest version.
CREATE TABLE schema_migration (
    version    INTEGER PRIMARY KEY,
    applied_at TIMESTAMPTZ NOT NULL DEFAULT now()
);
-- Saved trails (release 5, AC1.18): many-to-many app_user <-> trail
CREATE TABLE trail_favourite (
    user_id    INTEGER     NOT NULL REFERENCES app_user (id) ON DELETE CASCADE,
    trail_id   INTEGER     NOT NULL REFERENCES trail (id) ON DELETE CASCADE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    PRIMARY KEY (user_id, trail_id)
);
CREATE INDEX trail_favourite_trail ON trail_favourite (trail_id);

INSERT INTO schema_migration (version) VALUES (1), (2), (3), (4), (5);
