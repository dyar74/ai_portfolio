<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\ContactRequest;
use App\Dto\LeadScoringRequest;
use App\Dto\SummarizerRequest;
use App\Dto\TranslateRequest;
use App\Message\RecordDemoRunMessage;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Encapsulates the demo execution pipeline:
 *   1. Build the DTO from the raw payload
 *   2. Validate the DTO (throws ValidationException)
 *   3. Call n8n (throws N8nTimeoutException / N8nServerErrorException)
 *   4. Dispatch async DemoRun recording (best-effort, never breaks the response)
 *
 * The controller is a thin orchestrator that maps exceptions to HTTP status codes.
 */
final class DemoExecutor
{
    private const ALLOWED_LOCALES = ['en', 'uk'];

    public function __construct(
        private readonly N8nClient $n8nClient,
        private readonly DemoRegistry $registry,
        private readonly ValidatorInterface $validator,
        private readonly MessageBusInterface $bus,
    ) {}

    /**
     * Execute a demo: validate → call n8n → record async → return normalized result.
     *
     * @param array<string, mixed> $payload
     * @param string $locale
     * @param string $requestId
     * @return array<string, mixed>
     *
     * @throws ValidationException
     * @throws N8nTimeoutException
     * @throws N8nServerErrorException
     */
    public function execute(string $slug, array $payload, string $locale, string $requestId): array
    {
        $demo = $this->registry->get($slug);
        if ($demo === null) {
            throw new \RuntimeException('Unknown demo: ' . $slug);
        }

        $dto = $this->buildAndValidate($slug, $payload, $locale);
        $started = microtime(true);
        $status = 'ok';

        try {
            $result = $this->n8nClient->sendWebhook(
                $demo['webhookPath'],
                $this->buildN8nPayload($payload, $locale),
                $requestId,
            );
        } catch (\App\Service\N8nTimeoutException | \App\Service\N8nServerErrorException $e) {
            $status = $e instanceof \App\Service\N8nTimeoutException ? 'timeout' : 'error';
            $this->dispatchRunRecorded($slug, $status, $started, $locale, $requestId);
            throw $e;
        }

        $this->dispatchRunRecorded($slug, $status, $started, $locale, $requestId);

        return $result;
    }

    /**
     * Build and validate the DTO for a given slug.
     *
     * @param array<string, mixed> $payload
     * @return object|null
     *
     * @throws ValidationException
     */
    private function buildAndValidate(string $slug, array $payload, string $locale): ?object
    {
        $dto = match ($slug) {
            'contact-to-telegram' => new ContactRequest(
                name: (string) ($payload['name'] ?? ''),
                email: (string) ($payload['email'] ?? ''),
                message: (string) ($payload['message'] ?? ''),
                locale: (string) ($payload['locale'] ?? $locale),
                honeypot: (string) ($payload['website'] ?? ''),
            ),
            'ai-translate-en-uk' => new TranslateRequest(
                text: (string) ($payload['text'] ?? ''),
                direction: (string) ($payload['direction'] ?? 'en-uk'),
                tone: (string) ($payload['tone'] ?? 'neutral'),
            ),
            'url-summarizer' => new SummarizerRequest(
                url: (string) ($payload['url'] ?? ''),
            ),
            'lead-scoring' => new LeadScoringRequest(
                description: (string) ($payload['description'] ?? ''),
            ),
            default => null,
        };

        if ($dto === null) {
            return null;
        }

        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = ['field' => $violation->getPropertyPath(), 'message' => $violation->getMessage()];
            }
            throw new ValidationException($errors);
        }

        return $dto;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function buildN8nPayload(array $payload, string $locale): array
    {
        return array_merge($payload, ['locale' => $locale]);
    }

    private function dispatchRunRecorded(string $slug, string $status, float $started, string $locale, string $requestId): void
    {
        try {
            $durationMs = (int) ((microtime(true) - $started) * 1000);
            $this->bus->dispatch(new RecordDemoRunMessage(
                $slug,
                $status,
                $durationMs,
                $locale,
                $requestId,
            ));
        } catch (\Throwable) {
            // Demo run recording is best-effort; never break the HTTP response.
        }
    }
}
