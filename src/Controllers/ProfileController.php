<?php
declare(strict_types=1);

namespace TrailSense\Controllers;

use TrailSense\Core\Auth;
use TrailSense\Core\Database;
use TrailSense\Core\HttpException;
use TrailSense\Core\Request;
use TrailSense\Core\View;
use TrailSense\Repositories\ActivityRepository;
use TrailSense\Repositories\ProfileRepository;

/** F11 profile and statistics (public to signed-in users, no e-mail). */
final class ProfileController
{
    public function show(): void
    {
        $me = Auth::requireLogin();
        $db = Database::connection();
        $profiles = new ProfileRepository($db);
        $id = Request::id() ?? $me['id'];
        $profile = $profiles->user($id) ?? throw HttpException::notFound();
        View::render('profile/show', [
            'title' => $profile['name'],
            'active' => $id === $me['id'] ? 'account' : 'feed',
            'profile' => $profile,
            'isMe' => $id === $me['id'],
            'stats' => $profiles->stats($id),
            'monthly' => $profiles->monthly($id),
            'posts' => (new ActivityRepository($db))->byUser($id),
        ]);
    }
}
