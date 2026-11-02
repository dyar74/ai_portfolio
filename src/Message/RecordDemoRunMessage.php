<?php

namespace App\Message;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Async: persists a DemoRun row. The HTTP request waits only for n8n.
 */
class RecordDemoRunMessage
{
    public function __construct(
        public readonly string $slug,
        public readonly string $status,
        public readonly ?int $durationMs,
        public readonly string $locale,
        public readonly string $requestId,
    ) {}
}
