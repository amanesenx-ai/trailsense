-- Release 5 (2026-11-19): saved trails (AC1.18).
CREATE TABLE trail_favourite (
    user_id    INTEGER     NOT NULL REFERENCES app_user (id) ON DELETE CASCADE,
    trail_id   INTEGER     NOT NULL REFERENCES trail (id) ON DELETE CASCADE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
    PRIMARY KEY (user_id, trail_id)
);
CREATE INDEX trail_favourite_trail ON trail_favourite (trail_id);
