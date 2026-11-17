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
use TrailSense\Repositories\SocialRepository;
use TrailSense\Repositories\TrailRepository;
use TrailSense\Services\PhotoResizer;

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

    public function show(array $errors = [], string $draft = ''): void
    {
        $user = Auth::requireLogin();
        $post = $this->postOr404();
        $social = new SocialRepository(Database::connection());
        View::render('feed/show', [
            'title' => $post['trail_name'], 'active' => 'feed', 'post' => $post,
            'liked' => $social->likedBy((int) $post['id'], $user['id']),
            'comments' => $social->comments((int) $post['id']),
            'errors' => $errors, 'draft' => $draft,
        ], $errors ? 422 : 200);
    }

    /** AC6.4: like/unlike (one like per user, enforced by the primary key). */
    public function like(): void
    {
        Csrf::check();
        $user = Auth::requireLogin();
        $post = $this->postOr404();
        (new SocialRepository(Database::connection()))->toggleLike((int) $post['id'], $user['id']);
        $back = Request::post('back') === 'feed' ? 'feed' : 'activity';
        $back === 'feed' ? View::redirect('feed') : View::redirect('activity', ['id' => $post['id']]);
    }

    public function comment(): void
    {
        Csrf::check();
        $user = Auth::requireLogin();
        $post = $this->postOr404();
        $body = Request::post('body');
        if (mb_strlen($body) < 1 || mb_strlen($body) > 500) {
            $this->show(['body' => 'Write a comment of 1–500 characters.'], $body);
            return;
        }
        (new SocialRepository(Database::connection()))->addComment((int) $post['id'], $user['id'], $body);
        View::redirect('activity', ['id' => $post['id']]);
    }

    public function deleteComment(): void
    {
        Csrf::check();
        $user = Auth::requireLogin();
        $activityId = (new SocialRepository(Database::connection()))->deleteOwnComment(Request::id() ?? 0, $user['id']);
        if ($activityId === null) {
            throw HttpException::forbidden();       // not your comment (or it does not exist)
        }
        Flash::set('info', 'Comment deleted.');
        View::redirect('activity', ['id' => $activityId]);
    }

    private function postOr404(): array
    {
        $id = Request::id();
        $post = $id ? $this->activities->find($id) : null;
        return $post ?? throw HttpException::notFound();
    }

    /** Serves a stored photo. Only signed-in users (the feed is for members). */
    public function photo(): void
    {
        Auth::requireLogin();
        $name = Request::get('f');
        $photo = preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $name) ? $this->activities->photo($name) : null;
        if ($photo === null) {
            throw HttpException::notFound();
        }
        header('Content-Type: ' . $photo['mime']);
        header('Content-Length: ' . strlen($photo['bytes']));
        header('Cache-Control: private, max-age=604800, immutable');
        header('Content-Security-Policy: default-src \'none\'');
        echo $photo['bytes'];
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
        [$mime, $photoError] = PhotoValidator::check($_FILES['photo'] ?? null);
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

        $photo = null;
        if ($mime !== null) {
            $bytes = file_get_contents($_FILES['photo']['tmp_name']);
            if ($bytes === false || !is_uploaded_file($_FILES['photo']['tmp_name'])) {
                $this->create(['photo' => 'The photo could not be saved. Please try again.'], $input);
                return;
            }
            // AC7.2: random public name, never the user's file name; bytes stored in the DB (ADR-009)
            $small = PhotoResizer::shrink($bytes, $mime);      // ≤ 1600 px JPEG, metadata stripped
            $photo = ['name' => bin2hex(random_bytes(16)) . '.' . $small['ext'], 'mime' => $small['mime'], 'bytes' => $small['bytes']];
        }
        $alt = mb_substr($input['photo_alt'], 0, 150);
        $id = $this->activities->create($user['id'], $data, $planId, $photo, $alt);
        Flash::set('success', 'Your hike is published in the community feed.');
        View::redirect('activity', ['id' => $id]);
    }
}
