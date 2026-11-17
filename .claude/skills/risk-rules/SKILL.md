---
name: risk-rules
description: How TrailSense weather/daylight risk rules work and how to add or change one. Use when touching risk_rule, RiskEngine, AssessmentService or the plan page.
---
# Risk rules
- Rules are rows in `risk_rule` (code, factor, metric, comparator, threshold, level, name, precaution). Never hard-code thresholds in PHP (ADR-003).
- Metrics the engine understands: `precip_total_mm`, `gust_max_kmh`, `temp_max_c`, `temp_min_c`, `daylight_margin_min`.
  A new metric needs: a value in AssessmentService::assess, a label in RiskEngine::METRIC_LABELS, the CHECK list in schema.sql.
- Comparators: `>=`, `<=`, `<`. Levels: `caution` < `warning`. Overall = worst triggered level.
- Missing metric → result NOT EVALUATED and assessment incomplete. Never default a missing value to 0 (ADR-004).
- Precautions may use `{latest_start}` (sunset − 30 min − duration, rounded down to 5 min).
- To add a rule: INSERT in database/seed.sql, add a unit test in tests/Unit/RiskEngineTest.php for the boundary value
  (exactly at threshold and just below), update the table in SPEC.md §7.
