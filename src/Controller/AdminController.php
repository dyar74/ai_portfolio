<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\SiteContent;
use App\Repository\SiteContentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Admin API for editing the site's text content (per-locale).
 *
 * Security model:
 *  - The admin UI is a static SPA view (no per-user auth in this scope).
 *  - Write operations (POST/DELETE) require an `X-Admin-Token` header that
 *    must match the `ADMIN_TOKEN` env var (a shared secret). This is a simple
 *    gate to prevent anonymous write access; it is NOT a substitute for real
 *    authentication and should be fronted by a proper auth provider in prod.
 *  - Read operations (GET) are open so the admin/preview page can render.
 */
class AdminController extends AbstractController
{
    private const VALID_LOCALES = ['en', 'uk'];

    public function __construct(
        private readonly SiteContentRepository $contentRepo,
        private readonly string $adminToken,
    ) {}

    /**
     * GET /api/admin/content?locale=en
     * Returns all editable content strings for a locale.
     */
    #[Route('/api/admin/content', name: 'api_admin_content', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $locale = (string) $request->query->get('locale', 'en');
        if (!in_array($locale, self::VALID_LOCALES, true)) {
            return $this->json(['error' => 'invalid_locale', 'allowed' => self::VALID_LOCALES], 422);
        }

        $rows = $this->contentRepo->findByLocale($locale);
        $content = [];
        foreach ($rows as $row) {
            $content[$row->getKey()] = [
                'value' => $row->getValue(),
                'updatedAt' => $row->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
            ];
        }

        return $this->json(['locale' => $locale, 'content' => $content]);
    }

    /**
     * POST /api/admin/content  (create or update a single key)
     * Body: { "locale": "en", "key": "about.bio", "value": "..." }
     */
    #[Route('/api/admin/content', name: 'api_admin_content_save', methods: ['POST'])]
    public function save(Request $request): JsonResponse
    {
        if (!$this->authorized($request)) {
            return $this->json(['error' => 'unauthorized'], 401);
        }

        $data = json_decode((string) $request->getContent(), true);
        if (!\is_array($data)) {
            return $this->json(['error' => 'invalid_json'], 422);
        }

        $locale = (string) ($data['locale'] ?? '');
        $key = (string) ($data['key'] ?? '');
        $value = (string) ($data['value'] ?? '');

        if (!in_array($locale, self::VALID_LOCALES, true)) {
            return $this->json(['error' => 'invalid_locale', 'allowed' => self::VALID_LOCALES], 422);
        }
        if ($key === '' || mb_strlen($key) > 50) {
            return $this->json(['error' => 'invalid_key'], 422);
        }
        if ($value === '') {
            return $this->json(['error' => 'value_required'], 422);
        }

        $em = $this->getEntityManager();
        $row = $this->contentRepo->findOneByLocaleAndKey($locale, $key);
        if ($row === null) {
            $row = new SiteContent();
            $row->setLocale($locale);
            $row->setKey($key);
            $em->persist($row);
        }
        $row->setValue($value);
        $row->setUpdatedAt(new \DateTimeImmutable());
        $em->flush();

        return $this->json([
            'ok' => true,
            'locale' => $locale,
            'key' => $key,
            'updatedAt' => $row->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ]);
    }

    /**
     * DELETE /api/admin/content/{locale}/{key}
     */
    #[Route('/api/admin/content/{locale}/{key}', name: 'api_admin_content_delete', methods: ['DELETE'])]
    public function delete(Request $request, string $locale, string $key): JsonResponse
    {
        if (!$this->authorized($request)) {
            return $this->json(['error' => 'unauthorized'], 401);
        }

        if (!in_array($locale, self::VALID_LOCALES, true)) {
            return $this->json(['error' => 'invalid_locale'], 422);
        }

        $em = $this->getEntityManager();
        $row = $this->contentRepo->findOneByLocaleAndKey($locale, $key);
        if ($row === null) {
            return $this->json(['error' => 'not_found'], 404);
        }

        $em->remove($row);
        $em->flush();

        return $this->json(['ok' => true]);
    }

    /**
     * GET /api/admin/health  — quick check that the admin API is reachable.
     */
    #[Route('/api/admin/health', name: 'api_admin_health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return $this->json(['ok' => true, 'service' => 'admin', 'time' => new \DateTimeImmutable()]);
    }

    private function authorized(Request $request): bool
    {
        $token = (string) $request->headers->get('X-Admin-Token', '');
        if ($token === '' || $this->adminToken === '') {
            return false;
        }
        return hash_equals($this->adminToken, $token);
    }
}
