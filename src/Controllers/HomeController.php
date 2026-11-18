<?php
declare(strict_types=1);

namespace TrailSense\Controllers;

use TrailSense\Core\Auth;
use TrailSense\Core\Database;
use TrailSense\Core\View;
use TrailSense\Repositories\ActivityRepository;
use TrailSense\Services\AssessmentService;
use TrailSense\Repositories\PlanRepository;

/** Role-specific home screen (AC8.4, A1 "two roles, two views"). */
final class HomeController
{
    public function index(): void
    {
        $user = Auth::requireLogin();
        $db = Database::connection();
        $planRepo = new PlanRepository($db);
        $plans = $planRepo->forUser($user['id']);

        if (AssessmentService::checkUnchecked($db, $plans['upcoming'])) {   // same checks every time (A1)
            $plans = $planRepo->forUser($user['id']);
        }
        $feed = (new ActivityRepository($db))->feed(3);

        if ($user['role'] === 'organizer') {
            View::render('home/organizer', [
                'title' => 'My group hikes',
                'active' => 'home',
                'plans' => $plans['upcoming'],
                'kpi' => $planRepo->completeness($user['id']),
                'feed' => $feed,
            ]);
            return;
        }
        $open = array_values(array_filter(
            (new \TrailSense\Repositories\ParticipantRepository($db))->upcomingGroupHikes($user['id'], null, 6),
            static fn (array $h) => !in_array($h['joined'], [true, 't'], true)
        ));
        View::render('home/hiker', [
            'groupHikes' => array_slice($open, 0, 2),
            'title' => 'Home',
            'active' => 'home',
            'next' => $plans['upcoming'][0] ?? null,
            'more' => array_slice($plans['upcoming'], 1, 2),
            'feed' => (new ActivityRepository($db))->feed(3),
            'stats' => (new \TrailSense\Repositories\ProfileRepository($db))->stats($user['id']),
            'discover' => array_slice(array_values(array_filter((new \TrailSense\Repositories\TrailRepository($db))->search(),
                static fn (array $t) => !empty($t['thumb_url']))), 0, 8),
            'pastCount' => count($plans['past']),
        ]);
    }
}
