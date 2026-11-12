<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Serves the SPA shell for /, /en/... and /uk/... with per-locale SEO metadata.
 * The browser then takes over via the Vue app.
 */
class AppController extends AbstractController
{
    /**
     * SEO copy per locale (role + descriptions are generic marketing copy).
     * The display NAME is env-driven (anonymization): SITE_NAME (EN) /
     * SITE_NAME_UK (UK) from the .env file. No hardcoded owner identity.
     */
    private const CONTENT = [
        'en' => [
            'role' => 'AI Automation Engineer',
            'description' => 'I design and ship reliable, multilingual automation systems with n8n, LLMs and AI agents. Live, working demos powered by a real n8n instance.',
            'ogDescription' => 'Multilingual (EN/UK) business-card site with live, working n8n demos.',
        ],
        'uk' => [
            'role' => 'Інженер з AI-автоматизації',
            'description' => 'Я проєктую та запускаю надійні багатомовні автоматизації з n8n, LLM та AI-агентами. Живі демо на реальному n8n.',
            'ogDescription' => 'Багатомовний (EN/UK) сайт-візитка з живими демо на n8n.',
        ],
    ];

    #[Route('/', name: 'app_root', methods: ['GET'])]
    #[Route('/en/{path}', name: 'app_en', requirements: ['path' => '[a-z0-9/_-]*'], methods: ['GET'])]
    #[Route('/uk/{path}', name: 'app_uk', requirements: ['path' => '[a-z0-9/_-]*'], methods: ['GET'])]
    public function index(Request $request, ?string $path = null): Response
    {
        $route = $request->attributes->get('_route');
        $locale = $route === 'app_uk' ? 'uk' : 'en';

        $content = self::CONTENT[$locale];

        // Anonymization: identity comes ONLY from the .env file (Symfony
        // dotenv populates $_SERVER). No hardcoded owner identity.
        $name = $locale === 'uk'
            ? ($_SERVER['SITE_NAME_UK'] ?? '')
            : ($_SERVER['SITE_NAME'] ?? '');
        $title = trim($name . ' — ' . $content['role']);

        // Canonical base: use the production domain unless a dev host is served.
        // The request host is correct in production (SITE_DOMAIN via nginx);
        // in dev it is localhost:8080. We prefer the request host so it matches.
        $base = rtrim($request->getScheme() . '://' . $request->getHttpHost(), '/');
        $canonical = $base . '/' . $locale;

        // Only include social URLs that are configured (anonymization).
        $sameAs = array_values(array_filter([
            $_SERVER['TELEGRAM_URL'] ?? '',
            $_SERVER['LINKEDIN_URL'] ?? '',
            $_SERVER['GITHUB_URL'] ?? '',
        ], static fn (string $url): bool => $url !== ''));

        return $this->render('app.html.twig', [
            'locale' => $locale,
            'title' => $title,
            'description' => $content['description'],
            'ogTitle' => $title,
            'ogDescription' => $content['ogDescription'],
            'canonical' => $canonical,
            'ogImage' => $base . '/images/profile.jpg',
            'siteName' => $name,
            'siteDomain' => $_SERVER['SITE_DOMAIN'] ?? '',
            'contactEmail' => $_SERVER['CONTACT_EMAIL'] ?? '',
            'companyName' => $_SERVER['COMPANY_NAME'] ?? '',
            'sameAs' => $sameAs,
        ]);
    }
}
