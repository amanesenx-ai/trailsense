# TRACEABILITY.md — requirement → implementation → test

| Req | Description | Implemented in | Covering automated tests |
|---|---|---|---|
| AC1.2 | Combined filters | TrailController::index, TrailRepository::search | Integration: *trail search combines filters*; Http: *trail filters work…* |
| AC1.3 | Empty result message | views/trails/index.php | Http: *trail filters work…* |
| AC1.5 | Bad filter values ignored | TrailController::index | Http: *trail filters work… bad values are ignored*; Integration: *% and _ treated literally* |
| AC2.1 | Required plan fields | Domain/PlanValidator | Unit: *valid plan passes*, *impossible date… all reported* |
| AC2.2 | No past dates; keep values | PlanValidator, PlanController::store | Unit: *plan in the past is rejected*; Http: *invalid plan keeps typed values* |
| AC2.3 | Upcoming / past lists | PlanRepository::forUser | Manual M5 |
| AC2.4 | Only owner sees/deletes | PlanController::ownedPlan, PlanRepository::deleteOwned | Integration + Http: *cannot open or delete another user's plan* |
| AC3.1–3.2 | Forecast for hike window | Weather/OpenMeteoProvider, Forecast::fromHourly | Unit: *parses Open-Meteo hourly data…*, *request URL contains…* |
| AC3.3 | Clear message, nothing invented | OpenMeteoProvider::forecast | Unit: *beyond the 16-day horizon*, *service down*, *malformed answer* |
| AC3.4 | Snapshot stored | AssessmentRepository::save | Integration: *assessment stores snapshot + one result per rule* |
| AC4.1–4.3 | Evaluate all rules, worst level | Domain/RiskEngine::evaluate | Unit: RiskEngineTest (boundaries, worst level wins) |
| AC4.2 | Explain triggered rule | RiskEngine::describeCondition, views/plans/show.php | Unit: *condition text explains the rule*; Http: *Rule DAY_W triggered* |
| AC4.4 | Missing data ⇒ incomplete | RiskEngine::evaluate, ::byFactor | Unit: *missing forecast marks incomplete*; Integration: *unavailable forecast → incomplete* |
| AC4.5 | Daylight always evaluated | Domain/Daylight, AssessmentService::assess | Unit: DaylightTest; Integration: *daylight still evaluated* |
| AC4.6 | Rules are data | risk_rule table, RuleRepository | Unit + Integration: *thresholds are data / editing a threshold…* |
| AC5.1 | Precaution per warning | risk_rule.precaution | Unit: *every default rule has a precaution* |
| AC5.2 | "Start by HH:MM" | Daylight::latestStart, RiskEngine::precaution | Unit: *latest start…*, *placeholder is filled*; Http: *Start by* |
| AC6.1 | Feed newest first | ActivityRepository::feed | Integration: *publishing… top of the feed*; Http: *publish… top of the feed* |
| AC7.1 | Publish validation | Domain/ActivityValidator | Unit: *activity in the future*, *distance defaults…* |
| AC7.2 | Photo checked by content | Domain/PhotoValidator, ActivityController::store | Http: *a fake image is rejected*, *publish a hike with a photo* |
| AC7.3 | Notes ≤ 1000; plan link | ActivityValidator, ActivityController::store | Unit: *notes over 1000*; Integration: *SET NULL* |
| AC8.1–8.2 | Registration rules | Domain/UserValidator, AuthController::register | Unit: *registration rules*; Http: *register…*, *duplicate e-mail…* |
| AC8.3 | Generic login error | AuthController::login | Http: *wrong password…* |
| AC8.4 | Role menus | views/layout.php | Http: *register → hiker home*, *organizer sees Groups* |
| AC8.5 | Login required | Core/Auth::requireLogin | Http: *anonymous user is redirected* |
| AC8.6 | Hashing, CSRF | Core/Csrf, Core/Auth | Http: *POST without CSRF token is refused*; Integration: *password_verify* |
| AC9.1 | Group fields | PlanValidator::validateGroup | Unit: *group plan needs…*, *valid group plan…* |
| AC9.2–9.3 | Briefing + copy | Domain/Briefing, views/plans/show.php, app.js | Unit: BriefingTest; Http: *organizer group plan produces a copyable briefing* |
| K1 | Plans fully checked | PlanRepository::completeness, home/organizer | Integration: *K1 completeness*; Http: *organizer sees… indicator* |
| NFR1–2 | Phone, accessibility | app.css | Manual + scripted checks in UX_REVIEW.md |
| NFR3 | Security | Repositories (prepared), e(), Csrf | Http: *escaped in the feed (XSS)*, CSRF test; security scan in MANUAL_TESTS.md |

Run: `php tests/run.php` (see totals at the end of this file).

## Release 2
| Req | Description | Implemented in | Covering automated tests |
|---|---|---|---|
| AC1.6–1.9 | Real OSM route on interactive map, start + end markers, directions, attribution, SVG fallback | Html::map, app.js (Leaflet), trails/show, plans/show, database/source + bin/gen-trail-data.py | Release2Flows: *trail page: real map…*, *loop trails show one start/finish marker* |
| AC1.10–1.11 | Licensed photos with author/licence, fallback | trail_photo, TrailRepository::photos, trails/show, app.js | Release2Flows: *…photos with licence…*; Integration: *every trail has a photo* |
| AC4.7 | IPMA district warnings overlapping the hike | Alerts/IpmaProvider::matchWarnings, rules IPMA_Y/IPMA_O | Unit IpmaTest (district, overlap, orange>yellow, coastal, green ignored) |
| AC4.8 | IPMA fire risk of nearest municipality | IpmaProvider::nearestFireRisk, rules FIRE_C/FIRE_W | Unit IpmaTest *fire risk…*; RiskEngine *orange IPMA warning and fire risk 4…* |
| AC4.9 | Advisory rules don't block completeness | risk_rule.required, RiskEngine::evaluate/byFactor | Unit *advisory rules without data…*; IpmaTest *beyond the horizon* |
| AC4.10 | IPMA failure degrades gracefully | IpmaProvider (null + message) | Unit *IPMA down…*, *downloaded once per request* |
| AC9.4–9.7 | Group hikes listed, join/leave, capacity, read-only view, private personal plans | ParticipantRepository, GroupController, PlanController::visiblePlan, groups/index | Release2 integration (join/full/own/not-open/leave); Release2Flows *hiker joins…*, *organizer sees who is coming*; Flows *…personal plan…* |
| AC10.1–10.3 | Community reports; closed → WARNING | TrailReportRepository, TrailController::report, rule TRAIL_CL | Release2 integration *trail reports…*; Release2Flows *closed turns the plan check into a WARNING*, *report without a condition* |
| AC6.4 | Likes (unique) and comments (own delete) | SocialRepository, ActivityController::like/comment/deleteComment | Release2 integration *likes toggle…*, *comments: only the author…*; Release2Flows *like, comment…* |
| AC11.1–11.2 | Profile + stats, no e-mail | ProfileRepository, ProfileController, profile/show | Release2 integration *profile statistics…*; Release2Flows *profile shows statistics…* |
| AC12.1–12.3 | Public REST API | src/Api/Router + Endpoints, public/.htaccess, docs/API.md, docs/openapi.yaml | Http ApiTest (8 tests: index/CORS, filters, 400/404/405, GeoJSON, conditions not stored, no e-mails) |
| ADR-014 | Migrations upgrade the live DB once | Core/Installer, database/migrations/002_release2.sql | Integration InstallerTest *a release-1 database is upgraded…* |


## Release 3 (2026-11-18) — hike mode, map layers, visual risk check
| Requirement | What | Code | Tests |
|---|---|---|---|
| AC13.1–13.9 | Hike mode: live position, compass, progress, off-route alert, 112, share position, finish → publish, wake lock | HikeController, views/hike/show, public/assets/hike.js, ActivityController::create (from=hike) | Release3Flows *hike mode: full-screen map…*, *finishing a hike pre-fills Publish…*, *needs a signed-in user* |
| AC13.10 | Navigation maths | public/assets/hike-core.js | tests/js/hike-core.test.js (6) via Unit HikeCoreTest |
| AC13.6 | FCMP waymark legend per trail (PR/GR/PL, boardwalk) | Domain/Waymarks, views/partials/waymarks | Unit Release3 *waymark legend…*, *routes without a code…*; Release3Flows *trail page offers "Start hike"…*, *boardwalk…* |
| AC1.12 | Map layers OSM / OpenTopoMap / Waymarked Trails, Referer policy | public/assets/app.js `TS.layers`, public/index.php | Release3Flows *map layers…*; Release2Flows *map tiles get our origin as Referer* |
| AC4.11–4.12 | Verdict + checklist | Domain/Verdict, views/partials/risk | Unit Verdict (4) |
| AC4.13 | Hour by hour (chart + hour cards), stored hourly | Weather/Forecast::hourRows, OpenMeteoProvider (weather_code), Core/WeatherChart, Domain/WeatherCode, migration 003 | Unit Release3 *hourly rows…*, *WMO codes…*; Verdict *weather chart…*; Installer *migrated:2,3* |
| AC4.14–4.15 | Factor tiles, direct Open-Meteo/IPMA links (also in the API) | views/partials/risk, AssessmentService::links, Api/Endpoints | Release3Flows *visual risk check…*; ApiTest *conditions…* |


## Release 4 (2026-11-18) — trail GPS, "is this route for me?", richer screens
| Requirement | What | Code | Tests |
|---|---|---|---|
| AC13.6 (rev.) | FCMP marks as in the Rota Vicentina manual p. 13; placement rules | Domain/Waymarks (`svg`, `PLACEMENT`), views/partials/waymarks | Unit Release4 *FCMP marks…*; Release3 *waymark legend…* |
| AC13.7 (rev.) | 112 removed | views/hike/show | Release3Flows *…no 112* |
| AC13.11 | Trail GPS: turn-by-turn banner, arrow, walked line, voice, next climb, altitude | hike-core.js (`turns`, `instruction`, `pointAt`, `altitudeAt`, `nextClimb`), hike.js | tests/js (9), Release3Flows *hike mode…* |
| AC1.13 | Route type in plain words + climb analysis and rating | Domain/RouteType, Domain/Climb, views/partials/route-fit | Unit Release4 *climb analysis…*, *climb rating…*, *route type…*; Release4Flows *trail page…*, *flat coastal…* |
| AC1.14 | Elevation profile | migration 004, Core/ElevationChart, API `/trails/{id}` | Unit Release4 *elevation chart…*; ApiTest; Installer *migrated:2,3,4* |
| AC1.15 | Real map image on cards | Core/StaticMap, Html::cardMedia, card partials | Unit Release4 *static map…*; Release4Flows *real map image…* |
| AC2.5 | Plans agenda + tabs | views/plans/index, partials/plan-row, PlanController::index | Release4Flows *plans page is an agenda…* |
| AC8.3 | Home: hero, stats, discover, community | HomeController, views/home/hiker | Release4Flows *home…* |

Run: `php tests/run.php` → 143 tests (release 3: 132).

## Release 5 (2026-11-19) — reviewer's 45 improvements
Item-by-item mapping: docs/REVIEW_CHECKLIST.md. New ACs: AC0.1, AC1.16–1.19, AC2.5, AC8.5–8.6, AC9.8–9.9 (SPEC §11). Tests: Release5FlowsTest (12), Unit Release5Test (3), InstallerTest (migration 005). Run: `php tests/run.php` → 158 tests. CI: `.github/workflows/tests.yml` runs them on every push.

## Release 6 (2026-11-20) — offline trail GPS
| Requirement | Code | Tests |
|---|---|---|
| AC13.12 download for offline | public/sw.js, hike.js (offline section), hike-core.js `tilesForRoute`, `tileXY` | tests/js *offline tiles…*; Release5Flows *offline trail GPS…*; M-R6-1 |
| AC13.13 works without signal | sw.js (network-first hike pages, cached tiles/assets), hike.js `setOffline` | M-R6-1 |
| AC13.14 finish offline → publish later | hike.js finish, app.js pending-hike banner | M-R6-1 |
| AC13.15 sign-out clears saved pages | app.js → sw.js `clear-pages` | Release5Flows (sw contains clear-pages) |
