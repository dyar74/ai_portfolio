<?php

namespace App\Tests\Unit;

use App\Service\DemoRegistry;
use PHPUnit\Framework\TestCase;

class DemoRegistryTest extends TestCase
{
    private DemoRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new DemoRegistry();
    }

    public function testHasContactToTelegram(): void
    {
        $this->assertTrue($this->registry->has('contact-to-telegram'));
    }

    public function testHasTranslate(): void
    {
        $this->assertTrue($this->registry->has('ai-translate-en-uk'));
    }

    public function testHasSummarizer(): void
    {
        $this->assertTrue($this->registry->has('url-summarizer'));
    }

    public function testHasLeadScoring(): void
    {
        $this->assertTrue($this->registry->has('lead-scoring'));
    }

    public function testGetReturnsWebhookPath(): void
    {
        $demo = $this->registry->get('contact-to-telegram');
        $this->assertNotNull($demo);
        $this->assertSame('contact-to-telegram', $demo['webhookPath']);
    }

    public function testGetUnknownReturnsNull(): void
    {
        $this->assertNull($this->registry->get('unknown-demo'));
    }

    public function testSlugsReturnsAll(): void
    {
        $slugs = $this->registry->slugs();
        $this->assertCount(4, $slugs);
        $this->assertContains('contact-to-telegram', $slugs);
        $this->assertContains('ai-translate-en-uk', $slugs);
        $this->assertContains('url-summarizer', $slugs);
        $this->assertContains('lead-scoring', $slugs);
    }
}
