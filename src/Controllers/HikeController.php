<?php
declare(strict_types=1);

namespace TrailSense\Controllers;

use TrailSense\Core\Auth;
use TrailSense\Core\Database;
use TrailSense\Core\HttpException;
use TrailSense\Core\Request;
use TrailSense\Core\View;
use TrailSense\Domain\Waymarks;
use TrailSense\Repositories\TrailRepository;

/** F13 hike mode: live map, compass, progress and waymark legend. Tracking stays in the browser (ADR-015). */
final class HikeController
{
    public function show(): void
    {
        Auth::requireLogin();
        $id = Request::id();
        $trail = $id ? (new TrailRepository(Database::connection()))->find($id) : null;
        if ($trail === null || count($trail['route_points']) < 2) {
            throw HttpException::notFound();
        }
        $minutes = max(1, (int) $trail['est_duration_min']);
        View::render('hike/show', [
            'title' => 'Hiking · ' . $trail['name'],
            'active' => 'trails',
            'trail' => $trail,
            'planId' => Request::id('plan_id'),
            'waymarks' => Waymarks::forTrail((string) $trail['ref'], (string) $trail['name']),
            'paceKmh' => round((float) $trail['distance_km'] / ($minutes / 60), 2),
            'withMap' => true,
            'bodyClass' => 'hike-mode',
            'scripts' => ['hike-core.js', 'hike-offline.js', 'hike-finish.js', 'hike.js'],
        ]);
    }
}
