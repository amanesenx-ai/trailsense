<?php
declare(strict_types=1);

namespace TrailSense\Core;

final class HttpException extends \RuntimeException
{
    public static function notFound(): self
    {
        return new self('Page not found', 404);
    }

    public static function forbidden(): self
    {
        return new self('You do not have access to this page', 403);
    }

    public static function badRequest(string $message = 'Bad request'): self
    {
        return new self($message, 400);
    }
}
