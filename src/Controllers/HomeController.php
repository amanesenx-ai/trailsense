<?php
declare(strict_types=1);

namespace TrailSense\Controllers;

use TrailSense\Core\Auth;
use TrailSense\Core\Database;
use TrailSense\Core\View;
use TrailSense\Repositories\ActivityRepository;
use TrailSense\Repositories\AssessmentRepository;
use TrailSense\Repositories\RuleRepository;
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

        // "The same condition checks every time": upcoming plans never checked are assessed automatically.
        $unchecked = array_filter($plans['upcoming'], static fn (array $p) => $p['overall_level'] === null);
        if ($unchecked !== []) {
            $service = new AssessmentService(AssessmentService::providerFromConfig(), new RuleRepository($db), new AssessmentRepository($db));
            foreach (array_slice($unchecked, 0, 5) as $p) {
                $service->assess((array) $planRepo->find((int) $p['id']));
            }
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
        View::render('home/hiker', [
            'title' => 'Home',
            'active' => 'home',
            'next' => $plans['upcoming'][0] ?? null,
            'more' => array_slice($plans['upcoming'], 1, 2),
            'feed' => $feed,
        ]);
    }
}
