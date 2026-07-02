<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Setting;

use NeNeRecords\Media\Media;
use NeNeRecords\Setting\ListPublicSettingsUseCase;
use NeNeRecords\Setting\SettingDef;
use NeNeRecords\Tests\Media\InMemoryMediaRepository;
use PHPUnit\Framework\TestCase;
use NeNeRecords\Tests\Entity\InMemoryEntityRepository;
use NeNeRecords\Tests\EntityType\InMemoryEntityTypeRepository;

final class ListPublicSettingsMediaTest extends TestCase
{
    public function testMediaSettingResolvesToUrl(): void
    {
        $settings = new InMemorySettingRepository([
            new SettingDef('logo_media_id', 'media', '', true, 'Logo'),
        ]);
        $settings->applyValueDirect('logo_media_id', '7', null);

        $media = new InMemoryMediaRepository([
            new Media(
                id: 7,
                originalName: 'logo.png',
                storedName: 'logo.png',
                mimeType: 'image/png',
                size: 100,
                url: '/media/2026/06/logo.png',
                createdAt: '2026-06-17 00:00:00',
            ),
        ]);

        $output = (new ListPublicSettingsUseCase($settings, $media, new InMemoryEntityRepository(), new InMemoryEntityTypeRepository()))->execute();

        $this->assertSame('/media/2026/06/logo.png', $this->valueOf($output->items, 'logo_media_id'));
    }

    public function testUnsetOrMissingMediaResolvesToEmpty(): void
    {
        $settings = new InMemorySettingRepository([
            new SettingDef('logo_media_id', 'media', '', true, 'Logo'),
        ]);
        // value points at a media id that does not exist
        $settings->applyValueDirect('logo_media_id', '999', null);

        $output = (new ListPublicSettingsUseCase($settings, new InMemoryMediaRepository(), new InMemoryEntityRepository(), new InMemoryEntityTypeRepository()))->execute();

        $this->assertSame('', $this->valueOf($output->items, 'logo_media_id'));
    }

    /**
     * @param list<\NeNeRecords\Setting\SettingEntry> $items
     */
    private function valueOf(array $items, string $key): ?string
    {
        foreach ($items as $entry) {
            if ($entry->def->settingKey === $key) {
                return $entry->effectiveValue;
            }
        }

        return null;
    }
}
