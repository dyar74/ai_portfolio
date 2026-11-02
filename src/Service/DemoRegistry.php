<?php

namespace App\Service;

/**
 * Registry mapping demo slugs to their n8n webhook paths.
 */
final class DemoRegistry
{
    /** @var array<string, array{webhookPath: string, name: string}> */
    private array $demos = [
        'contact-to-telegram' => [
            'webhookPath' => 'contact-to-telegram',
            'name' => 'Contact to Telegram',
        ],
        'ai-translate-en-uk' => [
            'webhookPath' => 'ai-translate-en-uk',
            'name' => 'AI Translate EN/UK',
        ],
        'url-summarizer' => [
            'webhookPath' => 'url-summarizer',
            'name' => 'URL Summarizer',
        ],
        'lead-scoring' => [
            'webhookPath' => 'lead-scoring',
            'name' => 'Lead Scoring',
        ],
    ];

    public function get(string $slug): ?array
    {
        return $this->demos[$slug] ?? null;
    }

    public function has(string $slug): bool
    {
        return isset($this->demos[$slug]);
    }

    /** @return array<string, array{webhookPath: string, name: string}> */
    public function all(): array
    {
        return $this->demos;
    }

    /** @return string[] */
    public function slugs(): array
    {
        return array_keys($this->demos);
    }
}
