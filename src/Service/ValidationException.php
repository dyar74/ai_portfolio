<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Thrown when a demo request fails validation.
 * Carries the list of violations for the 422 response.
 */
final class ValidationException extends \RuntimeException
{
    /** @param array<int, array{field: string, message: string}> $violations */
    public function __construct(
        private readonly array $violations,
    ) {
        parent::__construct('Validation failed');
    }

    /** @return array<int, array{field: string, message: string}> */
    public function getViolations(): array
    {
        return $this->violations;
    }
}
