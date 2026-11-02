<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SitemapController extends AbstractController
{
    #[Route('/sitemap.xml', name: 'sitemap', methods: ['GET'])]
    public function sitemap(): Response
    {
        $base = $this->generateUrl('app_en', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);
        // base is like http://host/en/...; use the site root instead
        $request = $this->getRequest();
        $scheme = $request->getScheme();
        $host = $request->getHttpHost();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

        foreach (['en' => '/en', 'uk' => '/uk'] as $locale => $path) {
            $url = $scheme . '://' . $host . $path;
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . $url . '</loc>' . "\n";
            $xml .= '    <xhtml:link rel="alternate" hreflang="en" href="' . $scheme . '://' . $host . '/en" />' . "\n";
            $xml .= '    <xhtml:link rel="alternate" hreflang="uk" href="' . $scheme . '://' . $host . '/uk" />' . "\n";
            $xml .= '    <xhtml:link rel="alternate" hreflang="x-default" href="' . $scheme . '://' . $host . '/' . '" />' . "\n";
            $xml .= '  </url>' . "\n";
        }

        $xml .= '</urlset>' . "\n";

        return new Response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
