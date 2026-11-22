# Assignment 2 report — ONLY what the brief asks: link to the app + data tables + screenshots of the web application.
import re, os, json
src = open('/home/claude/tools/build_report.py').read()
prefix = src[:src.index('doc=f"""')]
exec(prefix)                                    # cols, cons, purpose, tables, e
style = re.search(r'<style>(.*?)</style>', src, re.S).group(1).replace('{{', '{').replace('}}', '}')
SHOTS = '/home/claude/report/shots/'            # the owner's screenshots of the live site
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
<div class="box"><h3>Contents</h3>
<ol class="small"><li>Data tables — {ntab} PostgreSQL tables with attributes, primary keys, foreign keys and constraints</li>
<li>Entity–relationship diagram</li><li>Screenshots of the web application (live site, November 2026)</li></ol>
<p class="small">Specification, architecture, decisions, tests, UX review, user research and sources are in the repository (<code>SPEC.md</code>, <code>CLAUDE.md</code>, <code>docs/</code>) and in the app (footer → “Sources and credits”).</p></div>
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
