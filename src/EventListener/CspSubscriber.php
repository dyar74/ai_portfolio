<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Sets Content-Security-Policy header.
 * CSP is set by Symfony (not nginx) per the infra contract.
 */
final class CspSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly string $appEnv,
        private readonly string $turnstileSecret,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onResponse', 255],
        ];
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();

        // Only set CSP on HTML responses, not JSON API responses
        $contentType = $response->headers->get('Content-Type', '');
        if (str_contains($contentType, 'application/json')) {
            return;
        }

        $csp = "default-src 'self'";

        if ($this->appEnv === 'dev') {
            // Vite dev server: allow its origin for scripts + HMR websocket.
            // Vite injects inline <style> tags (HMR / component styles) and uses
            // inline <script> for the client runtime — both need 'unsafe-inline'.
            // 'unsafe-eval' is required by some dev-time tooling (HMR, Vue devtools).
            $csp .= "; script-src 'self' http://localhost:5173 'unsafe-inline' 'unsafe-eval'; connect-src 'self' http://localhost:5173 ws://localhost:5173; style-src 'self' http://localhost:5173 'unsafe-inline'";
        } else {
            // Prod: Vite build emits external files; no inline styles or eval needed.
            $csp .= "; style-src 'self'";
        }

        if ($this->turnstileSecret !== '') {
            $csp .= "; script-src 'self' https://challenges.cloudflare.com; frame-src https://challenges.cloudflare.com";
        }

        $response->headers->set('Content-Security-Policy', $csp);
    }
}
