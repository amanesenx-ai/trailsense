import html, collections, re
S="/home/claude/trailsense/docs/screenshots/archive/"
cols=collections.OrderedDict()
for line in open('/home/claude/report/cols.tsv'):
    t,c,ty,nul,keys,fk=line.rstrip('\n').split('\t'); cols.setdefault(t,[]).append((c,ty,nul,keys,fk))
cons=collections.defaultdict(list)
for line in open('/home/claude/report/cons.tsv'):
    t,d=line.rstrip('\n').split('\t',1)
    if d.startswith('CHECK'): cons[t].append(d)
purpose={'app_user':'Users and their role (F8).','trail':'Curated trails with route drawing (F1).','hike_plan':'A user’s plan: trail, date, start, duration (F2).',
'group_plan':'Group details of an organizer plan — 1 : 0..1 with hike_plan (F9).','risk_rule':'Predefined, editable rules: thresholds are data (F4/F5).',
'weather_snapshot':'Forecast values actually used by an assessment (F3).','risk_assessment':'One evaluation of a plan; overall level; complete or not (F4).',
'assessment_result':'Association class assessment × rule with observed value (F4/F5).','activity':'Published hike shown in the community feed (F6/F7).','activity_photo':'Photos of a published hike (F7).'}
def e(s): return html.escape(s)
tables=""
for t,cl in cols.items():
    rows="".join(f"<tr><td><b>{e(c)}</b></td><td>{e(ty)}</td><td>{'' if n=='YES' else 'NOT NULL'}</td><td>{'PK ' if 'PRIMARY' in k else ''}{'UNIQUE ' if 'UNIQUE' in k else ''}{('FK → '+e(f)) if f else ''}</td></tr>" for c,ty,n,k,f in cl)
    ck="".join(f"<li><code>{e(re.sub(r'::[a-z ]+(\\[\\])?','',x))}</code></li>" for x in cons[t])
    tables+=f"<div class='tbl'><h3>{t}</h3><p class='purpose'>{purpose[t]}</p><table><tr><th>Attribute</th><th>Type</th><th>Null</th><th>Keys</th></tr>{rows}</table>{('<ul class=checks>'+ck+'</ul>') if ck else ''}</div>"
def shot(name,cap,cls=""):
    return f"<figure class='shot {cls}'><img src='{S}{name}.png'><figcaption>{cap}</figcaption></figure>"
test_out=open('/home/claude/report/test-output.txt').read()
summary=test_out.strip().splitlines()[-1]
suites=re.findall(r'== (\w+) ==',test_out)
counts={s:0 for s in suites}; cur=None
for l in test_out.splitlines():
    m=re.match(r'== (\w+) ==',l)
    if m: cur=m.group(1)
    elif l.strip().startswith('✔'): counts[cur]+=1
sample="\n".join(l for l in test_out.splitlines() if '✔' in l and ('AC4' in l or 'AC5' in l or 'AC2.4' in l or 'XSS' in l or 'CSRF' in l or 'AC7.2' in l or 'AC3.3' in l))
doc=f"""<!DOCTYPE html><html><head><meta charset=utf-8><style>
@page {{ size: A4; margin: 14mm 13mm; }}
body {{ font: 10pt/1.45 'Inter','Segoe UI',system-ui,sans-serif; color:#1b2a22; margin:0; }}
h1 {{ font-size: 26pt; margin:0; color:#0f2a1f; letter-spacing:-.02em }} h2 {{ font-size:15pt; color:#0f2a1f; margin:0 0 8px; border-bottom:3px solid #f4c430; padding-bottom:4px; display:inline-block }}
h3 {{ font-size:10.5pt; margin:0 0 2px; color:#163a2b }} .page {{ page-break-after: always; }} .page:last-child {{ page-break-after:auto }}
.cover {{ background:#0f2a1f; color:#f3f6f1; padding:28px 28px 22px; border-radius:14px; }} .cover h1 {{ color:#f4c430; font-size:34pt }} .cover .sub {{ font-size:13pt; color:#bcd0c5 }}
.kv {{ display:grid; grid-template-columns: 130px 1fr; gap:4px 12px; margin-top:16px; font-size:10.5pt }} .kv b {{ color:#f4c430 }} .kv code {{ color:#fff }}
.fill {{ background:#f4c430; color:#1a1a12; padding:1px 6px; border-radius:4px; font-weight:700 }}
.box {{ border:1px solid #cfdcd4; border-radius:10px; padding:10px 12px; margin-top:12px }} .box h3 {{ margin-bottom:6px }}
table {{ border-collapse:collapse; width:100%; font-size:8.3pt }} th,td {{ border-bottom:1px solid #dde7e1; text-align:left; padding:2.5px 5px; vertical-align:top }} th {{ background:#eef4f0; }}
.tbl {{ break-inside: avoid; margin-bottom:10px }} .purpose {{ margin:0 0 3px; color:#4b5d53; font-size:8.8pt }} .checks {{ margin:3px 0 0; padding-left:16px; font-size:7.6pt; color:#3d5047 }}
.cols2 {{ columns:2; column-gap:16px }} .cols2 .tbl {{ display:inline-block; width:100% }}
code {{ font-size:.92em }} .grid {{ display:grid; gap:10px }} .g4 {{ grid-template-columns:repeat(4,1fr) }} .g3 {{ grid-template-columns:repeat(3,1fr) }} .g2 {{ grid-template-columns:1fr 1fr }}
.shot {{ margin:0 }} .shot img {{ width:100%; height:330px; object-fit:cover; object-position:top; border-radius:10px; border:1px solid #9fb5a8; display:block }}
.shot.tall img {{ height:520px }} .shot.live img {{ height:auto; object-fit:contain }} .shot.desk img {{ height:auto }} .g2 .shot.desk {{ margin-bottom:8px }} figcaption {{ font-size:8pt; color:#3d5047; margin-top:3px }} figcaption b {{ color:#0f2a1f }}
ul {{ margin:4px 0; padding-left:18px }} li {{ margin:1.5px 0 }} .small {{ font-size:8.5pt }} pre {{ background:#0f2a1f; color:#d8f5e3; padding:10px; border-radius:8px; font-size:7.6pt; white-space:pre-wrap }}
.badge {{ display:inline-block; padding:0 6px; border-radius:6px; font-weight:800; font-size:8pt }} .improv td:first-child {{ width:30%; font-weight:600 }}
</style></head><body>

<section class="page">
<div class="cover">
  <div class="sub">Information Systems Architecture · Master in Services Engineering and Management</div>
  <h1>TrailSense — MVP</h1>
  <div class="sub">Assignment 2 · Minimum Viable Product · Ana Celonenco</div>
  <div class="kv">
    <b>Live app</b><span><b style='color:#fff'>https://amanesenx.alwaysdata.net</b></span>
    <b>Repository</b><span><span style='color:#fff'>https://github.com/amanesenx-ai/trailsense</span></span>
    <b>Demo hiker</b><span><code>joana@trailsense.pt</code> · password <code>trailsense123</code></span>
    <b>Demo organizer</b><span><code>bruno@trailsense.pt</code> · password <code>trailsense123</code></span>
    <b>Stack</b><span>PHP 8.3 (no framework) · PostgreSQL · HTML/CSS · Leaflet + OpenStreetMap · Open-Meteo + IPMA APIs</span>
    <b>Hosting</b><span>alwaysdata (always on, HTTPS) · auto-deploy from GitHub every 5 min</span>
    <b>Public API</b><span>https://amanesenx.alwaysdata.net/api/v1</span>
  </div>
</div>
<div class="box"><h3>What the MVP does (Assignment 1 Priority 0, all working end to end)</h3>
<ul>
<li><b>F8</b> Register / sign in with a role — hikers see Trails · Plans · Feed, organizers see Trails · Groups · Feed.</li>
<li><b>F1</b> Trail discovery (11 real OSM trails with official codes and photos) and an interactive map with start and end.</li>
<li><b>F2–F5</b> Plan a hike → forecast for the exact hours (Open-Meteo) → <b>rule-based risk check</b> (rain, wind, heat, cold, daylight) → each warning names the <b>rule</b>, the observed value and a <b>precaution</b> (e.g. “Start by 13:45 or choose a shorter trail”). Missing data is shown as <i>incomplete</i>, never as OK.</li>
<li><b>F9</b> Organizer group plan (size, meeting point/time) with an auto-generated, copyable <b>group briefing</b>.</li>
<li><b>F6–F7</b> Community feed and publish a hike (photo upload checked by content).</li>
<li><b>K1</b> Organizer KPI “upcoming hikes fully checked” — the A1 organizational metric now measured from real data.</li>
<li><b>Release 2</b> IPMA warnings + fire risk, group hikes open to join, trail reports, likes/comments, profiles, public REST API.</li>
</ul></div>
<div class="box"><h3>How this assignment improves on Assignment 1</h3>
<table class="improv">
<tr><td>Vague organizational value</td><td>The A1 KPI “planning completeness” is now computed from the database and shown to organizers (K1).</td></tr>
<tr><td>Only one user story had acceptance criteria</td><td>SPEC.md gives every feature numbered acceptance criteria (AC1.1–AC9.3) and non-functional requirements; each maps to code and tests (TRACEABILITY.md).</td></tr>
<tr><td>“Clear rules” were a promise</td><td>Rules are rows in a table (risk_rule); every result is stored per rule and shown with its condition — the transparency is verifiable.</td></tr>
<tr><td>Architecture diagram only</td><td>Layered architecture, folder structure and 8 Architecture Decision Records written before coding.</td></tr>
<tr><td>Mockups only</td><td>Working screens that follow the A1 visual language, reviewed for accessibility (all text contrast ≥ 6:1, 44 px targets, 360 px).</td></tr>
</table></div>
</section>

<section class="page">
<h2>Architecture and LLM workflow</h2>
<div class="grid g2">
<div class="box"><h3>Layered architecture (decided before the first prompt)</h3>
<ul class="small">
<li><b>public/index.php</b> — single front controller, routes in <code>src/routes.php</code></li>
<li><b>Controllers</b> — read request, check auth/role/ownership, call services</li>
<li><b>Domain</b> (pure PHP, no I/O) — RiskEngine, Daylight, validators, Briefing → unit-tested</li>
<li><b>Weather</b> — <code>WeatherProvider</code> interface; OpenMeteoProvider (production), DemoWeatherProvider (offline rehearsal, labelled)</li>
<li><b>Repositories</b> — the only place with SQL; PDO prepared statements only</li>
<li><b>Views</b> — templates; every value escaped with <code>e()</code></li>
<li><b>PostgreSQL</b> — 10 tables with PK, FK, UNIQUE and CHECK constraints</li>
</ul></div>
<div class="box"><h3>Good practices visible in the repository</h3>
<ul class="small">
<li><b>SPEC.md</b> — scope, out of scope, entities, acceptance criteria from A1 user stories</li>
<li><b>CLAUDE.md / AGENTS.md</b> — commands, layer rules, coding rules, definition of done</li>
<li><b>docs/ARCHITECTURE.md</b> and <b>docs/DECISIONS.md</b> (ADR-001…008)</li>
<li><b>.claude/agents</b> security-reviewer and ux-reviewer · <b>.claude/commands</b> new-feature (explore → plan → test → implement) · <b>.claude/skills</b> risk-rules</li>
<li><b>Hooks</b> — lint + unit tests after every edit; secret scan blocks commits containing credentials</li>
<li><b>docs/TRACEABILITY.md</b>, <b>MANUAL_TESTS.md</b>, <b>UX_REVIEW.md</b>, <b>LESSONS.md</b>, <b>DEBT.md</b></li>
<li>Every change accepted only with <code>php tests/run.php</code> → <code>FAILED: 0</code></li>
</ul></div>
</div>
<div class="box"><h3>Main flow: plan → check → advise</h3>
<p class="small">PlanController saves <code>hike_plan</code> (+ <code>group_plan</code>) → AssessmentService asks the WeatherProvider for the hourly window and computes sunset locally → RiskEngine evaluates every active row of <code>risk_rule</code> → one <code>weather_snapshot</code>, one <code>risk_assessment</code> and one <code>assessment_result</code> per rule are stored in a transaction → the plan page shows each factor with level, rule, observed value and precaution; organizers also get the briefing text.</p></div>
</section>
<section class="page"><h2>Entity–relationship diagram</h2><p class="small">10 tables · PK underlined · FK in italics · crow’s foot = many, circle = optional.</p>
<img src="/home/claude/report/er-rot.png" style="height:238mm; display:block; margin:0 auto">
</section>

<section class="page">
<h2>Data tables</h2>
<p class="small">Generated from the live PostgreSQL database. Referential actions: CASCADE where the child has no meaning without the parent; RESTRICT on reference data (trail, risk_rule); SET NULL on activity.plan_id so a published hike survives the deletion of its plan. Patterns from Class 4: many-to-one total (hike_plan→trail), many-to-one partial (activity→hike_plan), one-to-one partial (group_plan), one-to-one total (risk_assessment↔weather_snapshot), many-to-many with attributes (assessment_result).</p>
<div class="cols2">{tables}</div>
</section>

<section class="page">
<h2>Screens — hiker flow (phone, 390 px) · release 1</h2>
<div class="grid g4">
{shot('m-login','<b>Sign in</b> (F8) — demo accounts shown for reviewers')}
{shot('m-register','<b>Register</b> — role chosen at sign-up (F8)')}
{shot('m-home-hiker','<b>Hiker home</b> — next hike with level, community (A1 mockup)')}
{shot('m-trails','<b>Trails</b> — combined filters (F1)')}
{shot('m-trail','<b>Trail detail</b> — route, stats, “Plan this hike”')}
{shot('m-plan-form','<b>Plan a hike</b> — duration prefilled (F2)')}
{shot('m-plan-errors','<b>Validation</b> — error next to field, values kept')}
{shot('m-plans','<b>My plans</b> — upcoming and past')}
</div></section>

<section class="page">
<h2>Screens — weather risk assessment (F3–F5) · release 1</h2>
<div class="grid g3">
{shot('m-plan','<b>Risk assessment</b> — each factor: badge, observed value, rule code + condition, precaution','tall')}
{shot('m-plan-daylight','<b>Late start</b> — DAY_W warning with “Start by HH:MM or choose a shorter trail”','tall')}
{shot('m-plan-incomplete','<b>Beyond 16 days</b> — no invented data: “Incomplete”, daylight still checked','tall')}
</div>
<div class="grid g3" style="margin-top:10px">
{shot('m-rules-table','<b>Transparency</b> — every rule that was checked, with observed value')}
{shot('m-home-org','<b>Organizer home</b> — Groups menu and K1 KPI card')}
{shot('m-briefing','<b>Group briefing</b> (F9) — generated and copied to clipboard')}
</div></section>

<section class="page">
<h2>Screens — organizer and community · release 1</h2>
<div class="grid g4">
{shot('m-group-form','<b>New group hike</b> — size, meeting point and time (F9)')}
{shot('m-plan-group','<b>Group plan</b> — assessment shared with the group')}
{shot('m-feed','<b>Community feed</b> — newest first (F6)')}
{shot('m-post','<b>Shared hike</b> — route, distance, moving time, pace (F6)')}
</div>
<div class="grid g4" style="margin-top:10px">
{shot('m-publish','<b>Publish a hike</b> — photo + alt text (F7)')}
</div>
</section><section class="page"><h2>Screens — desktop (1280 px) · release 1</h2><p class='small'>Release 1 used a provisional trail catalogue; release 2 (next pages, captured on the live site) replaced it with real OpenStreetMap trails.</p>
<div class="grid g2">
{shot('d-plan','<b>Plan page</b> on desktop — top navigation','desk')}
{shot('d-home-org','<b>Organizer home</b> on desktop','desk')}
{shot('d-trails','<b>Trails</b> — 3-column grid','desk')}
{shot('d-feed','<b>Community feed</b>','desk')}
</div></section>


<section class="page">
<h2>Release 2 — improving the Assignment 1 proposals</h2>
<p class="small">After the first version went online, the owner asked for the improvements below. Each comes from the A1 deck (problem, opportunity, journeys or the Priority 1 list), was added to SPEC.md §8 with acceptance criteria and to DECISIONS.md (ADR-010…014) <b>before</b> coding, and is covered by tests.</p>
<table class="improv">
<tr><td>Real map with start and end</td><td>Interactive OpenStreetMap map (Leaflet) with the <b>real route</b> of each official trail (PR codes), labelled Start / End markers, "Directions to the start" and "View route on OpenStreetMap".</td></tr>
<tr><td>Trail pictures</td><td>2–3 real photos per trail from Wikimedia Commons, with author and licence on each photo (as in A1, which used Wikipedia photos).</td></tr>
<tr><td>Second API: official alerts</td><td><b>IPMA</b> (Portuguese met office) district warnings and rural fire risk 1–5 are new rules next to the Open-Meteo forecast; "not published yet" is shown honestly and does not block the check.</td></tr>
<tr><td>Plans visible to others</td><td>Group hikes are open: hikers join while places are left, then see the briefing; organizers see who is coming. Personal plans stay private.</td></tr>
<tr><td>A1 "community trail reports"</td><td>Hikers report conditions (muddy, obstacle, closed…); a "closed" report turns the plan check into WARNING.</td></tr>
<tr><td>A1 Priority 1</td><td>Likes and comments, profile with statistics (hikes, km, ascent, 6-month chart).</td></tr>
<tr><td>Reachable by other applications</td><td><b>Public REST API</b> <code>/api/v1</code> (trails, GeoJSON routes, live risk check, group hikes, feed) documented with OpenAPI.</td></tr>
<tr><td>Engineering</td><td>Versioned migrations upgraded the live database without losing data; GitHub Actions runs all tests on every push; auto-deploy every 5 min.</td></tr>
</table>
<div class="grid g3" style="margin-top:10px">
<figure class='shot'><img src='/home/claude/trailsense/docs/screenshots/live-trail-map.jpg'><figcaption><b>Live: real OSM route, Start/End, directions</b></figcaption></figure><figure class='shot'><img src='/home/claude/trailsense/docs/screenshots/live-trail-photos.jpg'><figcaption><b>Live: licensed trail photos</b></figcaption></figure><figure class='shot'><img src='/home/claude/trailsense/docs/screenshots/live-trails-list.jpg'><figcaption><b>Live: trail list with official codes and photos</b></figcaption></figure>
</div></section>
<section class="page">
<h2>Release 2 — screens</h2>
<div class="grid g4">
<figure class='shot tall'><img src='/home/claude/trailsense/docs/screenshots/live-plan-ipma.jpg'><figcaption><b>Live: Open-Meteo + IPMA check</b></figcaption></figure><figure class='shot tall'><img src='/home/claude/trailsense/docs/screenshots/live-groups.jpg'><figcaption><b>Live: group hikes open to join</b></figcaption></figure><figure class='shot tall'><img src='/home/claude/trailsense/docs/screenshots/r2-plan-participant.png'><figcaption><b>Participant view: joined, briefing</b></figcaption></figure><figure class='shot tall'><img src='/home/claude/trailsense/docs/screenshots/r2-plan-organizer.png'><figcaption><b>Organizer: IPMA warning + who is coming</b></figcaption></figure>
</div>
<div class="grid g4" style="margin-top:10px">
<figure class='shot'><img src='/home/claude/trailsense/docs/screenshots/r2-post-comments.png'><figcaption><b>Likes and comments</b></figcaption></figure><figure class='shot'><img src='/home/claude/trailsense/docs/screenshots/r2-profile.png'><figcaption><b>Profile with statistics</b></figcaption></figure>
<div class="box" style="grid-column: span 2"><h3>Public API example</h3><pre>GET /api/v1/trails/7/conditions?date=2026-11-19&amp;start=10:00&amp;duration=150

&#123; "data": &#123; "trail": &#123;"id": 7, "name": "Trilho da Preguiça"&#125;,
  "overall": "caution", "complete": true, "sunset": "17:09",
  "factors": [ &#123;"factor": "wind", "status": "caution",
     "observed": "56.2 km/h", "rule": &#123;"code": "WIND_C", ...&#125;&#125;,
     &#123;"factor": "official", "status": "ok", "observed": "no warning"&#125;,
     &#123;"factor": "fire", "status": "ok", "observed": "1/5 Reduced"&#125; ],
  "forecast": &#123;"source": "open-meteo", ...&#125;,
  "official": &#123;"source": "ipma", "warning_level": 0, "fire_risk": 1&#125; &#125; &#125;</pre>
<p class="small">Measured on the live server on 18 Nov 2026 (real Open-Meteo and IPMA data).</p></div>
</div></section>
<section class="page">
<h2>Testing</h2>
<div class="grid g3">
<div class="box"><h3>{counts.get('Unit',0)} unit tests</h3><p class="small">Rule engine (boundaries, worst level, missing data, rules as data), daylight and latest start, all validators, Open-Meteo parsing and failure modes, briefing text.</p></div>
<div class="box"><h3>{counts.get('Integration',0)} integration tests</h3><p class="small">Real PostgreSQL test database: constraints, transactions, ownership, stored assessments, rules as data, K1, group capacity, reports, likes, profiles, and the <b>release-1 → release-2 migration</b>.</p></div>
<div class="box"><h3>{counts.get('Http',0)} end-to-end HTTP flows</h3><p class="small">Real requests to the running app and the <b>public API</b>: login, CSRF, filters, plan → assessment, joining group hikes, reports, likes/comments, map and photos on the trail page, API status codes, no e-mails exposed.</p></div>
</div>
<p><b>Result:</b> <code>php tests/run.php</code> → <b>{e(summary)}</b>. Excerpt:</p>
<pre>{e(sample)}</pre>
<div class="box"><h3>Manual and accessibility checks</h3>
<ul class="small">
<li>GitHub Actions runs the whole suite on every push (.github/workflows/tests.yml).</li><li>Online check on https://amanesenx.alwaysdata.net: real Open-Meteo and IPMA answers, map/photos on a phone; HTTPS forced, protected pages redirect to login, source/config/database files not reachable (404).</li><li>16 screen-by-screen checks (MANUAL_TESTS.md) at 390 px and 1280 px, including keyboard-only navigation, confirm dialog on delete and clipboard copy of the briefing.</li>
<li>Scripted on 15 pages at 360 px: no horizontal scroll, every input labelled, touch targets ≥ 44 px (two small links found and fixed).</li>
<li>Contrast of all 13 text/background pairs ≥ 6:1 (WCAG AA needs 4.5:1). Risk levels always carry text, never colour only.</li>
<li>Security checklist: prepared statements only, output escaped, CSRF on every POST, role and ownership checks, upload type checked by content, secrets only in config.php.</li>
</ul></div>
</section>
</body></html>"""
import re as _re
doc=_re.sub(r"<figure class='shot( tall)?'><img src='([^']*/live-[^']*)'", r"<figure class='shot live'><img src='\2'", doc)
open('/home/claude/report/report.html','w').write(doc)
