<?php
declare(strict_types=1);

namespace TrailSense\Domain;

/**
 * Sources and credits shown on the public "Sources" page (r=sources) and in the report.
 * Every number TrailSense quotes about the problem comes from one of these (docs/VALUE_CASE.md).
 */
final class Sources
{
    /** @return array<string, array{title:string, intro:string, items: list<array{name:string, what:string, url:string}>}> */
    public static function all(): array
    {
        return [
            'data' => [
                'title' => 'Data the app uses',
                'intro' => 'Live or stored data behind every screen. Each one is shown with its source where it is used.',
                'items' => [
                    ['name' => 'Open-Meteo — weather forecast', 'what' => 'Hour-by-hour forecast for the hike window and the 7-day outlook on each trail.', 'url' => 'https://open-meteo.com/'],
                    ['name' => 'Open-Meteo — elevation', 'what' => 'Terrain heights (90 m model) for the elevation profiles and climbs, fetched once and stored.', 'url' => 'https://open-meteo.com/en/docs/elevation-api'],
                    ['name' => 'IPMA — Portuguese weather service', 'what' => 'Official weather warnings by district and rural fire risk by municipality.', 'url' => 'https://www.ipma.pt/'],
                    ['name' => 'OpenStreetMap', 'what' => 'Trail routes and map tiles. © OpenStreetMap contributors, ODbL.', 'url' => 'https://www.openstreetmap.org/copyright'],
                    ['name' => 'OpenTopoMap', 'what' => 'Optional topographic map layer (CC-BY-SA).', 'url' => 'https://opentopomap.org/'],
                    ['name' => 'Waymarked Trails', 'what' => 'Optional layer with the marked hiking routes.', 'url' => 'https://hiking.waymarkedtrails.org/'],
                    ['name' => 'Wikimedia Commons', 'what' => 'Trail photos; author and licence shown on each photo.', 'url' => 'https://commons.wikimedia.org/'],
                    ['name' => 'Leaflet 1.9.4', 'what' => 'Open-source map library (BSD-2).', 'url' => 'https://leafletjs.com/'],
                ],
            ],
            'rules' => [
                'title' => 'Trail marks and rules',
                'intro' => 'Where the explanations of routes, marks and closures come from.',
                'items' => [
                    ['name' => 'Rota Vicentina — Trail marking and maintenance manual', 'what' => 'Colours and shapes of the marks, distances between marks, turn and confirmation marks.', 'url' => 'https://rotavicentina.com/wp-content/uploads/2023/01/Manual-dos-Trilhos-Pedestres-Rota-Vicentina-Sinaletica-e-Manutencao-compressed.pdf'],
                    ['name' => 'FCMP — Federação de Campismo e Montanhismo de Portugal', 'what' => 'Registers and approves walking routes (PR, GR, PL) in Portugal.', 'url' => 'https://fcmportugal.com/'],
                    ['name' => 'Decreto-Lei n.º 82/2021 (Diário da República)', 'what' => 'Integrated rural fire management system; basis for access restrictions in high fire risk.', 'url' => 'https://data.dre.pt/eli/dec-lei/82/2021/10/13/p/dre'],
                ],
            ],
            'evidence' => [
                'title' => 'Evidence for the problem',
                'intro' => 'News and reports behind the numbers in the project (full table in docs/VALUE_CASE.md).',
                'items' => [
                    ['name' => 'DN / Lusa (Aug 2026)', 'what' => 'GNR: 425 protection and rescue operations in mountain areas since 2024; Serra da Estrela 138 in 2024 and 115 in 2025.', 'url' => 'https://www.dn.pt/sociedade/gnr-realizou-mais-de-400-operaes-de-proteo-e-socorro-em-reas-de-montanha-desde-2024'],
                    ['name' => 'DNoticias.pt (Aug 2022)', 'what' => 'Madeira: 68 accidents on levadas and walking routes by mid-August 2022.', 'url' => 'https://www.dnoticias.pt/2022/8/15/324043-madeira-regista-68-acidentes-em-percursos-pedestres-este-ano/'],
                    ['name' => 'DNoticias.pt (Dec 2023)', 'what' => 'How a mountain rescue works in Madeira; 24 accidents on walking routes in May 2023 alone.', 'url' => 'https://www.dnoticias.pt/2023/12/8/385929-saiba-como-decorre-um-resgate-nas-serras-da-madeira/'],
                    ['name' => 'Notícias ao Minuto (Aug 2024)', 'what' => 'A ~10-hour rescue on a trail in Peneda-Gerês and the rescuers’ advice to hikers.', 'url' => 'https://www.noticiasaominuto.com/pais/2611139/mulher-resgatada-em-trilho-no-geres-operacao-durou-quase-10-horas'],
                    ['name' => 'DNoticias.pt (Feb 2026)', 'what' => 'Political debate in Madeira about safety on walking routes.', 'url' => 'https://www.dnoticias.pt/2026/2/10/480919-ps-diz-que-negligencia-do-gr-contribui-para-falhas-na-seguranca-dos-trilhos/'],
                    ['name' => 'e-konomista.pt citing FCMP', 'what' => '8,885 km of PR and 4,386 km of GR approved; 382 promoters in 273 municipalities; marks at most 250 m apart.', 'url' => 'https://www.e-konomista.pt/marcas-trilhos/'],
                    ['name' => 'Rota Vicentina — impact on the region', 'what' => 'About 24,000 walkers in 2017.', 'url' => 'https://rotavicentina.com/en/impact-on-the-region/'],
                    ['name' => 'Notícias ao Minuto (Jul 2026)', 'what' => 'Warnings and active fires are followed in separate apps.', 'url' => 'https://www.noticiasaominuto.com/tech/3030222/incendios-em-portugal-as-3-apps-que-nao-podem-faltar-no-seu-telemovel'],
                ],
            ],
            'compare' => [
                'title' => 'Existing apps we compared with',
                'intro' => 'What the big trail apps already offer, so TrailSense does not claim what they do.',
                'items' => [
                    ['name' => 'AllTrails — Peak membership (press release, May 2025)', 'what' => 'Paid tiers with offline maps, wrong-turn alerts and trail conditions.', 'url' => 'https://www.alltrails.com/press/alltrails-expands-membership-offering-with-alltrails-peak'],
                    ['name' => 'komoot Premium', 'what' => 'Turn-by-turn voice navigation, offline maps and weather on route.', 'url' => 'https://www.komoot.com/premium'],
                    ['name' => 'Wikiloc (App Store)', 'what' => 'GPS navigation with off-track alerts in Premium.', 'url' => 'https://apps.apple.com/app/432102730'],
                ],
            ],
        ];
    }
}
