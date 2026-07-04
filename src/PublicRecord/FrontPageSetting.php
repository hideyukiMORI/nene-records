<?php

declare(strict_types=1);

namespace NeNeRecords\PublicRecord;

use NeNeRecords\Entity\Entity;
use NeNeRecords\Entity\EntityRepositoryInterface;
use NeNeRecords\Entity\EntityStatus;
use NeNeRecords\EntityType\EntityType;
use NeNeRecords\EntityType\EntityTypeRepositoryInterface;
use NeNeRecords\Setting\SettingRepositoryInterface;
use NeNeRecords\Setting\SettingValueInvalidException;
use Throwable;

/**
 * The single home of the `front_page` rule (#701): what the org's pinned front page IS
 * (read side) and what may BECOME it (write side), org-scoped.
 *
 * {@see resolvePublished()} returns null when the setting is unset, malformed, unreadable
 * (no org resolved on the tenant-less apex), or no longer a published record — so every
 * caller treats "no front page" and "front page not applicable here" identically. Shared
 * by the render, canonical-redirect, sitemap and public-settings layers so the front page
 * is served at `/`, its own permalink redirects home, and it appears once in the sitemap.
 * The resolution is memoized: those layers can run in one request without re-querying.
 */
final class FrontPageSetting
{
    private bool $resolved = false;

    /** @var array{Entity, EntityType}|null */
    private ?array $published = null;

    public function __construct(
        private readonly SettingRepositoryInterface $settings,
        private readonly EntityRepositoryInterface $entities,
        private readonly EntityTypeRepositoryInterface $entityTypes,
    ) {
    }

    /**
     * The pinned record and its type, or null to fall back to the default home — on
     * unset / non-numeric / not-found / not-published values, on a type-less record,
     * and on any settings-read failure.
     *
     * @return array{Entity, EntityType}|null
     */
    public function resolvePublished(): ?array
    {
        if ($this->resolved) {
            return $this->published;
        }

        $this->resolved = true;
        $id = $this->pinnedRecordId();

        if ($id === null) {
            return null;
        }

        // findById is org-scoped and excludes soft-deleted records.
        $entity = $this->entities->findById($id);

        if ($entity === null || $entity->id === null || $entity->status !== EntityStatus::Published) {
            return null;
        }

        $type = $this->entityTypes->findById($entity->entityTypeId);

        if ($type === null) {
            return null;
        }

        return $this->published = [$entity, $type];
    }

    /**
     * Write-side twin of {@see resolvePublished()}: assert that a submitted setting
     * value may be pinned as the front page ('' = unset is always allowed), with a
     * distinct message per failure so the 422 tells the admin what to fix.
     *
     * @throws SettingValueInvalidException
     */
    public function assertPinnable(string $value): void
    {
        if ($value === '') {
            return;
        }

        if (!ctype_digit($value)) {
            throw new SettingValueInvalidException('Front page must be a record id.');
        }

        // findById is already org-scoped and excludes soft-deleted records.
        $entity = $this->entities->findById((int) $value);

        if ($entity === null) {
            throw new SettingValueInvalidException('Front page record does not exist.');
        }

        if ($entity->status !== EntityStatus::Published) {
            throw new SettingValueInvalidException('Front page record must be published.');
        }
    }

    private function pinnedRecordId(): ?int
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
