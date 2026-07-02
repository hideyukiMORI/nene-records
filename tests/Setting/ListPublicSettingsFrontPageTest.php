<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Setting;

use DateTimeImmutable;
use NeNeRecords\Entity\Entity;
use NeNeRecords\Entity\EntityStatus;
use NeNeRecords\EntityType\EntityType;
use NeNeRecords\Setting\ListPublicSettingsUseCase;
use NeNeRecords\Setting\SettingDef;
use NeNeRecords\Tests\Entity\InMemoryEntityRepository;
use NeNeRecords\Tests\EntityType\InMemoryEntityTypeRepository;
use NeNeRecords\Tests\Media\InMemoryMediaRepository;
use PHPUnit\Framework\TestCase;

/**
 * Public settings resolve the `front_page` record id to a canonical path (#701),
 * mirroring how `media` resolves an id to a URL. Anything that is not a currently
 * published, in-org record resolves to '' so the SPA falls back to the default home.
 */
final class ListPublicSettingsFrontPageTest extends TestCase
{
    public function testPublishedFrontPageResolvesToItsCanonicalPath(): void
    {
        $output = $this->resolve(
            frontPageValue: '5',
            entities: [$this->page(5, 2, 'about', EntityStatus::Published)],
        );

        self::assertSame('/pages/about', $this->valueOf($output, 'front_page'));
    }

    public function testDraftFrontPageResolvesToEmpty(): void
    {
        $output = $this->resolve(
            frontPageValue: '5',
            entities: [$this->page(5, 2, 'about', EntityStatus::Draft)],
        );

        self::assertSame('', $this->valueOf($output, 'front_page'));
    }

    public function testMissingFrontPageRecordResolvesToEmpty(): void
    {
        $output = $this->resolve(frontPageValue: '999', entities: []);

        self::assertSame('', $this->valueOf($output, 'front_page'));
    }

    public function testUnsetFrontPageResolvesToEmpty(): void
    {
        $output = $this->resolve(frontPageValue: '', entities: []);

        self::assertSame('', $this->valueOf($output, 'front_page'));
    }

    /**
     * @param list<Entity> $entities
     */
    private function resolve(string $frontPageValue, array $entities): \NeNeRecords\Setting\ListPublicSettingsOutput
    {
        $settings = new InMemorySettingRepository([
            new SettingDef('front_page', 'text', '', true, 'Front page'),
        ]);
        $settings->applyValueDirect('front_page', $frontPageValue, null);

        $entityTypes = new InMemoryEntityTypeRepository([
            new EntityType(name: 'Pages', slug: 'pages', id: 2, permalinkPattern: '/{type}/{slug}'),
        ]);

        return (new ListPublicSettingsUseCase(
            $settings,
            new InMemoryMediaRepository(),
            new InMemoryEntityRepository($entities),
            $entityTypes,
        ))->execute();
    }

    private function page(int $id, int $typeId, string $slug, EntityStatus $status): Entity
    {
        return new Entity(
            id: $id,
            entityTypeId: $typeId,
            slug: $slug,
            status: $status,
            publishedAt: new DateTimeImmutable('2026-06-01 00:00:00'),
        );
    }

    private function valueOf(\NeNeRecords\Setting\ListPublicSettingsOutput $output, string $key): ?string
    {
        foreach ($output->items as $entry) {
            if ($entry->def->settingKey === $key) {
                return $entry->effectiveValue;
            }
        }

        return null;
    }
}
