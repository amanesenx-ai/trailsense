# Assignment 2 report — ONLY what the brief asks: link to the app + data tables + screenshots of the web application.
import re, os, json
src = open('/home/claude/tools/build_report.py').read()
prefix = src[:src.index('doc=f"""')]
exec(prefix)                                    # cols, cons, purpose, tables, e
# composite keys: show them as one combined key, not as a UNIQUE/PK mark on each column (a column alone is not unique)
comp = collections.defaultdict(list)
for line in open('/home/claude/report/composites.tsv'):
    t, ty, cs = line.rstrip('\n').split('\t'); comp[t].append((ty, [c.strip() for c in cs.split(',')]))
def keys_for(t, c, k, f):
    out = []
    for ty, cs in comp[t]:
        if c in cs: out.append(('PK' if ty == 'PRIMARY KEY' else 'UNIQUE') + ' (' + ', '.join(cs) + ')')
    single_pk = 'PRIMARY' in k and not any(ty == 'PRIMARY KEY' and c in cs for ty, cs in comp[t])
    single_uq = 'UNIQUE' in k and not any(ty == 'UNIQUE' and c in cs for ty, cs in comp[t])
    return ' '.join((['PK'] if single_pk else []) + (['UNIQUE'] if single_uq else []) + out + ([('FK → ' + e(f))] if f else []))
tables = ""
for t, cl in cols.items():
    rows = "".join(f"<tr><td><b>{e(c)}</b></td><td>{e(ty)}</td><td>{'' if n=='YES' else 'NOT NULL'}</td><td>{keys_for(t,c,k,f)}</td></tr>" for c,ty,n,k,f in cl)
    ck = "".join(f"<li><code>{e(re.sub(r'::[a-z ]+(\\[\\])?','',x))}</code></li>" for x in cons[t])
    note = ''.join(f"<li>{'Composite primary key' if ty=='PRIMARY KEY' else 'Unique together'}: <code>({', '.join(cs)})</code>" + (" — each column alone can repeat" if ty=='UNIQUE' else '') + "</li>" for ty, cs in comp[t])
    tables += f"<div class='tbl'><h3>{t}</h3><p class='purpose'>{purpose[t]}</p><table><tr><th>Attribute</th><th>Type</th><th>Null</th><th>Keys</th></tr>{rows}</table>{('<ul class=checks>'+note+ck+'</ul>') if (ck or note) else ''}</div>"
style = re.search(r'<style>(.*?)</style>', src, re.S).group(1).replace('{{', '{').replace('}}', '}')
SHOTS = '/home/claude/report/shots/'            # screenshots captured on CI (bin/screenshots.py)
order = json.load(open('/home/claude/tools/shots.json'))
pages_html = ''
groups = collections.OrderedDict()
for s in order: groups.setdefault(s['group'], []).append(s)
for g, items in groups.items():
    have = [s for s in items if os.path.exists(SHOTS + s['file'])]
    if not have: continue
    for i in range(0, len(have), 2):
        chunk = have[i:i+2]
        figs = ''.join(f"<figure class='sh'><img src='{SHOTS}{s['file']}'><figcaption><b>{e(s['title'])}</b> — {e(s['caption'])}</figcaption></figure>" for s in chunk)
        pages_html += f"<section class='page'><h2>Screens — {e(g)}</h2><div class='shots'>{figs}</div></section>"
ntab = len(cols)
doc = f"""<!DOCTYPE html><html><head><meta charset=utf-8><style>{style}
.sh {{ margin:0 0 10px; break-inside:avoid }} .sh img {{ width:100%; max-height:118mm; object-fit:contain; border:1px solid #9fb5a8; border-radius:8px; display:block; background:#0f2a1f }}
.sh figcaption {{ font-size:9pt; margin-top:4px }}
</style></head><body>
<section class="page">
<div class="cover">
  <div class="sub">Information Systems Architecture · Master in Services Engineering and Management · FEUP</div>
  <h1>TrailSense — MVP</h1>
  <div class="sub">Assignment 2 · Minimum Viable Product · Ana Celonenco</div>
  <div class="kv">
    <b>Live app</b><span><b style='color:#fff'>https://amanesenx.alwaysdata.net</b></span>
    <b>Repository</b><span style='color:#fff'>https://github.com/amanesenx-ai/trailsense</span>
    <b>Public API</b><span style='color:#fff'>https://amanesenx.alwaysdata.net/api/v1</span>
    <b>Demo accounts</b><span>one hiker and one club organizer (fictional) — credentials sent privately</span>
  </div>
</div>
<div class="box"><h3>Scope: core MVP and extensions</h3>
<p class="small" style="margin:2px 0"><b>Core (Assignment 1 Priority 0):</b> sign in with roles (hiker / club organizer) · trail discovery · plan a hike · forecast for the hike hours → rule-based risk check with the rule and a precaution · group hike with briefing · planning-completeness indicator (K1) · community feed and publish a hike.</p>
<p class="small" style="margin:2px 0"><b>Extensions</b> (added after the core worked and was tested, each with its own acceptance criteria and tests): likes and comments, profiles, IPMA warnings and fire risk, trail condition reports, GPS directions with compass, offline use, favourites, read-only public API. The live demo follows the two core journeys.</p></div>
<div class="box"><h3>Contents</h3>
<ol class="small"><li>How I worked with the LLM — where to find the evidence</li><li>Data tables — {ntab} PostgreSQL tables with attributes, primary keys, foreign keys and constraints</li>
<li>Entity–relationship diagram</li><li>Screenshots of the web application (running app with the demo data, real weather, maps and photos — November 2026)</li></ol>
</div>
</section>
<section class="page">
<h2>How I worked with the LLM — where to find the evidence</h2>
<p class="small">Everything below is in the repository <b>https://github.com/amanesenx-ai/trailsense</b>. This page is only a map to it.</p>
<table class="vt">
<tr><th style="width:28%">Good practice (brief)</th><th>Evidence in the repository</th></tr>
<tr><td><b>Architecture and folder structure defined before prompting</b></td><td>The <b>first commit</b> (“docs: spec, project rules, architecture and ADRs before code”) contains only documents and agent rules — no code: <code>docs/ARCHITECTURE.md</code> (layers: Controllers → Domain → Repositories → Views, folder structure), <code>docs/DECISIONS.md</code> (22 architecture decisions, ADR-001 to ADR-022).</td></tr>
<tr><td><b>SPEC.md based on the Assignment 1 user stories</b></td><td><code>SPEC.md</code>: features F1–F13 from the A1 stories, each as “As a … I want … so that …” with its own testable acceptance criteria (AC IDs); scope and “out of scope” stated; later releases added as new sections, never by silent changes.</td></tr>
<tr><td><b>CLAUDE.md / AGENTS.md with the project rules</b></td><td><code>CLAUDE.md</code> (stack, commands, layer rules, security rules, definition of done) and <code>AGENTS.md</code>. In <code>.claude/</code>: two review subagents (<code>security-reviewer</code>, <code>ux-reviewer</code>), a skill (<code>risk-rules</code>), a command (<code>new-feature</code>: explore → plan → test → implement → verify) and hooks (<code>bin/hooks/</code>: syntax check after every edit, block secrets before commands).</td></tr>
<tr><td><b>Tests</b></td><td><code>tests/</code>: <b>171 automated tests</b> — unit (domain rules, validators, GPS maths in JavaScript), integration on PostgreSQL (constraints, migrations, ownership) and HTTP end-to-end flows (login, CSRF, plan → risk check, group hikes, public API). They run with <code>php tests/run.php</code> and on GitHub Actions on every push. Manual checks per screen at 360 px and desktop: <code>docs/MANUAL_TESTS.md</code>.</td></tr>
<tr><td><b>Verify each change before accepting it</b></td><td>Every commit names the acceptance criterion it implements (e.g. “F9 AC9.10: …”) and was accepted only with <code>FAILED: 0</code>. <code>docs/TRACEABILITY.md</code> maps each requirement to its code and its test. Each change was also checked on the live site.</td></tr>
<tr><td><b>Use the LLM to improve the UI, not only generate it</b></td><td><code>docs/UX_REVIEW.md</code>: seven review rounds (hierarchy, contrast, spacing, consistency, accessibility, phone), comparison with the A1 mockups, and changes made after feedback from real users.</td></tr>
<tr><td><b>Other</b></td><td><code>docs/DATA_MODEL.md</code> and versioned migrations (<code>database/migrations/</code>); <code>docs/API.md</code> + <code>docs/openapi.yaml</code> (public API); <code>docs/VALUE_CASE.md</code> and <code>docs/research/</code> (evidence, assumptions, 7 user interviews); <code>docs/LESSONS.md</code> and <code>docs/DEBT.md</code>.</td></tr>
</table>
<div class="box" style="margin:12px 0 0;border-color:#f4c430;background:#fffbea"><p style="margin:0"><b>Roles.</b> Every decision about scope, features, design and priorities was mine: I wrote and changed SPEC.md, and the agent implemented against it. I reviewed every change before accepting it — the test results (<code>FAILED: 0</code>) and the result on the live site — and asked for corrections when something was wrong or did not match what I wanted.</p></div>
</section>
<section class="page">
<h2>Data tables</h2>
<p class="small">Generated from the PostgreSQL schema (<code>database/schema.sql</code>). Referential actions: CASCADE where the child has no meaning without the parent; RESTRICT on reference data (trail, risk_rule); SET NULL on activity.plan_id so a published hike survives the deletion of its plan.</p>
<div class="cols2">{tables}</div>
</section>
<section class="page"><h2>Entity–relationship diagram</h2><p class="small">PK underlined · FK in italics · crow’s foot = many, circle = optional.</p>
<img src="/home/claude/report/er-rot.png" style="height:238mm; display:block; margin:0 auto">
</section>
{pages_html}
</body></html>"""
open('/home/claude/report/report-a2.html', 'w').write(doc)
print('screens:', pages_html.count("<figure"), 'tables:', ntab)
