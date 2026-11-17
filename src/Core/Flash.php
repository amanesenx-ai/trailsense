<?php
declare(strict_types=1);

namespace TrailSense\Core;

/** One-time messages shown after a redirect (Post/Redirect/Get). */
final class Flash
{
    public static function set(string $type, string $message): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    public static function take(): ?array
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $flash;
    }
}
