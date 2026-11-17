<?php
declare(strict_types=1);

namespace TrailSense\Controllers;

use TrailSense\Core\Auth;
use TrailSense\Core\Csrf;
use TrailSense\Core\Database;
use TrailSense\Core\Flash;
use TrailSense\Core\HttpException;
use TrailSense\Core\Request;
use TrailSense\Core\View;
use TrailSense\Repositories\ParticipantRepository;

/** F9.2: hikers find and join organizer group hikes. */
final class GroupController
{
    private ParticipantRepository $participants;

    public function __construct()
    {
        $this->participants = new ParticipantRepository(Database::connection());
    }

    public function index(): void
    {
        $user = Auth::requireLogin();
        $hikes = $this->participants->upcomingGroupHikes($user['id']);
        if (\TrailSense\Services\AssessmentService::checkUnchecked(Database::connection(), $hikes)) {
            $hikes = $this->participants->upcomingGroupHikes($user['id']);
        }
        View::render('groups/index', [
            'title' => 'Group hikes',
            'active' => 'groups',
            'hikes' => $hikes,
        ]);
    }

    public function join(): void
    {
        Csrf::check();
        $user = Auth::requireLogin();
        $planId = Request::id() ?? throw HttpException::notFound();
        $result = $this->participants->join($planId, $user['id']);
        $messages = [
            'joined' => ['success', 'You joined the group hike. The briefing is on the hike page.'],
            'already' => ['info', 'You are already in this group hike.'],
            'full' => ['info', 'Sorry, this group hike is full.'],
            'own' => ['info', 'You organise this hike.'],
            'not-open' => ['info', 'This group hike is no longer open.'],
        ];
        Flash::set(...$messages[$result]);
        $result === 'joined' || $result === 'already' || $result === 'own'
            ? View::redirect('plan', ['id' => $planId])
            : View::redirect('groups');
    }

    public function leave(): void
    {
        Csrf::check();
        $user = Auth::requireLogin();
        $planId = Request::id() ?? throw HttpException::notFound();
        if (!$this->participants->leave($planId, $user['id'])) {
            throw HttpException::forbidden();
        }
        Flash::set('info', 'You left the group hike.');
        View::redirect('groups');
    }
}
