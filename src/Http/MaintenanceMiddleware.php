<?php

declare(strict_types=1);

namespace NeNeRecords\Http;

use Nene2\Auth\TokenVerificationException;
use Nene2\Auth\TokenVerifierInterface;
use NeNeRecords\Auth\SessionCookie;
use NeNeRecords\Setting\SettingRepositoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Per-org maintenance mode (#813).
 *
 * When the resolved org's `maintenance_mode` setting is on, anonymous requests to
 * the public-facing surface get a 503 "メンテナンス中です" page. A logged-in visitor
 * (valid session cookie / Bearer) passes through, so the org's own staff can keep
 * working and preview the live site.
 *
 * Runs after OrgResolverMiddleware (so the org — and thus its setting — is known)
 * but before auth: public pages don't carry auth claims (the auth middleware skips
 * open paths), so this middleware verifies the session itself, read-only.
 *
 * Never gated (reachable even in maintenance): /health, /api/v1/auth/* (login), the
 * back-office SPA (/admin, /login, /superadmin) and the rest of /api/v1/* (auth-
 * scoped admin API). Only the public consumer surface is gated: /api/v1/public/* and
 * public HTML pages. Fail-open: if the setting can't be read (e.g. org unresolved),
 * the request passes through rather than wrongly blocking the whole site.
 */
final readonly class MaintenanceMiddleware implements MiddlewareInterface
{
    /** Back-office SPA surfaces that must stay reachable so an operator can log in and work. */
    private const BACK_OFFICE_PATTERN = '#^/(admin|login|superadmin)(/|$)#';

    public function __construct(
        private SettingRepositoryInterface $settings,
        private TokenVerifierInterface $verifier,
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath() ?: '/';

        if (!$this->gatesPath($path)) {
            return $handler->handle($request);
        }

        if (!$this->isMaintenanceOn()) {
            return $handler->handle($request);
        }

        if ($this->hasValidSession($request)) {
            return $handler->handle($request);
        }

        return $this->maintenanceResponse();
    }

    /**
     * True only for the public consumer surface. Operational and back-office paths
     * are never gated so the site can be recovered / worked on during maintenance.
     */
    private function gatesPath(string $path): bool
    {
        if ($path === '/health' || str_starts_with($path, '/health/')) {
            return false;
        }

        if (str_starts_with($path, '/api/v1/auth/')) {
            return false;
        }

        if (preg_match(self::BACK_OFFICE_PATTERN, $path) === 1) {
            return false;
        }

        // The public consumer read API IS part of the gated surface.
        if (str_starts_with($path, '/api/v1/public/')) {
            return true;
        }

        // Any other API (admin / machine / webhooks / cron) is auth-scoped — never gate it.
        if (str_starts_with($path, '/api/v1/')) {
            return false;
        }

        // Everything else is a public-facing consumer HTML page → gate.
        return true;
    }

    private function isMaintenanceOn(): bool
    {
        try {
            $value = $this->settings->findValueByKey('maintenance_mode');
        } catch (\Throwable) {
            // Fail-open: never let a settings read error take the whole site down.
            return false;
        }

        return $value !== null && $value->value === 'true';
    }

    private function hasValidSession(ServerRequestInterface $request): bool
    {
        $authorization = $request->getHeaderLine('Authorization');
        if (str_starts_with($authorization, 'Bearer ')) {
            $token = substr($authorization, 7);
        } else {
            $cookies = $request->getCookieParams();
            $token = isset($cookies[SessionCookie::NAME]) && is_string($cookies[SessionCookie::NAME])
                ? $cookies[SessionCookie::NAME]
                : '';
        }

        if ($token === '') {
            return false;
        }

        try {
            $this->verifier->verify($token);

            return true;
        } catch (TokenVerificationException) {
            return false;
        }
    }

    private function maintenanceResponse(): ResponseInterface
    {
        $html = <<<'HTML'
<!doctype html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex">
<title>メンテナンス中です</title>
</head>
<body style="margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#fbf9f5;color:#232e3d;font-family:-apple-system,BlinkMacSystemFont,'Hiragino Kaku Gothic ProN','Hiragino Sans','Noto Sans JP','Yu Gothic Medium',Meiryo,sans-serif">
<main style="text-align:center;padding:40px 24px;max-width:32rem">
<div style="font:700 clamp(1.6rem,5vw,2.4rem)/1.3;margin:0 0 16px">メンテナンス中です</div>
<p style="font:400 1rem/1.8;color:#5a6472;margin:0">ただいまメンテナンスを行っています。お時間をおいて、あらためてアクセスしてください。ご不便をおかけします。</p>
</main>
</body>
</html>
HTML;

        $response = $this->responseFactory->createResponse(503, 'Service Unavailable')
            ->withHeader('Content-Type', 'text/html; charset=utf-8')
            ->withHeader('Retry-After', '3600')
            ->withHeader('Cache-Control', 'no-store');

        return $response->withBody($this->streamFactory->createStream($html));
    }
}
