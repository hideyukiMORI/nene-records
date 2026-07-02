<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Renders a resolved public record as a crawlable, SPA-hydratable HTML page.
 *
 * Extracted so the front-page edge layer ({@see RenderPublicHomeHandler}) depends on the
 * render capability rather than the concrete {@see RenderPublicRecordViewHandler}, keeping
 * the home pipeline unit-testable with a lightweight fake.
 */
interface PublicRecordViewRendererInterface
{
    /**
     * @param bool $asFrontPage Render as the site home (canonical = root, og:type = website,
     *                          no breadcrumbs) rather than a normal record page.
     */
    public function renderEntity(
        string $typeSlug,
        ?string $entitySlug,
        ?int $entityId,
        ServerRequestInterface $request,
        bool $asFrontPage = false,
    ): ResponseInterface;
}
