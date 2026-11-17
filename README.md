# TrailSense — MVP (ASI Assignment 2)

Hikers and club organizers find a trail, plan a hike, get **weather warnings explained by explicit rules with a precaution**, and share the hike with a community.
Built with an LLM agent following the course workflow: architecture and spec written first, rules in CLAUDE.md, tests before acceptance.

**Live app:** `https://<render-url>` (Render + Neon, see docs/DEPLOY.md) · **Demo accounts** (password `trailsense123`): `joana@trailsense.pt` (hiker), `bruno@trailsense.pt` (organizer)

## Stack
PHP 8 (no framework) · PostgreSQL · HTML/CSS · minimal JS · Open-Meteo forecast API. Layered architecture — see docs/ARCHITECTURE.md.

## Run locally
```bash
cp config/config.example.php config/config.php   # set DB credentials, base_url '/', weather_provider
php bin/db-reset.php                             # schema + seed
php -S localhost:8000 -t public                  # open http://localhost:8000
php tests/run.php                                # 66 tests: unit, integration, http
```

## Where to look (assessment criteria)
| Criterion | Evidence |
|---|---|
| Core features end to end | F1–F9 + K1 in SPEC.md; flows tested in tests/Http/FlowsTest.php |
| Data tables | database/schema.sql · docs/DATA_MODEL.md · docs/er-diagram.svg |
| Testing | tests/ (66 automated) · docs/MANUAL_TESTS.md · docs/TRACEABILITY.md |
| LLM good practices | CLAUDE.md, AGENTS.md, SPEC.md, docs/ARCHITECTURE.md, docs/DECISIONS.md (ADRs), .claude/ (subagents, command, skill, hooks), docs/LESSONS.md, docs/DEBT.md |
| UX/UI | docs/UX_REVIEW.md · docs/screenshots/ |
| Online | docs/DEPLOY.md |
