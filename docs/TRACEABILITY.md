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

Run: `php tests/run.php` → 73 tests (unit · integration · HTTP).
