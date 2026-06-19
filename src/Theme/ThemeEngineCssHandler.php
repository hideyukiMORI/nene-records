<?php

declare(strict_types=1);

namespace NeNeRecords\Theme;

use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Returns the deployed public-site base engine CSS so ClaudeDesign can read the
 * exact flag implementations (`[data-cards='framed']` etc.) over MCP instead of
 * guessing border/radius/padding values (#448).
 *
 * Runtime themes restyle this fixed engine via tokens + flags; the per-built-in
 * `*.components.css` is intentionally NOT included (runtime themes never get it).
 */
final readonly class ThemeEngineCssHandler
{
    private const DEFAULT_RELATIVE = 'frontend/src/pages/consumer/public-site.css';

    public function __construct(
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $path = $this->resolvePath();
        $css = is_file($path) ? (string) file_get_contents($path) : '';

        $note = 'Deployed public-site base engine CSS. Runtime themes restyle this via tokens '
            . '(var(--token)) and flags (data-* attributes); copy flag rules like '
            . "[data-cards='framed'] .card for exact radius/border/padding. Per-theme "
            . '*.components.css is NOT included — runtime themes cannot use it.';

        if ($css === '') {
            $note = 'Engine CSS not found on this deployment. Set NENE_ENGINE_CSS_PATH to the '
                . 'public-site.css location. ' . $note;
        }

        return $this->response->create([
            'source' => self::DEFAULT_RELATIVE,
            'bytes' => strlen($css),
            'css' => $css,
            'note' => $note,
        ]);
    }

    private function resolvePath(): string
    {
        $override = getenv('NENE_ENGINE_CSS_PATH');
        if (is_string($override) && $override !== '') {
            return $override;
        }

        return dirname(__DIR__, 2) . '/' . self::DEFAULT_RELATIVE;
    }
}
