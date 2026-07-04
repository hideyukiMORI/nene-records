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
        /** Subdomain SaaS base domain; the bare host is the tenant-less apex. */
        private string $baseDomain = '',
    ) {
    }

    public function apply(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $isGet = strtoupper($request->getMethod()) === 'GET';
        $wantsHtml = str_contains($request->getHeaderLine('Accept'), 'text/html');
        $path = $request->getUri()->getPath();

        // The home `/` is the NENE2 framework-info JSON for API clients, but a
        // browser there wants the public site / SaaS landing — serve the SPA shell
        // for `/` + text/html even though the framework answered 200 (not 404).
        // EXCEPT when an upstream handler already produced an HTML page for `/` (the
        // front-page SSR, #701): that response is the real page, so let it pass through.
        $alreadyHtml = str_contains($response->getHeaderLine('Content-Type'), 'text/html');
        $isHtmlHome = $isGet && $wantsHtml && $path === '/' && !$alreadyHtml;

        if ($response->getStatusCode() !== 404 && !$isHtmlHome) {
            return $response;
        }

        if (!$isGet) {
            return $response;
        }

        if (!$wantsHtml) {
            return $response;
        }

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

        $html = $this->injectBasePath($html, $request);
        $html = $this->injectApexFlag($html, $request);

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
     * Repoint the built shell's `<base href="/">` to the install sub-directory
     * (#zip-install S2). The `<base>` anchors the shell's relative asset URLs and
     * is what the SPA reads to derive the router basename / API prefix — no inline
     * script, so the strict public CSP stays intact. A no-op at root.
     */
    private function injectBasePath(string $html, ServerRequestInterface $request): string
    {
        // Fixed install prefix + per-request tenant prefix (directory mode), so the
        // <base href> anchors assets and the SPA derives its router/API base.
        $base = $this->basePath . (string) $request->getAttribute('nene2.base_prefix', '');

        if ($base === '') {
            return $html;
        }

        return str_replace('<base href="/"', '<base href="' . $base . '/"', $html);
    }

    /**
     * Flag the tenant-less apex (bare base domain) so the SPA renders the global
     * landing instead of a tenant home. Computed from host + base domain here (the
     * pipeline's `nene2.apex` attribute does not reach this edge layer). CSP-safe:
     * a meta, not an inline script.
     */
    private function injectApexFlag(string $html, ServerRequestInterface $request): string
    {
        if ($this->baseDomain === '') {
            return $html;
        }

        $host = $request->getUri()->getHost();
        if (str_contains($host, ':')) {
            $host = explode(':', $host)[0];
        }

        if ($host !== $this->baseDomain) {
            return $html;
        }

        return $this->injectIntoHead($html, '<meta name="nene:apex" content="1" />');
    }
}
