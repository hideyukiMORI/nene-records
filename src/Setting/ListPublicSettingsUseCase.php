<?php

declare(strict_types=1);

namespace NeNeRecords\Setting;

use NeNeRecords\Media\MediaRepositoryInterface;
use NeNeRecords\PublicRecord\FrontPageSetting;
use NeNeRecords\PublicRecord\PublicPermalinkResolver;

final readonly class ListPublicSettingsUseCase implements ListPublicSettingsUseCaseInterface
{
    /** The setting that pins a single record as the public front page (#701). */
    private const FRONT_PAGE_SETTING = 'front_page';

    public function __construct(
        private SettingRepositoryInterface $settings,
        private MediaRepositoryInterface $media,
        private FrontPageSetting $frontPage,
    ) {
    }

    public function execute(): ListPublicSettingsOutput
    {
        $items = array_map(
            fn (SettingEntry $entry): SettingEntry => $this->resolve($entry),
            $this->settings->findPublicEntries(),
        );

        return new ListPublicSettingsOutput(items: $items);
    }

    /**
     * The public site wants a flat key→string map it can use directly, so a couple of
     * settings that store an id are resolved to a URL/path here (unset / invalid → '').
     */
    private function resolve(SettingEntry $entry): SettingEntry
    {
        if ($entry->def->dataType === 'media') {
            return $this->resolveMedia($entry);
        }

        if ($entry->def->settingKey === self::FRONT_PAGE_SETTING) {
            return new SettingEntry(
                $entry->def,
                $this->resolveFrontPagePath(),
                $entry->storedValue,
            );
        }

        return $entry;
    }

    /**
     * `media`-type settings store a media id; the public site needs a URL.
     * Resolve it here so the shell can render an `<img>` directly. Missing → ''.
     */
    private function resolveMedia(SettingEntry $entry): SettingEntry
    {
        $url = '';
        if ($entry->effectiveValue !== '') {
            $media = $this->media->findById((int) $entry->effectiveValue);
            if ($media !== null) {
                $url = $media->url;
            }
        }

        return new SettingEntry($entry->def, $url, $entry->storedValue);
    }

    /**
     * `front_page` stores a record id; the public site needs the record's canonical
     * path so it can render/link to it as the home page. Only a currently published,
     * non-deleted record in this org resolves ({@see FrontPageSetting}); anything
     * else returns '' so the SPA falls back to the default magazine home.
     */
    private function resolveFrontPagePath(): string
    {
        $front = $this->frontPage->resolvePublished();

        if ($front === null) {
            return '';
        }

        [$entity, $type] = $front;

        return PublicPermalinkResolver::canonicalPath(
            $entity->permalink,
            $type->permalinkPattern,
            $type->slug,
            $entity->slug,
            (int) $entity->id,
            $entity->publishedAt,
        );
    }
}
