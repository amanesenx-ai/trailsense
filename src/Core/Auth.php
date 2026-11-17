<?php
declare(strict_types=1);

namespace TrailSense\Core;

/** Session-based authentication and role checks (F8). */
final class Auth
{
    public static function login(array $user): void
    {
        session_regenerate_id(true);               // prevent session fixation (AC8.6)
        $_SESSION['user'] = [
            'id'   => (int) $user['id'],
            'name' => $user['name'],
            'role' => $user['role'],
        ];
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function id(): int
    {
        return (int) (self::user()['id'] ?? 0);
    }

    public static function isOrganizer(): bool
    {
        return (self::user()['role'] ?? '') === 'organizer';
    }

    /** Redirects anonymous users to the login page (AC8.5). */
    public static function requireLogin(): array
    {
        $user = self::user();
        if ($user === null) {
            Flash::set('info', 'Please sign in to continue.');
            header('Location: ' . url('login'));
            exit;
        }
        return $user;
    }

    public static function requireOrganizer(): array
    {
        $user = self::requireLogin();
        if ($user['role'] !== 'organizer') {
            throw HttpException::forbidden();
        }
        return $user;
    }
}
