<?php

declare(strict_types=1);

namespace NeNeRecords\Organization\Resolution;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Reserved `www.<base-domain>` host (subdomain SaaS mode): 301s to the apex
 * instead of falling into org resolution.
 *
 * `www` is the near-universal DNS default, so a subdomain-mode deployer's own
 * promotional domain lands here first — and it is not an org slug, so
 * {@see OrgResolverMiddleware} would 404 it (and, one layer below, on-demand
 * TLS issuance for it would be refused by
 * {@see \NeNeRecords\Organization\TlsCheckHandler} were this host not also
 * allow-listed there, #832). This middleware runs ahead of OrgResolverMiddleware
 * in the auth pipeline so the redirect happens before any tenant lookup / 404.
 *
 * No-op when `$baseDomain` is empty — single/path mode deployers, where the SaaS
 * base domain isn't configured, are never affected.
 */
final readonly class WwwRedirectMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        /** Subdomain SaaS base domain, e.g. `nene-records.com`; '' disables this middleware. */
        private string $baseDomain = '',
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->baseDomain === '' || !$this->isWwwHost($request)) {
            return $handler->handle($request);
        }

        $uri = $request->getUri();
        $target = $uri->getPath() . ($uri->getQuery() !== '' ? '?' . $uri->getQuery() : '');

        return $this->responseFactory->createResponse(301)
            ->withHeader('Location', 'https://' . $this->baseDomain . $target);
    }

    private function isWwwHost(ServerRequestInterface $request): bool
    {
        $host = $request->getUri()->getHost();
        $host = str_contains($host, ':') ? explode(':', $host)[0] : $host;

        return $host === 'www.' . $this->baseDomain;
    }
}
