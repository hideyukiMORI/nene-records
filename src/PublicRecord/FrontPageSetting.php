<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

use NeNeRecords\Setting\SettingRepositoryInterface;
use Throwable;

/**
 * Reads the org's pinned front-page record id (#701) from settings, org-scoped.
 *
 * Returns null when the setting is unset, malformed, or unreadable (no org resolved on
 * the tenant-less apex), so callers treat "no front page" and "front page not applicable
 * here" identically. Shared by the render, canonical-redirect and sitemap layers so the
 * front page is served at `/`, its own permalink 301s home, and it appears once in the
 * sitemap.
 */
final readonly class FrontPageSetting
{
    public function __construct(
        private SettingRepositoryInterface $settings,
    ) {
    }

    public function pinnedRecordId(): ?int
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

        return $value !== '' && ctype_digit($value) ? (int) $value : null;
    }
}
