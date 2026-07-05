<?php

declare(strict_types=1);

namespace NeNeRecords\Setting;

use NeNeRecords\Media\MediaDerivativeUrl;
use NeNeRecords\Media\MediaRepositoryInterface;
use NeNeRecords\PublicRecord\FrontPageSetting;
use NeNeRecords\PublicRecord\PublicPermalinkResolver;

final readonly class ListPublicSettingsUseCase implements ListPublicSettingsUseCaseInterface
{
    /** The setting that pins a single record as the public front page (#701). */
    private const FRONT_PAGE_SETTING = 'front_page';

    /** The per-theme customizer JSON whose image slots store media ids (#372). */
    private const THEME_OVERRIDES_SETTING = 'theme_overrides';

    /** Image slots inside `theme_overrides` that hold a media id needing a URL. */
    private const IMAGE_SLOTS = ['hero', 'background', 'logo'];

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

        if ($entry->def->settingKey === self::THEME_OVERRIDES_SETTING) {
            return $this->resolveThemeOverrideImages($entry);
        }

        return $entry;
    }

    /**
     * The customizer stores image slots (`images.{hero|background|logo}.{light|dark}`)
     * inside the per-theme `theme_overrides` JSON as media **ids**; the public site
     * needs same-origin **URLs** to inject as CSS `url(...)`/render an `<img>`. Rewrite
     * only those slots — every other key in the payload is passed through verbatim, so
     * this stays decoupled from the full override schema. Malformed JSON is left
     * untouched (never throws), and a missing media id drops the slot so the SPA falls
     * back cleanly (matching {@see resolveMedia}).
     */
    private function resolveThemeOverrideImages(SettingEntry $entry): SettingEntry
    {
        if ($entry->effectiveValue === '') {
            return $entry;
        }

        /** @var mixed $decoded */
        $decoded = json_decode($entry->effectiveValue, true);
        if (!is_array($decoded)) {
            return $entry;
        }

        $changed = false;
        foreach ($decoded as $themeId => $override) {
            if (!is_array($override) || !isset($override['images']) || !is_array($override['images'])) {
                continue;
            }

            foreach (self::IMAGE_SLOTS as $slot) {
                $slotRefs = $override['images'][$slot] ?? null;
                if (!is_array($slotRefs)) {
                    continue;
                }

                foreach (['light', 'dark'] as $mode) {
                    $ref = $slotRefs[$mode] ?? null;
                    if (!is_int($ref) && !(is_string($ref) && ctype_digit($ref))) {
                        continue; // already a URL, absent, or invalid — leave as-is
                    }

                    $resolved = $this->resolveImageUrl((int) $ref, $slot);
                    if ($resolved === null) {
                        unset($decoded[$themeId]['images'][$slot][$mode]);
                    } else {
                        $decoded[$themeId]['images'][$slot][$mode] = $resolved;
                    }
                    $changed = true;
                }
            }
        }

        if (!$changed) {
            return $entry;
        }

        $json = json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return new SettingEntry($entry->def, $json === false ? $entry->effectiveValue : $json, $entry->storedValue);
    }

    /**
     * Resolve a media id to a same-origin URL: hero/background bake to the `lg`
     * derivative (a single CSS `background-image` has no srcset); logo uses the raw
     * URL (small, and SVG has no derivative). Missing / non-positive id → null.
     */
    private function resolveImageUrl(int $id, string $slot): ?string
    {
        if ($id <= 0) {
            return null;
        }

        $media = $this->media->findById($id);
        if ($media === null) {
            return null;
        }

        if ($slot === 'logo') {
            return $media->url;
        }

        return MediaDerivativeUrl::forPreset($media->url, 'lg') ?? $media->url;
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
