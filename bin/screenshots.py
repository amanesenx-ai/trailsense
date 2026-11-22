"""Captures the report screenshots from a running TrailSense (real Open-Meteo/IPMA weather, real OSM maps and photos).
Run by .github/workflows/screenshots.yml against the app started on the CI machine with the demo data (seed.sql).
Usage: python bin/screenshots.py http://127.0.0.1:8000 docs/screenshots/a2"""
import asyncio, sys, os
from playwright.async_api import async_playwright

BASE, OUT = sys.argv[1].rstrip('/'), sys.argv[2]
PW = os.environ.get('DEMO_PW', 'trailsense123')          # local seed password of the throwaway CI database
os.makedirs(OUT, exist_ok=True)

async def shot(pg, name, full=False):
    await pg.wait_for_timeout(1500)
    await pg.screenshot(path=f'{OUT}/{name}', full_page=full)
    print('saved', name)

async def login(ctx, email):
    pg = await ctx.new_page()
    await pg.goto(BASE + '/?r=login')
    await pg.fill('#email', email); await pg.fill('#password', PW)
    await pg.click('form button[type=submit]'); await pg.wait_for_load_state('networkidle')
    return pg

async def go(pg, route):
    await pg.goto(BASE + '/?' + route, wait_until='networkidle')
    await pg.add_style_tag(content='html{scroll-behavior:auto !important}')   # instant scrolling for the captures

async def main():
    async with async_playwright() as p:
        b = await p.chromium.launch()
        desk = dict(viewport={'width': 1280, 'height': 860}, device_scale_factor=1.5)
        # public pages
        c = await b.new_context(**desk); pg = await c.new_page()
        await go(pg, 'r=home'); await shot(pg, '01-landing.png')
        await pg.evaluate("document.getElementById('why').scrollIntoView({block:'start'}); window.scrollBy(0,-130)"); await pg.wait_for_timeout(600); await shot(pg, '02-landing-why.png')
        await go(pg, 'r=register'); await shot(pg, '03-register.png')
        await go(pg, 'r=sources'); await shot(pg, '04-sources.png')
        await c.close()
        # hiker
        c = await b.new_context(**desk); pg = await login(c, 'joana@trailsense.pt')
        await go(pg, 'r=home'); await shot(pg, '05-hiker-home.png')
        await go(pg, 'r=trails'); await shot(pg, '06-trails.png')
        await go(pg, 'r=trail&id=7'); await shot(pg, '07-trail.png')
        await pg.evaluate("document.querySelectorAll('details.drop').forEach((d,i)=>d.open = i===1)")
        await pg.evaluate("document.querySelectorAll('details.drop')[1].scrollIntoView({block:'start'}); window.scrollBy(0,-90)")
        await shot(pg, '08-trail-fit.png')
        await go(pg, 'r=plan.new&trail_id=7'); await shot(pg, '09-plan-form.png')
        await go(pg, 'r=plan&id=1'); await shot(pg, '10-risk-check.png')
        await pg.evaluate("document.querySelector('.hours').scrollIntoView({block:'start'}); window.scrollBy(0,-160)")
        await shot(pg, '11-risk-hours.png')
        await go(pg, 'r=feed'); await shot(pg, '13-feed.png')
        await go(pg, 'r=publish'); await shot(pg, '14-publish.png')
        await go(pg, 'r=account'); await shot(pg, '15-profile.png')
        await go(pg, 'r=groups'); await shot(pg, '16-groups.png')
        await c.close()
        # hike mode on a phone, standing at the trail start
        c = await b.new_context(viewport={'width': 390, 'height': 844}, device_scale_factor=2, is_mobile=True, has_touch=True,
                                geolocation={'latitude': 41.10026, 'longitude': -8.55777}, permissions=['geolocation'])
        pg = await login(c, 'joana@trailsense.pt')
        await go(pg, 'r=hike&id=1'); await pg.wait_for_timeout(3000); await shot(pg, '12-hike-mode.png')
        await c.close()
        # organizer
        c = await b.new_context(**desk); pg = await login(c, 'bruno@trailsense.pt')
        await go(pg, 'r=home'); await shot(pg, '17-organizer-home.png')
        await go(pg, 'r=plan&id=3')
        await pg.evaluate("document.querySelector('.briefing').scrollIntoView({block:'start'}); window.scrollBy(0,-90)")
        await shot(pg, '18-event.png')
        await c.close(); await b.close()

asyncio.run(main())
