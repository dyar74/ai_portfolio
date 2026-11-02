<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HealthControllerTest extends WebTestCase
{
    public function testHealth(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/health');

        $this->assertResponseIsSuccessful();

        $response = $client->getResponse();
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $this->assertSame('no-store', $response->headers->get('Cache-Control'));

        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('site', $data);
        $this->assertSame('ok', $data['site']);
    }
}
