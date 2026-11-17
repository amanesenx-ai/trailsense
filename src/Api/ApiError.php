<?php
declare(strict_types=1);

namespace TrailSense\Api;

final class ApiError extends \RuntimeException
{
    public function __construct(public readonly int $status, string $message)
    {
        parent::__construct($message);
    }
}
