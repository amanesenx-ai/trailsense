# ARCHITECTURE.md — current state of the system

Defined before the first prompt (Assignment 2 rule). Describes *what is*, not history — history lives in DECISIONS.md.

## Style: layered monolith (client · server · database)
The A1 diagram had three blocks: web application, application backend (auth, business logic, API integration,
rule engine, community services) and storage + external services (OpenStreetMap, Open-Meteo). The MVP keeps that shape
and makes each block an explicit layer:

```
 Browser (HTML + CSS, phone-first; tiny JS for "copy briefing")
        │  HTTP GET/POST   (?r=route)
 ┌──────▼─────────────────────────────────────────────────────┐
 │ public/index.php  front controller → src/routes.php         │
 │ Controllers   Auth · Home · Trail · Plan · Group · Feed · Activity
 │      │                     │                                │
 │ Domain (pure PHP)     Weather (adapter)                     │
 │  RiskEngine, Rule,     WeatherProvider ◄─ OpenMeteoProvider ──► api.open-meteo.com
 │  Daylight, Briefing,                 ◄─ DemoWeatherProvider (local/demo only, labelled)
 │  Validators                                                 │
 │      │                                                      │
 │ Repositories (PDO, prepared statements)                     │
 └──────┬─────────────────────────────────────────────────────┘
        │ SQL
 PostgreSQL: app_user, trail, hike_plan, group_plan, risk_rule,
             weather_snapshot, risk_assessment, assessment_result,
             activity, activity_photo (photo bytes stored in the DB)
```

Dependency rule: Controllers → Domain / Weather / Repositories. Domain depends on nothing.
Repositories depend only on PDO. Views receive plain arrays.

## Folder structure
```
trailsense/
├── CLAUDE.md  AGENTS.md  SPEC.md  README.md
├── .claude/              agents/ (security-reviewer, ux-reviewer) · commands/ · skills/risk-rules · settings.json (hooks)
├── bin/                  db-reset.php
├── config/               config.example.php (committed) · config.php (secrets, git-ignored)
├── database/             schema.sql · seed.sql
├── docs/                 ARCHITECTURE · DATA_MODEL · DECISIONS · TRACEABILITY · MANUAL_TESTS · UX_REVIEW · LESSONS · DEBT · DEMO_SCRIPT · DEPLOY
├── public/               index.php (only entry) · assets/app.css · assets/app.js
├── docker/               vhost.conf · php.ini · entrypoint.sh   (+ Dockerfile, render.yaml at the root)
├── src/
│   ├── bootstrap.php     autoload, config, session, helpers (e(), url(), csrf)
│   ├── routes.php        route name → [Controller, method]
│   ├── Core/             Database · Auth · Csrf · View · Request · Flash · HttpException
│   ├── Domain/           RiskEngine · Daylight · Briefing · PlanValidator · ActivityValidator · UserValidator
│   ├── Weather/          WeatherProvider · Forecast · OpenMeteoProvider · DemoWeatherProvider
│   ├── Repositories/     User · Trail · Plan · Rule · Assessment · Activity
│   ├── Services/         AssessmentService (orchestrates weather → engine → storage)
│   └── Controllers/      one per area
├── views/                layout.php · partials/ · auth/ · home/ · trails/ · plans/ · feed/ · errors/
└── tests/                run.php · Unit/ · Integration/ · Http/ · fixtures/
```

## Main flow: plan → check → advise (F2–F5)
1. `PlanController::store` validates with `PlanValidator`, saves `hike_plan` (+ `group_plan` for organizers).
2. `AssessmentService::assess(plan)` asks `WeatherProvider` for the hourly window, computes sunset with `Daylight`,
   stores a `weather_snapshot`, runs `RiskEngine` with the rules from `risk_rule`, stores `risk_assessment` + one
   `assessment_result` per rule.
3. `plans/show` renders each factor with level badge (text + colour), rule condition, observed value and precaution.
4. Organizer: `Briefing::build()` renders the same data as plain text for "Copy briefing".

## Deployment (ADR-009)
Docker image (php:8.3-apache, document root `public/`) on Render's free web service; PostgreSQL on Neon (free).
Config from environment variables; `bin/db-init.php` creates the tables on first boot. See docs/DEPLOY.md.
