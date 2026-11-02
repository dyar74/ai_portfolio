<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Thrown when an n8n webhook call exceeds the configured timeout (20s).
 * Maps to HTTP 504 Gateway Timeout in the controller.
 */
final class N8nTimeoutException extends N8nException
{
}
