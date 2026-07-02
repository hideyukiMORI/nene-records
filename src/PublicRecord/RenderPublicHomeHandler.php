<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

use NeNeRecords\Entity\EntityRepositoryInterface;
use NeNeRecords\Entity\EntityStatus;
use NeNeRecords\EntityType\EntityTypeRepositoryInterface;
use NeNeRecords\Setting\SettingRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Single-origin edge layer for the public site root `/` (#701).
 *
 * When the org pinned a `front_page` that resolves to a currently published record on a
 * tenant host, this server-renders that record as the home page (canonical = site root,
 * og:type = website, no breadcrumbs) and returns it in place of the framework-info JSON.
 * Otherwise it passes the response through unchanged, so API clients keep the `/` payload
 * and a browser gets the default magazine home / SaaS landing via the SPA-shell fallback.
 *
 * It is an edge layer (not a route) because the framework already owns a `/` route that a
 * later app route cannot beat (equal specificity → registration order wins). Running after
 * the application, like {@see \NeNeRecords\Http\SpaShellFallback}, keeps the resolved org
 * context valid and lets it override the framework's `/` answer for the home navigation.
 */
final readonly class RenderPublicHomeHandler
{
    public function __construct(
        private SettingRepositoryInterface $settings,
        private EntityRepositoryInterface $entities,
        private EntityTypeRepositoryInterface $entityTypes,
        private PublicRecordViewRendererInterface $renderer,
    ) {
    }

    public function apply(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // Only a browser navigation to the exact site root is a candidate.
        if (strtoupper($request->getMethod()) !== 'GET' || $request->getUri()->getPath() !== '/') {
            return $response;
        }

        if (!str_contains($request->getHeaderLine('Accept'), 'text/html')) {
            return $response;
        }

        $front = $this->resolveFrontPage();

        if ($front === null) {
            return $response;
        }

        [$typeSlug, $entityId] = $front;

        try {
            return $this->renderer->renderEntity($typeSlug, null, $entityId, $request, asFrontPage: true);
        } catch (Throwable) {
            // Never white-screen the home page: fall back to the default home on any failure.
            return $response;
        }
    }

    /**
     * The (type slug, entity id) of the pinned front page, or null to fall back to the
     * default home — on unset / non-numeric / not-found / not-published values, and on any
     * settings-read failure (no org is resolved on the tenant-less apex).
     *
     * @return array{0: string, 1: int}|null
     */
    private function resolveFrontPage(): ?array
    {
        try {
            $stored = $this->settings->findValueByKey('front_page');
        } catch (Throwable) {
            return null;
        }

        if ($stored === null) {
            return null;
        }

        $value = $stored->value ?? '';

        if ($value === '' || !ctype_digit($value)) {
            return null;
        }

        // findById is org-scoped and excludes soft-deleted records.
        $entity = $this->entities->findById((int) $value);

        if ($entity === null || $entity->id === null || $entity->status !== EntityStatus::Published) {
            return null;
        }

        $type = $this->entityTypes->findById($entity->entityTypeId);

        if ($type === null) {
            return null;
        }

        return [$type->slug, $entity->id];
    }
}
