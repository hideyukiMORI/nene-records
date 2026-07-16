<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

/**
 * Composes the public `<title>` from a page title and the site name (#909).
 *
 * The suffix is skipped when the page title already carries the site name —
 * meta_title values imported from other CMSes (and the AYANE delivery) come as
 * "ページ名｜社名", and appending " — 社名" again doubled the brand on every tab.
 *
 * Twin: `frontend/src/shared/lib/public-document-title.ts` — the SPA sets
 * `document.title` with the same rule so hydration/client navigation renders the
 * exact string the SSR emitted. Change both together.
 */
final class PublicDocumentTitle
{
    public static function compose(string $pageTitle, string $siteName): string
    {
        $pageTitle = trim($pageTitle);
        $siteName = trim($siteName);

        if ($pageTitle === '') {
            return $siteName;
        }

        if ($siteName === '' || str_contains($pageTitle, $siteName)) {
            return $pageTitle;
        }

        return $pageTitle . ' — ' . $siteName;
    }
}
