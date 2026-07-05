<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Setting;

use NeNeRecords\Media\Media;
use NeNeRecords\Media\MediaRepositoryInterface;
use NeNeRecords\PublicRecord\FrontPageSetting;
use NeNeRecords\Setting\ListPublicSettingsUseCase;
use NeNeRecords\Setting\SettingDef;
use NeNeRecords\Tests\Entity\InMemoryEntityRepository;
use NeNeRecords\Tests\EntityType\InMemoryEntityTypeRepository;
use NeNeRecords\Tests\Media\InMemoryMediaRepository;
use PHPUnit\Framework\TestCase;

final class ListPublicSettingsThemeOverridesImagesTest extends TestCase
{
    public function testResolvesImageIdsToUrlsPerSlot(): void
    {
        $settings = new InMemorySettingRepository([
            new SettingDef('theme_overrides', 'text', '{}', true, 'Theme customizations'),
        ]);
        $settings->applyValueDirect('theme_overrides', (string) json_encode([
            'aurora' => [
                'accent' => '#1e90ff',
                'images' => [
                    'hero' => ['light' => 2],
                    'background' => ['dark' => 2],
                    'logo' => ['light' => 3, 'dark' => 999],
                ],
            ],
        ]), null);

        $media = new InMemoryMediaRepository([
            $this->image(2, '/media/2026/06/hero.png'),
            $this->image(3, '/media/2026/06/logo.png'),
        ]);

        $images = $this->resolvedOverrides($settings, $media)['aurora']['images'];

        // hero / background bake to the lg derivative; logo uses the raw url.
        self::assertSame('/media/lg/2026/06/hero.png', $images['hero']['light']);
        self::assertSame('/media/lg/2026/06/hero.png', $images['background']['dark']);
        self::assertSame('/media/2026/06/logo.png', $images['logo']['light']);
        // Missing media id (999) is dropped so the SPA falls back cleanly.
        self::assertArrayNotHasKey('dark', $images['logo']);
    }

    public function testLeavesNonImageKeysUntouched(): void
    {
        $settings = new InMemorySettingRepository([
            new SettingDef('theme_overrides', 'text', '{}', true, 'Theme customizations'),
        ]);
        $settings->applyValueDirect('theme_overrides', (string) json_encode([
            'aurora' => ['accent' => '#1e90ff', 'fontBody' => 'source-serif'],
        ]), null);

        $resolved = $this->resolvedOverrides($settings, new InMemoryMediaRepository());

        self::assertSame('#1e90ff', $resolved['aurora']['accent']);
        self::assertSame('source-serif', $resolved['aurora']['fontBody']);
    }

    public function testMalformedJsonIsPassedThroughUnchanged(): void
    {
        $settings = new InMemorySettingRepository([
            new SettingDef('theme_overrides', 'text', '{}', true, 'Theme customizations'),
        ]);
        $settings->applyValueDirect('theme_overrides', 'not json', null);

        self::assertSame('not json', $this->rawOverrides($settings, new InMemoryMediaRepository()));
    }

    private function image(int $id, string $url): Media
    {
        return new Media(
            id: $id,
            originalName: 'x.png',
            storedName: 'x.png',
            mimeType: 'image/png',
            size: 100,
            url: $url,
            createdAt: '2026-06-17 00:00:00',
        );
    }

    private function rawOverrides(InMemorySettingRepository $settings, MediaRepositoryInterface $media): ?string
    {
        $output = (new ListPublicSettingsUseCase(
            $settings,
            $media,
            new FrontPageSetting($settings, new InMemoryEntityRepository(), new InMemoryEntityTypeRepository()),
        ))->execute();

        foreach ($output->items as $entry) {
            if ($entry->def->settingKey === 'theme_overrides') {
                return $entry->effectiveValue;
            }
        }

        return null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function resolvedOverrides(InMemorySettingRepository $settings, MediaRepositoryInterface $media): array
    {
        $raw = $this->rawOverrides($settings, $media);
        self::assertIsString($raw);
        $decoded = json_decode($raw, true);
        self::assertIsArray($decoded);

        /** @var array<string, array<string, mixed>> $decoded */
        return $decoded;
    }
}
