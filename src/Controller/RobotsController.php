<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RobotsController extends AbstractController
{
    #[Route('/robots.txt', name: 'robots', methods: ['GET'])]
    public function robots(): Response
    {
        $request = $this->getRequest();
        $url = $request->getScheme() . '://' . $request->getHttpHost() . '/sitemap.xml';

        $txt = "User-agent: *\nAllow: /\n\nSitemap: " . $url . "\n";

        return new Response($txt, 200, ['Content-Type' => 'text/plain']);
    }
}
