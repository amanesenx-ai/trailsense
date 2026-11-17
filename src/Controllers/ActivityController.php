<?php
declare(strict_types=1);

namespace TrailSense\Controllers;

use DateTimeImmutable;
use TrailSense\Core\Auth;
use TrailSense\Core\Csrf;
use TrailSense\Core\Database;
use TrailSense\Core\Flash;
use TrailSense\Core\HttpException;
use TrailSense\Core\Request;
use TrailSense\Core\View;
use TrailSense\Domain\ActivityValidator;
use TrailSense\Domain\PhotoValidator;
use TrailSense\Repositories\ActivityRepository;
use TrailSense\Repositories\PlanRepository;
use TrailSense\Repositories\TrailRepository;

/** F6 community feed, F7 publish a hike. */
final class ActivityController
{
    private ActivityRepository $activities;
    private TrailRepository $trails;

    public function __construct()
    {
        $db = Database::connection();
        $this->activities = new ActivityRepository($db);
        $this->trails = new TrailRepository($db);
    }

    public function feed(): void
    {
        Auth::requireLogin();
        View::render('feed/index', ['title' => 'Community', 'active' => 'feed', 'posts' => $this->activities->feed()]);
    }

    public function show(): void
    {
        Auth::requireLogin();
        $id = Request::id();
        $post = $id ? $this->activities->find($id) : null;
        if ($post === null) {
            throw HttpException::notFound();
        }
        View::render('feed/show', ['title' => $post['trail_name'], 'active' => 'feed', 'post' => $post]);
    }

    public function create(array $errors = [], array $old = []): void
    {
        $user = Auth::requireLogin();
        if ($old === []) {
            $old = ['hiked_on' => date('Y-m-d')];
            $planId = Request::id('plan_id');
            $plan = $planId ? (new PlanRepository(Database::connection()))->find($planId) : null;
            if ($plan !== null && (int) $plan['user_id'] === $user['id']) {
                $old = ['plan_id' => $plan['id'], 'trail_id' => $plan['trail_id'], 'hiked_on' => $plan['planned_date'],
                    'duration_min' => $plan['duration_min'], 'distance_km' => ''];
            }
        }
        View::render('feed/form', [
            'title' => 'Publish a hike',
            'active' => 'feed',
            'trails' => $this->trails->options(),
            'errors' => $errors,
            'old' => $old,
        ], $errors ? 422 : 200);
    }

    public function store(): void
    {
        Csrf::check();
        $user = Auth::requireLogin();
        $input = [];
        foreach (['trail_id', 'hiked_on', 'distance_km', 'duration_min', 'notes', 'plan_id', 'photo_alt'] as $f) {
            $input[$f] = Request::post($f);
        }
        [$data, $errors] = ActivityValidator::validate($input, fn (int $id) => $this->trails->find($id), new DateTimeImmutable('today'));
        [$ext, $photoError] = PhotoValidator::check($_FILES['photo'] ?? null);
        if ($photoError !== null) {
            $errors['photo'] = $photoError;
        }
        if ($errors !== []) {
            $this->create($errors, $input);
            return;
        }

        // AC7.3: link the plan only if it belongs to this user
        $planId = null;
        if (ctype_digit($input['plan_id'])) {
            $plan = (new PlanRepository(Database::connection()))->find((int) $input['plan_id']);
            $planId = ($plan !== null && (int) $plan['user_id'] === $user['id']) ? (int) $plan['id'] : null;
        }

        $fileName = null;
        if ($ext !== null) {
            $fileName = bin2hex(random_bytes(16)) . '.' . $ext;     // AC7.2: random name, never the user's
            $target = TS_ROOT . '/public/uploads/' . $fileName;
            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $target)) {
                $this->create(['photo' => 'The photo could not be saved. Please try again.'], $input);
                return;
            }
        }
        $alt = mb_substr($input['photo_alt'], 0, 150);
        $id = $this->activities->create($user['id'], $data, $planId, $fileName, $alt);
        Flash::set('success', 'Your hike is published in the community feed.');
        View::redirect('activity', ['id' => $id]);
    }
}
