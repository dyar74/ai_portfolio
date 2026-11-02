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
    private const CONTENT = [
        'en' => [
            'title' => 'Yaroslav Dupliak — AI Automation Engineer',
            'description' => 'I design and ship reliable, multilingual automation systems with n8n, LLMs and AI agents. Live, working demos powered by a real n8n instance.',
            'ogTitle' => 'Yaroslav Dupliak — AI Automation Engineer',
            'ogDescription' => 'Multilingual (EN/UK) business-card site with live, working n8n demos.',
        ],
        'uk' => [
            'title' => 'Ярослав Дупляк — Інженер з AI-автоматизації',
            'description' => 'Я проєктую та запускаю надійні багатомовні автоматизації з n8n, LLM та AI-агентами. Живі демо на реальному n8n.',
            'ogTitle' => 'Ярослав Дупляк — Інженер з AI-автоматизації',
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

        // Canonical base: use the production domain unless a dev host is served.
        // The request host is correct in production (novotek.com.ua via nginx);
        // in dev it is localhost:8080. We prefer the request host so it always matches.
        $base = rtrim($request->getScheme() . '://' . $request->getHttpHost(), '/');
        $canonical = $base . '/' . $locale;

        return $this->render('app.html.twig', [
            'locale' => $locale,
            'title' => $content['title'],
            'description' => $content['description'],
            'ogTitle' => $content['ogTitle'],
            'ogDescription' => $content['ogDescription'],
            'canonical' => $canonical,
            'ogImage' => $base . '/images/profile.jpg',
        ]);
    }
}
