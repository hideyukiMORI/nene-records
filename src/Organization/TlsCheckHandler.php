<?php

declare(strict_types=1);

namespace NeNeRecords\Organization;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * On-demand TLS gate for the subdomain SaaS (`GET /internal/tls-check?domain=…`).
 *
 * Caddy's `on_demand_tls { ask … }` calls this before issuing a certificate for an
 * incoming SNI host: a 2xx authorizes issuance, anything else denies it. Without
 * this gate any `random.nene-records.com` request would trigger a cert order and
 * exhaust the Let's Encrypt per-registered-domain rate limit.
 *
 * Authorized hosts: the base domain itself (apex landing / signup), a subdomain
 * whose first label is an active org slug, or a registered custom domain.
 *
 * Org-resolution is bypassed for this route (it answers for *other* hosts), so it
 * must never assume a tenant context.
 */
final readonly class TlsCheckHandler
{
    public function __construct(
        private OrganizationRepositoryInterface $organizations,
        private ResponseFactoryInterface $responseFactory,
        /** The SaaS base domain, e.g. `nene-records.com`. */
        private string $baseDomain,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        $domain = is_string($params['domain'] ?? null) ? trim($params['domain']) : '';

        if (str_contains($domain, ':')) {
            $domain = explode(':', $domain)[0];
        }

        if ($domain === '' || $this->baseDomain === '') {
            return $this->responseFactory->createResponse(400);
        }

        // Apex (base domain) = the global landing / signup surface — always allowed.
        if ($domain === $this->baseDomain) {
            return $this->decide(true);
        }

        $baseParts = explode('.', $this->baseDomain);
        $hostParts = explode('.', $domain);

        // Subdomain of the base domain → the first label is the org slug.
        if (count($hostParts) > count($baseParts)
            && array_slice($hostParts, -count($baseParts)) === $baseParts) {
            return $this->decide($this->isActiveOrg($this->organizations->findBySlug($hostParts[0])));
        }

        // Otherwise treat it as a custom domain mapped to a tenant.
        return $this->decide($this->isActiveOrg($this->organizations->findByCustomDomain($domain)));
    }

    private function isActiveOrg(?Organization $org): bool
    {
        return $org !== null && $org->isActive;
    }

    private function decide(bool $allow): ResponseInterface
    {
        return $this->responseFactory->createResponse($allow ? 200 : 403);
    }
}
