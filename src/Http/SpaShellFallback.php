<?php

declare(strict_types=1);

namespace NeNeRecords\Http;

use NeNeRecords\Setting\ListPublicSettingsUseCaseInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Single-origin SPA fallback: when the router 404s a GET HTML navigation to a
 * non-API path, serve the built SPA shell (`frontend/dist/index.html`) so the
 * client router can handle `/admin`, `/login`, `/search`, `/tag/:slug`, browse
 * pages, etc. API / media / view / asset paths keep their genuine 404, and the
 * fallback is a no-op when no build is present (dev / unbuilt).
 *
 * For public client-routed pages it also injects the org's GA4 / GTM tag with a
 * Consent Mode v2 default (matching the SSR record pages), so the SPA's initial
 * shell load is measured too. Analytics is skipped on admin / auth surfaces and
 * is strictly best-effort: any failure to resolve settings (e.g. no org context
 * on `/admin`) falls back to the plain shell with the strict baseline CSP.
 */
final readonly class SpaShellFallback
{
    private const PASSTHROUGH = '#^/(api|media|view|assets)(/|$)#';

    /** Surfaces that must never carry public analytics (logged-in / back office). */
    private const ANALYTICS_SKIP = '#^/(admin|login|superadmin)(/|$)#';

    public function __construct(
        private string $shellPath,
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
        private ?ListPublicSettingsUseCaseInterface $publicSettings = null,
        /** Sub-directory install prefix (`APP_BASE_PATH`); '' = served at root. */
        private string $basePath = '',
    ) {
    }

    public function apply(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($response->getStatusCode() !== 404) {
            return $response;
        }

        if (strtoupper($request->getMethod()) !== 'GET') {
            return $response;
        }

        if (!str_contains($request->getHeaderLine('Accept'), 'text/html')) {
            return $response;
        }

        $path = $request->getUri()->getPath();

        if (preg_match(self::PASSTHROUGH, $path) === 1) {
            return $response;
        }

        if (!is_file($this->shellPath)) {
            return $response;
        }

        $html = file_get_contents($this->shellPath);

        if ($html === false) {
            return $response;
        }

        $html = $this->injectBasePath($html);

        $analytics = $this->resolveAnalytics($path);
        $nonce = $analytics->isEnabled() ? bin2hex(random_bytes(16)) : '';

        if ($analytics->isEnabled()) {
            $html = $this->injectIntoHead($html, WebAnalyticsHeadSnippet::render($analytics, $nonce));
        }

        return $this->responseFactory->createResponse(200)
            ->withHeader('Content-Type', 'text/html; charset=utf-8')
            ->withHeader('Content-Security-Policy', PublicHtmlCsp::build($analytics, $nonce !== '' ? $nonce : null))
            ->withBody($this->streamFactory->createStream($html));
    }

    /**
     * Best-effort analytics resolution for the shell. Disabled on admin / auth
     * paths and whenever settings can't be read (org-scoped lookup throws when no
     * org is resolved — same resilience contract as the 301 redirect layer).
     */
    private function resolveAnalytics(string $path): WebAnalyticsConfig
    {
        if ($this->publicSettings === null || preg_match(self::ANALYTICS_SKIP, $path) === 1) {
            return WebAnalyticsConfig::disabled();
        }

        try {
            $map = [];
            foreach ($this->publicSettings->execute()->items as $entry) {
                $map[$entry->def->settingKey] = $entry->effectiveValue;
            }

            return WebAnalyticsConfig::fromSettings($map);
        } catch (\Throwable) {
            return WebAnalyticsConfig::disabled();
        }
    }

    /** Insert the snippet immediately before the first `</head>` (no-op if absent). */
    private function injectIntoHead(string $html, string $snippet): string
    {
        if ($snippet === '') {
            return $html;
        }

        $pos = stripos($html, '</head>');

        if ($pos === false) {
            return $html;
        }

        return substr($html, 0, $pos) . $snippet . substr($html, $pos);
    }

    /**
     * Make the built shell base-path-aware (#zip-install S2): repoint its
     * `<base href="/">` to the install sub-directory (which anchors the shell's
     * relative asset URLs) and expose the base to the SPA router / API client via
     * `window.__BASE_PATH__`. A no-op for assets at root; the global is always set.
     */
    private function injectBasePath(string $html): string
    {
        if ($this->basePath !== '') {
            $html = str_replace('<base href="/"', '<base href="' . $this->basePath . '/"', $html);
        }

        $script = '<script>window.__BASE_PATH__ = '
            . json_encode($this->basePath, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES)
            . ';</script>';

        return $this->injectIntoHead($html, $script);
    }
}
