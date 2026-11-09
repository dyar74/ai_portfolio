<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Sets the Content-Security-Policy header on HTML responses.
 *
 * Policy goals:
 *   - No 'unsafe-eval' (blocks `new Function()` / `eval()`). The n8n
 *     url-summarizer workflow no longer uses Function() (it parses URLs with a
 *     regex), so the browser never needs eval.
 *   - Allow inline <script type="application/ld+json"> (structured data).
 *     These are data, not executable JS, so 'unsafe-inline' for script-src is
 *     safe here (we do not ship any inline executable scripts).
 *   - Turnstile (Cloudflare) scripts + frame allowed when the secret is set.
 *
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

        // Only set CSP on HTML responses, not JSON API responses.
        $contentType = $response->headers->get('Content-Type', '');
        if (str_contains($contentType, 'application/json')) {
            return;
        }

        // Base policy.
        $csp = "default-src 'self'";

        if ($this->appEnv === 'dev') {
            // Vite dev server: allow its origin for scripts + HMR websocket.
            // Vite injects inline <style> and an inline <script> for the client
            // runtime; 'unsafe-eval' is required by dev-time tooling (HMR, Vue
            // devtools). This is dev-only; the prod policy below is stricter.
            $scriptSrc = "'self' 'unsafe-inline' 'unsafe-eval' http://localhost:5173";
            $connectSrc = "'self' http://localhost:5173 ws://localhost:5173";
            $styleSrc = "'self' 'unsafe-inline' http://localhost:5173";
        } else {
            // Prod: Vite build emits external files. Allow inline <script>
            // (JSON-LD structured data in app.html.twig). No 'unsafe-eval'.
            $scriptSrc = "'self' 'unsafe-inline'";
            $connectSrc = "'self'";
            $styleSrc = "'self' 'unsafe-inline'";
        }

        // Turnstile (Cloudflare) — merge into the same script-src/frame-src to
        // avoid a duplicate directive (a second script-src would override the
        // first and drop 'unsafe-inline', breaking the inline JSON-LD).
        if ($this->turnstileSecret !== '') {
            $scriptSrc .= ' https://challenges.cloudflare.com';
            $csp .= '; frame-src https://challenges.cloudflare.com';
        }

        $csp .= "; script-src {$scriptSrc}; connect-src {$connectSrc}; style-src {$styleSrc}";

        $response->headers->set('Content-Security-Policy', $csp);
    }
}
