<?php

declare(strict_types=1);

namespace NeNeRecords\Setting;

use NeNeRecords\Media\MediaRepositoryInterface;

final readonly class ListPublicSettingsUseCase implements ListPublicSettingsUseCaseInterface
{
    public function __construct(
        private SettingRepositoryInterface $settings,
        private MediaRepositoryInterface $media,
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
     * `media`-type settings store a media id; the public site needs a URL.
     * Resolve it here so the response stays a flat key→string map (and the
     * shell can render an `<img>` directly). Unset / missing media → ''.
     */
    private function resolve(SettingEntry $entry): SettingEntry
    {
        if ($entry->def->dataType !== 'media') {
            return $entry;
        }

        $url = '';
        if ($entry->effectiveValue !== '') {
            $media = $this->media->findById((int) $entry->effectiveValue);
            if ($media !== null) {
                $url = $media->url;
            }
        }

        return new SettingEntry($entry->def, $url, $entry->storedValue);
    }
}
