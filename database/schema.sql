-- TrailSense — PostgreSQL schema (authoritative data model; see docs/DATA_MODEL.md)
SET client_min_messages = warning;
-- Re-runnable: drops and recreates everything.

DROP TABLE IF EXISTS activity_photo, activity, assessment_result, risk_assessment,
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
    route_points     JSONB        NOT NULL,           -- [[lat, lon], ...] drawn as SVG (ADR-006)
    description      TEXT         NOT NULL DEFAULT '',
    UNIQUE (name, region)
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

-- Predefined, editable risk rules (F4, ADR-003)
CREATE TABLE risk_rule (
    code        VARCHAR(10)  PRIMARY KEY,
    factor      VARCHAR(10)  NOT NULL CHECK (factor IN ('rain', 'wind', 'heat', 'cold', 'daylight')),
    metric      VARCHAR(30)  NOT NULL CHECK (metric IN ('precip_total_mm', 'gust_max_kmh', 'temp_max_c',
                                                         'temp_min_c', 'daylight_margin_min')),
    comparator  VARCHAR(2)   NOT NULL CHECK (comparator IN ('>=', '<=', '<')),
    threshold   NUMERIC(6,1) NOT NULL,
    level       VARCHAR(7)   NOT NULL CHECK (level IN ('caution', 'warning')),
    name        VARCHAR(80)  NOT NULL,
    precaution  VARCHAR(200) NOT NULL,
    active      BOOLEAN      NOT NULL DEFAULT TRUE
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

CREATE TABLE activity_photo (
    id          SERIAL PRIMARY KEY,
    activity_id INTEGER      NOT NULL REFERENCES activity(id) ON DELETE CASCADE,
    file_name   VARCHAR(80)  NOT NULL UNIQUE,
    alt_text    VARCHAR(150) NOT NULL DEFAULT ''
);
