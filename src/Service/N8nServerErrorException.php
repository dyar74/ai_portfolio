<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Thrown when n8n returns a 5xx status code (after retries are exhausted)
 * or an unrecoverable transport error.
 * Maps to HTTP 502 Bad Gateway in the controller.
 */
final class N8nServerErrorException extends N8nException
{
    public function __construct(
        private readonly int $statusCode,
        string $message = '',
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message !== '' ? $message : sprintf('n8n server error (%d)', $statusCode), 0, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
