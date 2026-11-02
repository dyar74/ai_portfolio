<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Base exception for n8n communication failures.
 * Subclasses let the controller map failures to HTTP status codes by CATCH TYPE
 * instead of string-matching exception messages (the old broken approach).
 */
abstract class N8nException extends \RuntimeException
{
}
