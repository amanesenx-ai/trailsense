-- Release 3 (2026-11-18): hourly forecast stored with each snapshot for the visual risk check (ADR-017).
ALTER TABLE weather_snapshot ADD COLUMN hourly JSONB NOT NULL DEFAULT '[]';
