<?php

namespace App\Twig;

use Symfony\Component\HttpKernel\KernelInterface;
use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFunction;

/**
 * Provides the vite_assets() Twig function.
 *
 * - dev:  emits the Vite dev-server client + entry module script (http://localhost:5173)
 * - prod: reads public/build/.vite/manifest.json and emits hashed CSS + JS (+ modulepreload).
 *
 * Fails loudly if the manifest is missing in prod.
 */
final class ViteExtension extends AbstractExtension
{
    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly string $publicDir,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('vite_assets', [$this, 'assets']),
        ];
    }

    public function assets(): Markup
    {
        if ($this->kernel->getEnvironment() === 'dev') {
            $html = <<<'HTML'
                <script type="module" src="http://localhost:5173/@vite/client"></script>
                <script type="module" src="http://localhost:5173/src/main.ts"></script>
                HTML;
            return new Markup($html, 'UTF-8');
        }

        $manifestPath = $this->publicDir . '/build/.vite/manifest.json';
        if (!is_file($manifestPath)) {
            throw new \RuntimeException(
                sprintf(
                    'Vite manifest not found at "%s". Build the frontend first (npm run build) or set APP_ENV=dev.',
                    $manifestPath
                )
            );
        }

        $raw = (string) file_get_contents($manifestPath);
        $manifest = json_decode($raw, true);
        if (!is_array($manifest)) {
            throw new \RuntimeException('Vite manifest could not be parsed: ' . $manifestPath);
        }

        $css = [];
        $preloads = [];
        $entry = null;

        foreach ($manifest as $asset) {
            if (!is_array($asset) || !isset($asset['file']) || !is_string($asset['file'])) {
                continue;
            }

            if (($asset['isEntry'] ?? false) && str_ends_with($asset['file'], '.js')) {
                $entry = $asset['file'];
            }

            if (str_ends_with($asset['file'], '.css')) {
                $css[] = $asset['file'];
            }

            if (isset($asset['imports']) && is_array($asset['imports'])) {
                foreach ($asset['imports'] as $import) {
                    if (isset($manifest[$import]['file']) && str_ends_with($manifest[$import]['file'], '.js')) {
                        $preloads[] = $manifest[$import]['file'];
                    }
                }
            }
        }

        if ($entry === null) {
            throw new \RuntimeException('Vite manifest contains no entry JS file: ' . $manifestPath);
        }

        $html = '';
        foreach (array_unique($css) as $file) {
            $html .= sprintf('<link rel="stylesheet" href="/build/%s">' . "\n", htmlspecialchars($file, ENT_QUOTES));
        }
        foreach (array_unique($preloads) as $file) {
            $html .= sprintf('<link rel="modulepreload" href="/build/%s">' . "\n", htmlspecialchars($file, ENT_QUOTES));
        }
        $html .= sprintf('<script type="module" src="/build/%s"></script>' . "\n", htmlspecialchars($entry, ENT_QUOTES));

        return new Markup($html, 'UTF-8');
    }
}
