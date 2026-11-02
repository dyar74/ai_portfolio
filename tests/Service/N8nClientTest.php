<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\N8nClient;
use App\Service\N8nServerErrorException;
use App\Service\N8nTimeoutException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class N8nClientTest extends TestCase
{
    private N8nClient $client;

    protected function setUp(): void
    {
        $this->client = new N8nClient(
            httpClient: new MockHttpClient(),
            baseUrl: 'https://n8n.example.com',
            webhookSecret: 'test-secret',
            hmacKey: 'test-hmac',
            apiKey: 'test-api-key',
        );
    }

    public function testSendWebhookSuccess(): void
    {
        $http = new MockHttpClient(function (string $method, string $url, array $options) {
            $this->assertSame('POST', $method);
            $this->assertStringContainsString('/webhook/contact-to-telegram', $url);
            $this->assertSame('test-secret', $options['headers']['X-Webhook-Secret']);
            $this->assertStringStartsWith('sha256=', $options['headers']['X-Signature']);
            $this->assertArrayHasKey('X-Request-Id', $options['headers']);

            return new MockResponse('{"ok":true,"category":"sales","priority":3}');
        });

        $client = $this->makeClient($http);
        $result = $client->sendWebhook('contact-to-telegram', ['name' => 'Test'], 'req-123');

        $this->assertSame(['ok' => true, 'category' => 'sales', 'priority' => 3], $result);
    }

    public function testSendWebhookRetriesOn502(): void
    {
        $attempts = 0;
        $http = new MockHttpClient(function (string $method, string $url, array $options) use (&$attempts) {
            $attempts++;
            if ($attempts === 1) {
                return new MockResponse('', ['status_code' => 502]);
            }
            return new MockResponse('{"ok":true}');
        });

        $client = $this->makeClient($http);
        $result = $client->sendWebhook('contact-to-telegram', ['name' => 'Test']);

        $this->assertSame(2, $attempts);
        $this->assertSame(['ok' => true], $result);
    }

    public function testSendWebhookThrowsOn502AfterRetry(): void
    {
        $http = new MockHttpClient(function () {
            return new MockResponse('', ['status_code' => 502]);
        });

        $client = $this->makeClient($http);

        $this->expectException(N8nServerErrorException::class);
        $this->expectExceptionCode(502);
        $client->sendWebhook('contact-to-telegram', ['name' => 'Test']);
    }

    public function testSendWebhookThrowsOn500(): void
    {
        $http = new MockHttpClient(function () {
            return new MockResponse('', ['status_code' => 500]);
        });

        $client = $this->makeClient($http);

        $this->expectException(N8nServerErrorException::class);
        $this->expectExceptionCode(500);
        $client->sendWebhook('contact-to-telegram', ['name' => 'Test']);
    }

    public function testCheckHealthOk(): void
    {
        $http = new MockHttpClient(function () {
            return new MockResponse('ok');
        });

        $client = $this->makeClient($http);
        $health = $client->checkHealth();

        $this->assertSame('ok', $health['status']);
        $this->assertIsInt($health['latencyMs']);
    }

    public function testCheckHealthDown(): void
    {
        $http = new MockHttpClient(function () {
            return new MockResponse('', ['status_code' => 503]);
        });

        $client = $this->makeClient($http);
        $health = $client->checkHealth();

        $this->assertSame('down', $health['status']);
    }

    public function testGetLastExecutionsNoApiKey(): void
    {
        $http = new MockHttpClient();
        $client = new N8nClient(
            httpClient: $http,
            baseUrl: 'https://n8n.example.com',
            webhookSecret: 'test-secret',
            hmacKey: 'test-hmac',
            apiKey: '',
        );

        $this->assertSame([], $client->getLastExecutions(5));
    }

    public function testGetLastExecutionsSuccess(): void
    {
        $http = new MockHttpClient(function () {
            return new MockResponse(json_encode([
                'data' => [
                    ['id' => 'exec-1', 'workflowName' => 'Test WF', 'status' => 'success', 'duration' => 150, 'startedAt' => '2024-01-01T00:00:00Z'],
                ],
            ]));
        });

        $client = $this->makeClient($http);
        $execs = $client->getLastExecutions(5);

        $this->assertCount(1, $execs);
        $this->assertSame('exec-1', $execs[0]['id']);
        $this->assertSame('Test WF', $execs[0]['workflowName']);
        $this->assertSame('success', $execs[0]['status']);
    }

    private function makeClient(\Symfony\Contracts\HttpClient\HttpClientInterface $http): N8nClient
    {
        return new N8nClient(
            httpClient: $http,
            baseUrl: 'https://n8n.example.com',
            webhookSecret: 'test-secret',
            hmacKey: 'test-hmac',
            apiKey: 'test-api-key',
        );
    }
}
