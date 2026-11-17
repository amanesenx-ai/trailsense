import json, math
routes={t['id']:t for t in json.load(open('data/routes.json'))}
photos=json.load(open('data/photos.json'))
H='$2y$10$sMXtQVVqUFOBQ4Ei44g2RujbZew6NP2bQOKpKFjEyGgPBnpw94Jue'
def q(s): return "'"+str(s).replace("'","''")+"'"
# id, osm, name, region, ipma, description
T=[
 (1,17637058,'Trilho do Rio Febros','Vila Nova de Gaia','PTO',"One-way riverside walk along the Febros valley, mostly on shaded earth paths. Gentle and well suited to a first hike; parts get muddy after rain."),
 (2,12935837,'Trilho dos Romanos','Valongo','PTO',"Route through the Serras do Porto on the Santa Justa ridge, named after the Roman gold mines whose old galleries are still visible. Schist paths with short steep climbs."),
 (3,12935835,'Trilho do Castelo','Valongo','PTO',"Loop in the Serras do Porto above the Ferreira river: forest tracks, schist paths and a few steep sections with views over the valley."),
 (4,5389226,'Passadiços do Paiva','Arouca','AVR',"About 8.7 km of wooden walkways and stairs along the Paiva river gorge between Areinho and Espiunca. Many steps; tickets must be booked in advance."),
 (5,7576696,'Rota do Ouro Negro','Arouca','AVR',"Arouca Geopark route about the wolfram (\"black gold\") mining past of the Freita mountains. Rough mountain paths and exposed sections."),
 (6,18693015,'Caminho do Carteiro','Arouca','AVR',"Old mountain path once used to carry the post between Arouca villages. Steep stone paths with a big descent and ascent — for fit hikers."),
 (7,1796007,'Trilho da Preguiça','Gerês','BRG',"Classic loop in the Peneda-Gerês National Park through oak woods to the Preguiça viewpoint over the Gerês valley. Rocky in places."),
 (8,8408131,'Trilho da Calcedónia','Gerês','BRG',"Loop to the granite summit area of Calcedónia in the Peneda-Gerês National Park, with wide views over the Caniçada reservoir. Steep, rocky climb."),
 (9,8624819,'Caminhos da Senhora da Graça (Bilhó)','Mondim de Basto','VRL',"Long one-way route from the village of Bilhó up to the Senhora da Graça sanctuary on Monte Farinha. Steady ascent and an exposed summit."),
 (10,6540308,'Passear Perre II','Viana do Castelo','VCT',"Rural loop around the parish of Perre on country lanes and paths, with views towards the Lima valley. Mostly gentle."),
 (11,5370636,'Trilho dos Dólmens','Baião','PTO',"Loop on the Aboboreira plateau past megalithic dolmens, through a landscape where ancient oak forest gave way to pasture. Exposed upland — check wind and visibility."),
]
def stats(osm):
    r=routes[osm]; km=r['km']; gain=r['gain']
    mins=int(round((km/4*60+gain/100*10)/5)*5)
    score=km+gain/100
    diff='easy' if (score<8 and gain<300) else ('hard' if score>=15 else 'moderate')
    loop=r['loopGapM']<200
    return km,gain,mins,diff,loop,r
rows=[]
for (i,osm,name,reg,area,desc) in T:
    km,gain,mins,diff,loop,r=stats(osm)
    s=r['pts'][0]
    rows.append(dict(id=i,osm=osm,name=name,region=reg,area=area,desc=desc,km=km,gain=gain,mins=mins,diff=diff,loop=loop,ref=r['ref'],pts=r['pts'],lat=s[0],lon=s[1]))
for x in rows: print(x['id'],x['name'],x['km'],'km',x['gain'],'m',x['mins'],'min',x['diff'],'loop' if x['loop'] else 'oneway')

def trail_values(x):
    return "(%d,%s,%s,%s,%d,%d,%s,%s,%s,%s,%s,%s,%s,%s,%s,FALSE)"%(x['id'],q(x['name']),q(x['region']),x['km'],x['gain'],x['mins'],q(x['diff']),x['lat'],x['lon'],q(json.dumps(x['pts'],separators=(',',':'))),q(x['desc']),q(x['ref']),x['osm'],'TRUE' if x['loop'] else 'FALSE',q(x['area']))
TRAIL_COLS="(id, name, region, distance_km, elevation_gain_m, est_duration_min, difficulty, start_lat, start_lon, route_points, description, ref, osm_relation_id, is_loop, ipma_area, coastal)"
osm2id={x['osm']:x['id'] for x in rows}
ph=[]
for osm,lst in photos.items():
    tid=osm2id.get(int(osm))
    if not tid: continue
    for pos,(path,lic,author,alt) in enumerate(lst,1):
        fname=path.split('/')[-1]
        image='https://upload.wikimedia.org/wikipedia/commons/'+path
        thumb=image if path.startswith('0/0b/') else 'https://upload.wikimedia.org/wikipedia/commons/thumb/'+path+'/960px-'+fname
        src='https://commons.wikimedia.org/wiki/File:'+fname
        ph.append("(%d,%d,%s,%s,%s,%s,%s,%s)"%(tid,pos,q(image),q(thumb),q(src),q(author),q(lic),q(alt)))
RULES_NEW="""('IPMA_Y','official','ipma_warning_level','>=',1,'caution','IPMA yellow warning for the district','Follow the IPMA warning: check its type, take extra layers and keep the hike short.',FALSE),
('IPMA_O','official','ipma_warning_level','>=',2,'warning','IPMA orange or red warning for the district','Do not start this hike: an official orange/red warning covers your hiking hours.',FALSE),
('FIRE_C','fire','fire_risk','>=',3,'caution','Elevated rural fire risk','No fires or stoves; stay on marked paths and know your exits.',FALSE),
('FIRE_W','fire','fire_risk','>=',4,'warning','Very high or maximum rural fire risk','Avoid forest and scrubland trails today; access to some areas may be restricted.',FALSE),
('TRAIL_CL','trail','trail_closed','>=',1,'warning','Trail reported closed by a hiker','Check the latest report and choose another trail.',TRUE)"""
BASE_RULES="""('RAIN_C','rain','precip_total_mm','>=',2,'caution','Rain expected during the hike','Pack a waterproof jacket; rocks and trails may be slippery.',TRUE),
('RAIN_W','rain','precip_total_mm','>=',10,'warning','Heavy rain during the hike','Postpone or choose a short, low trail with an easy way out.',TRUE),
('WIND_C','wind','gust_max_kmh','>=',40,'caution','Strong wind gusts','Avoid exposed ridges and cliff edges; secure loose gear.',TRUE),
('WIND_W','wind','gust_max_kmh','>=',60,'warning','Dangerous wind gusts','Do not hike exposed routes; pick a sheltered valley trail or postpone.',TRUE),
('HEAT_C','heat','temp_max_c','>=',30,'caution','High temperature','Carry at least 2 L of water and avoid the midday hours.',TRUE),
('COLD_C','cold','temp_min_c','<=',3,'caution','Near-freezing temperature','Wear layers, a hat and gloves; check for ice on shaded paths.',TRUE),
('DAY_C','daylight','daylight_margin_min','<',60,'caution','Finishing close to sunset','Bring a head torch and keep a steady pace.',TRUE),
('DAY_W','daylight','daylight_margin_min','<',30,'warning','Finishing after or near sunset','Start by {latest_start} or choose a shorter trail.',TRUE)"""

seed=["-- TrailSense seed data (demo). Password for every demo account: trailsense123",
"-- Trails: real OpenStreetMap hiking relations (© OpenStreetMap contributors, ODbL); elevation: Open-Meteo; photos: Wikimedia Commons (see trail_photo).",
"","INSERT INTO risk_rule (code, factor, metric, comparator, threshold, level, name, precaution, required) VALUES",BASE_RULES+",",RULES_NEW+";","",
"INSERT INTO trail "+TRAIL_COLS+" VALUES",",\n".join(trail_values(x) for x in rows)+";",
"SELECT setval('trail_id_seq', (SELECT MAX(id) FROM trail));","",
"INSERT INTO trail_photo (trail_id, position, image_url, thumb_url, source_url, author, license, alt_text) VALUES",",\n".join(ph)+";","",
f"""INSERT INTO app_user (name, email, password_hash, role) VALUES
('Joana Silva','joana@trailsense.pt','{H}','hiker'),
('Bruno Costa','bruno@trailsense.pt','{H}','organizer'),
('Rita Moreira','rita@trailsense.pt','{H}','hiker'),
('Miguel Antunes','miguel@trailsense.pt','{H}','hiker');
"""]
DEMO="""-- Plans relative to today so the demo always has upcoming hikes
INSERT INTO hike_plan (user_id, trail_id, planned_date, start_time, duration_min, notes) VALUES
(1, 1, CURRENT_DATE + 4, '15:00', 75,  'First hike of the season'),
(1, 3, CURRENT_DATE + 11, '10:00', 130, ''),
(2, 3, CURRENT_DATE + 4, '09:30', 150, 'Club Saturday hike'),
(2, 4, CURRENT_DATE + 11, '09:00', 220, 'Paiva walkways, morning group'),
(1, 2, CURRENT_DATE - 6, '10:00', 120, 'Santa Justa ridge'),
(2, 7, CURRENT_DATE + 1, '10:00', 150, 'Gerês day trip');
INSERT INTO group_plan (plan_id, group_size, meeting_point, meeting_time) VALUES
(3, 8, 'Valongo train station', '09:00'),
(4, 12, 'Areinho car park, Arouca', '08:30'),
(6, 10, 'Gerês village, bus stop', '09:30');
INSERT INTO hike_participant (plan_id, user_id, joined_at) VALUES
(3, 1, now() - interval '2 days'), (3, 3, now() - interval '1 day'), (4, 4, now() - interval '3 days'), (6, 3, now() - interval '5 hours');

INSERT INTO activity (user_id, trail_id, plan_id, hiked_on, distance_km, duration_min, notes, created_at) VALUES
(3, 7, NULL, CURRENT_DATE - 9, 3.8, 125, 'Misty start but the view from the Preguiça viewpoint opened up at the top. Well marked; the descent is slippery on wet granite, so take your time.', now() - interval '9 days'),
(4, 4, NULL, CURRENT_DATE - 5, 8.8, 185, 'Went early to beat the crowds. The stairs at the Espiunca end are tough for beginners: start from Areinho if you can.', now() - interval '5 days'),
(2, 3, NULL, CURRENT_DATE - 3, 5.8, 140, 'Club hike with 9 people. Wind picked up on the ridge exactly as the check predicted, we used the lower path back.', now() - interval '3 days'),
(1, 2, 5, CURRENT_DATE - 6, 4.1, 115, 'Short but steep in places. Loved the old Roman mine galleries — bring a torch to peek inside.', now() - interval '1 day');
INSERT INTO activity_like (activity_id, user_id) VALUES (1, 1), (1, 2), (2, 1), (2, 3), (3, 1), (3, 3), (3, 4), (4, 3);
INSERT INTO activity_comment (activity_id, user_id, body, created_at) VALUES
(2, 1, 'Thanks for the tip about starting at Areinho!', now() - interval '4 days'),
(3, 3, 'Great group, see you on the next one.', now() - interval '2 days'),
(4, 2, 'Nice! The galleries are even better after rain.', now() - interval '20 hours');
INSERT INTO trail_report (trail_id, user_id, condition, note, created_at) VALUES
(1, 3, 'muddy', 'Riverside path very muddy after the rain, boots needed.', now() - interval '2 days'),
(4, 4, 'good', 'Walkways dry and open.', now() - interval '5 days'),
(8, 2, 'obstacle', 'Fallen pine across the path just before the summit, easy to step around.', now() - interval '20 days'),
(2, 1, 'good', '', now() - interval '40 days');
"""
seed.append(DEMO)
open('/home/claude/trailsense/database/seed.sql','w').write("\n".join(seed))

# ---- migration for databases created by release 1 ----
mig=["-- Release 2 (2026-11-17): real OSM trails, photos, IPMA rules, group participants, reports, likes, comments (ADR-010..014).",
"-- Applied once by Core\\Installer; runs inside a transaction.",
"""ALTER TABLE trail ADD COLUMN ref VARCHAR(20) NOT NULL DEFAULT '',
    ADD COLUMN osm_relation_id BIGINT UNIQUE,
    ADD COLUMN is_loop BOOLEAN NOT NULL DEFAULT FALSE,
    ADD COLUMN ipma_area CHAR(3) NOT NULL DEFAULT 'PTO' CHECK (ipma_area ~ '^[A-Z]{3}$'),
    ADD COLUMN coastal BOOLEAN NOT NULL DEFAULT FALSE;
ALTER TABLE risk_rule ADD COLUMN required BOOLEAN NOT NULL DEFAULT TRUE;
ALTER TABLE risk_rule DROP CONSTRAINT IF EXISTS risk_rule_factor_check, DROP CONSTRAINT IF EXISTS risk_rule_metric_check;
ALTER TABLE risk_rule ADD CONSTRAINT risk_rule_factor_check CHECK (factor IN ('rain', 'wind', 'heat', 'cold', 'daylight', 'official', 'fire', 'trail')),
    ADD CONSTRAINT risk_rule_metric_check CHECK (metric IN ('precip_total_mm', 'gust_max_kmh', 'temp_max_c', 'temp_min_c', 'daylight_margin_min', 'ipma_warning_level', 'fire_risk', 'trail_closed'));
ALTER TABLE weather_snapshot ADD COLUMN alerts_source VARCHAR(10) NOT NULL DEFAULT 'none' CHECK (alerts_source IN ('ipma', 'demo', 'none')),
    ADD COLUMN warning_level SMALLINT CHECK (warning_level BETWEEN 0 AND 3),
    ADD COLUMN warnings JSONB NOT NULL DEFAULT '[]',
    ADD COLUMN fire_risk SMALLINT CHECK (fire_risk BETWEEN 1 AND 5),
    ADD COLUMN alerts_message VARCHAR(300) NOT NULL DEFAULT '';"""]
sch=open('/home/claude/trailsense/database/schema.sql').read()
import re
for tbl in ['trail_photo','hike_participant','trail_report','activity_like','activity_comment']:
    m=re.search(r'CREATE TABLE '+tbl+r' \(.*?\n\);',sch,re.S); mig.append(m.group(0))
for idx in re.findall(r'CREATE INDEX idx_(?:report_trail_date|comment_activity) ON [^;]+;',sch): mig.append(idx)
mig.append("INSERT INTO risk_rule (code, factor, metric, comparator, threshold, level, name, precaution, required) VALUES\n"+RULES_NEW+";")
mig.append("-- free the (name, region) pairs before renaming: some new names were used by other release-1 trails\nUPDATE trail SET name = name || ' [r1-' || id || ']' WHERE id <= 10;")
for x in rows:
    if x['id']<=10:
        mig.append("UPDATE trail SET name=%s, region=%s, distance_km=%s, elevation_gain_m=%d, est_duration_min=%d, difficulty=%s, start_lat=%s, start_lon=%s, route_points=%s, description=%s, ref=%s, osm_relation_id=%d, is_loop=%s, ipma_area=%s, coastal=FALSE WHERE id=%d;"%(q(x['name']),q(x['region']),x['km'],x['gain'],x['mins'],q(x['diff']),x['lat'],x['lon'],q(json.dumps(x['pts'],separators=(',',':'))),q(x['desc']),q(x['ref']),x['osm'],'TRUE' if x['loop'] else 'FALSE',q(x['area']),x['id']))
mig.append("INSERT INTO trail "+TRAIL_COLS+" VALUES\n"+",\n".join(trail_values(x) for x in rows if x['id']>10)+"\nON CONFLICT (id) DO NOTHING;")
mig.append("SELECT setval('trail_id_seq', (SELECT MAX(id) FROM trail));")
mig.append("INSERT INTO trail_photo (trail_id, position, image_url, thumb_url, source_url, author, license, alt_text) VALUES\n"+",\n".join(ph)+";")
mig.append("""-- Demo content refreshed so it matches the new trails (only rows that still hold the release-1 demo text)
UPDATE group_plan SET meeting_point = 'Areinho car park, Arouca', meeting_time = '08:30' WHERE plan_id = 4 AND meeting_point = 'Valongo centre, church square';
UPDATE hike_plan SET start_time = '09:00', duration_min = 220, notes = 'Paiva walkways, morning group' WHERE id = 4 AND notes = 'Mills route, afternoon group';
UPDATE hike_plan SET notes = 'Santa Justa ridge' WHERE id = 5 AND notes = 'Seafront walk';
UPDATE activity SET distance_km = 3.8, duration_min = 125, notes = 'Misty start but the view from the Preguiça viewpoint opened up at the top. Well marked; the descent is slippery on wet granite, so take your time.' WHERE id = 1 AND notes LIKE 'Misty start%';
UPDATE activity SET distance_km = 8.8 WHERE id = 2 AND notes LIKE 'Went early%';
UPDATE activity SET distance_km = 5.8, duration_min = 140 WHERE id = 3 AND notes LIKE 'Club hike%';
UPDATE activity SET distance_km = 4.1, duration_min = 115, notes = 'Short but steep in places. Loved the old Roman mine galleries — bring a torch to peek inside.' WHERE id = 4 AND notes LIKE 'Easy and flat%';
INSERT INTO hike_participant (plan_id, user_id) SELECT 3, 1 WHERE EXISTS (SELECT 1 FROM group_plan WHERE plan_id = 3) ON CONFLICT DO NOTHING;
INSERT INTO hike_participant (plan_id, user_id) SELECT 3, 3 WHERE EXISTS (SELECT 1 FROM group_plan WHERE plan_id = 3) AND EXISTS (SELECT 1 FROM app_user WHERE id = 3) ON CONFLICT DO NOTHING;
INSERT INTO activity_like (activity_id, user_id) SELECT a, u FROM (VALUES (1,1),(1,2),(2,1),(2,3),(3,1),(3,3),(3,4),(4,3)) v(a,u)
    WHERE EXISTS (SELECT 1 FROM activity WHERE id = a) AND EXISTS (SELECT 1 FROM app_user WHERE id = u) ON CONFLICT DO NOTHING;
INSERT INTO trail_report (trail_id, user_id, condition, note, created_at) SELECT 1, 3, 'muddy', 'Riverside path very muddy after the rain, boots needed.', now() - interval '2 days' WHERE EXISTS (SELECT 1 FROM app_user WHERE id = 3);
INSERT INTO trail_report (trail_id, user_id, condition, note, created_at) SELECT 8, 2, 'obstacle', 'Fallen pine across the path just before the summit, easy to step around.', now() - interval '20 days' WHERE EXISTS (SELECT 1 FROM app_user WHERE id = 2);
-- assessments made with the old trail geometry are outdated: they are recomputed on the next visit
DELETE FROM risk_assessment;
DELETE FROM weather_snapshot;""")
import os; os.makedirs('/home/claude/trailsense/database/migrations',exist_ok=True)
open('/home/claude/trailsense/database/migrations/002_release2.sql','w').write("\n\n".join(mig)+"\n")
print('photos',len(ph))
