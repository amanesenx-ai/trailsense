-- 006 (22 Nov) — keep the demo data current for the live demo.
-- The seed creates plans relative to the day the database was filled, so on the live site they slide into the past.
-- Data-only migration, limited to the four demo accounts (@trailsense.pt) and to plans that are in the past
-- and were never hiked (no activity points to them). Each is moved forward by whole weeks (same weekday),
-- and its old weather check is removed so the plan page fetches a new one for the new date.
-- Real users' data is never touched. To repeat later: php bin/demo-refresh.php
CREATE TEMP TABLE demo_moved ON COMMIT DROP AS
SELECT p.id
FROM hike_plan p JOIN app_user u ON u.id = p.user_id
WHERE u.email IN ('joana@trailsense.pt', 'bruno@trailsense.pt', 'rita@trailsense.pt', 'miguel@trailsense.pt')
  AND p.planned_date < CURRENT_DATE
  AND NOT EXISTS (SELECT 1 FROM activity a WHERE a.plan_id = p.id);

DELETE FROM weather_snapshot WHERE plan_id IN (SELECT id FROM demo_moved);

UPDATE hike_plan p
SET planned_date = p.planned_date + 7 * CEIL((CURRENT_DATE - p.planned_date + 1) / 7.0)::int
WHERE p.id IN (SELECT id FROM demo_moved);
