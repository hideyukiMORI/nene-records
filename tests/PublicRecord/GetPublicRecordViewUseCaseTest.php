<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\PublicRecord;

use NeNeRecords\Entity\Entity;
use NeNeRecords\Entity\EntityStatus;
use NeNeRecords\EntityType\EntityType;
use NeNeRecords\FieldDef\FieldDef;
use NeNeRecords\PublicRecord\GetPublicRecordViewInput;
use NeNeRecords\PublicRecord\GetPublicRecordViewUseCase;
use NeNeRecords\PublicRecord\PublicEntityTypeNotFoundException;
use NeNeRecords\PublicRecord\PublicRecordNotFoundException;
use NeNeRecords\Setting\ListPublicSettingsUseCase;
use NeNeRecords\Tests\BoolField\InMemoryBoolFieldRepository;
use NeNeRecords\Tests\DateTimeField\InMemoryDateTimeFieldRepository;
use NeNeRecords\Tests\Entity\InMemoryEntityRepository;
use NeNeRecords\Tests\EntityRelation\InMemoryEntityRelationRepository;
use NeNeRecords\Tests\EntityType\InMemoryEntityTypeRepository;
use NeNeRecords\Tests\EnumField\InMemoryEnumFieldRepository;
use NeNeRecords\Tests\FieldDef\InMemoryFieldDefRepository;
use NeNeRecords\Tests\IntField\InMemoryIntFieldRepository;
use NeNeRecords\Tests\Media\InMemoryMediaRepository;
use NeNeRecords\Tests\Setting\InMemorySettingRepository;
use NeNeRecords\Tests\TextField\InMemoryTextFieldRepository;
use NeNeRecords\TextField\TextField;
use PHPUnit\Framework\TestCase;

final class GetPublicRecordViewUseCaseTest extends TestCase
{
    public function testReturnsBootstrapWithScalarFields(): void
    {
        $entityTypes = new InMemoryEntityTypeRepository([
            new EntityType(name: 'Article', slug: 'article', id: 1),
        ]);
        $entities = new InMemoryEntityRepository([
            new Entity(id: 10, entityTypeId: 1, slug: 'hello-world', status: EntityStatus::Published),
        ]);
        $fieldDefs = new InMemoryFieldDefRepository([
            new FieldDef(entityTypeId: 1, fieldKey: 'title', dataType: 'text', id: 1),
            new FieldDef(entityTypeId: 1, fieldKey: 'views', dataType: 'int', id: 2),
        ]);
        $textFields = new InMemoryTextFieldRepository([
            new TextField(entityId: 10, fieldKey: 'title', value: 'Hello', id: 1),
        ], $entities);
        $intFields = new InMemoryIntFieldRepository([
            new \NeNeRecords\IntField\IntField(entityId: 10, fieldKey: 'views', value: 42, id: 1),
        ]);

        $useCase = new GetPublicRecordViewUseCase(
            $entityTypes,
            $entities,
            $fieldDefs,
            $textFields,
            $intFields,
            new InMemoryEnumFieldRepository(),
            new InMemoryBoolFieldRepository(),
            new InMemoryDateTimeFieldRepository(),
            new InMemoryEntityRelationRepository(),
            new ListPublicSettingsUseCase(new InMemorySettingRepository(), new InMemoryMediaRepository()),
        );

        $output = $useCase->execute(new GetPublicRecordViewInput('article', 'hello-world'));

        self::assertSame('article', $output->entityTypeSlug);
        self::assertSame('Hello', $output->pageTitle);
        self::assertSame('article', $output->bootstrap['entityTypeSlug']);
        self::assertSame(10, $output->bootstrap['entityId']);
        self::assertSame('Hello', $output->bootstrap['textFields']['items'][0]['value']);
        self::assertSame(42, $output->bootstrap['intFields']['items'][0]['value']);
        self::assertArrayHasKey('publicSettings', $output->bootstrap);
    }

    public function testThrowsWhenEntityTypeMissing(): void
    {
        $useCase = new GetPublicRecordViewUseCase(
            new InMemoryEntityTypeRepository(),
            new InMemoryEntityRepository(),
            new InMemoryFieldDefRepository(),
            new InMemoryTextFieldRepository(),
            new InMemoryIntFieldRepository(),
            new InMemoryEnumFieldRepository(),
            new InMemoryBoolFieldRepository(),
            new InMemoryDateTimeFieldRepository(),
            new InMemoryEntityRelationRepository(),
            new ListPublicSettingsUseCase(new InMemorySettingRepository(), new InMemoryMediaRepository()),
        );

        $this->expectException(PublicEntityTypeNotFoundException::class);
        $useCase->execute(new GetPublicRecordViewInput('missing', 'some-slug'));
    }

    public function testThrowsWhenRecordMissing(): void
    {
        $entityTypes = new InMemoryEntityTypeRepository([
            new EntityType(name: 'Article', slug: 'article', id: 1),
        ]);

        $useCase = new GetPublicRecordViewUseCase(
            $entityTypes,
            new InMemoryEntityRepository(),
            new InMemoryFieldDefRepository(),
            new InMemoryTextFieldRepository(),
            new InMemoryIntFieldRepository(),
            new InMemoryEnumFieldRepository(),
            new InMemoryBoolFieldRepository(),
            new InMemoryDateTimeFieldRepository(),
            new InMemoryEntityRelationRepository(),
            new ListPublicSettingsUseCase(new InMemorySettingRepository(), new InMemoryMediaRepository()),
        );

        $this->expectException(PublicRecordNotFoundException::class);
        $useCase->execute(new GetPublicRecordViewInput('article', 'nonexistent'));
    }

    public function testBuildsChapterNavAndHidesReservedFieldsForAMiddleChapter(): void
    {
        $output = $this->executeChapter(chapterNo: 2, chapterTotal: 11);

        self::assertNotNull($output->chapterNav);
        self::assertSame('/work/aozora-000148-752', $output->chapterNav->indexUrl);
        self::assertSame('/work/aozora-000148-752-1', $output->chapterNav->prevUrl);
        self::assertSame('/work/aozora-000148-752-3', $output->chapterNav->nextUrl);
        self::assertSame(2, $output->chapterNav->chapterNo);
        self::assertSame(11, $output->chapterNav->chapterTotal);

        // The reserved chapter-nav metadata never renders as an ordinary field…
        $displayedKeys = array_map(
            static fn ($field): string => $field->fieldKey,
            $output->displayFields,
        );
        self::assertNotContains('series', $displayedKeys);
        self::assertNotContains('chapter_no', $displayedKeys);
        self::assertNotContains('chapter_total', $displayedKeys);
        // …while ordinary content fields still do.
        self::assertContains('title', $displayedKeys);
        self::assertContains('body', $displayedKeys);

        // The JSON bootstrap mirrors the nav.
        self::assertSame(
            [
                'indexUrl' => '/work/aozora-000148-752',
                'prevUrl' => '/work/aozora-000148-752-1',
                'nextUrl' => '/work/aozora-000148-752-3',
                'chapterNo' => 2,
                'chapterTotal' => 11,
            ],
            $output->bootstrap['chapterNav'],
        );
    }

    public function testFirstChapterHasNoPreviousLink(): void
    {
        $output = $this->executeChapter(chapterNo: 1, chapterTotal: 11);

        self::assertNotNull($output->chapterNav);
        self::assertNull($output->chapterNav->prevUrl);
        self::assertSame('/work/aozora-000148-752-2', $output->chapterNav->nextUrl);
    }

    public function testLastChapterHasNoNextLink(): void
    {
        $output = $this->executeChapter(chapterNo: 11, chapterTotal: 11);

        self::assertNotNull($output->chapterNav);
        self::assertSame('/work/aozora-000148-752-10', $output->chapterNav->prevUrl);
        self::assertNull($output->chapterNav->nextUrl);
    }

    public function testChapterNavIsNullWithoutSeriesFields(): void
    {
        $entityTypes = new InMemoryEntityTypeRepository([
            new EntityType(name: 'Article', slug: 'article', id: 1),
        ]);
        $entities = new InMemoryEntityRepository([
            new Entity(id: 10, entityTypeId: 1, slug: 'hello-world', status: EntityStatus::Published),
        ]);
        $fieldDefs = new InMemoryFieldDefRepository([
            new FieldDef(entityTypeId: 1, fieldKey: 'title', dataType: 'text', id: 1),
        ]);
        $textFields = new InMemoryTextFieldRepository([
            new TextField(entityId: 10, fieldKey: 'title', value: 'Hello', id: 1),
        ], $entities);

        $useCase = new GetPublicRecordViewUseCase(
            $entityTypes,
            $entities,
            $fieldDefs,
            $textFields,
            new InMemoryIntFieldRepository(),
            new InMemoryEnumFieldRepository(),
            new InMemoryBoolFieldRepository(),
            new InMemoryDateTimeFieldRepository(),
            new InMemoryEntityRelationRepository(),
            new ListPublicSettingsUseCase(new InMemorySettingRepository(), new InMemoryMediaRepository()),
        );

        $output = $useCase->execute(new GetPublicRecordViewInput('article', 'hello-world'));

        self::assertNull($output->chapterNav);
        self::assertNull($output->bootstrap['chapterNav']);
    }

    /**
     * Build and run the public view for one chapter of the work
     * `aozora-000148-752` (slug permalink), used by the chapter-nav tests.
     */
    private function executeChapter(int $chapterNo, int $chapterTotal): \NeNeRecords\PublicRecord\GetPublicRecordViewOutput
    {
        $series = 'aozora-000148-752';
        $slug = $series . '-' . $chapterNo;

        $entityTypes = new InMemoryEntityTypeRepository([
            new EntityType(name: 'Work', slug: 'work', id: 1, permalinkPattern: '/{type}/{slug}'),
        ]);
        $entities = new InMemoryEntityRepository([
            new Entity(id: 20, entityTypeId: 1, slug: $slug, status: EntityStatus::Published),
        ]);
        $fieldDefs = new InMemoryFieldDefRepository([
            new FieldDef(entityTypeId: 1, fieldKey: 'title', dataType: 'text', id: 1),
            new FieldDef(entityTypeId: 1, fieldKey: 'body', dataType: 'markdown', id: 2),
            new FieldDef(entityTypeId: 1, fieldKey: 'series', dataType: 'text', id: 3),
            new FieldDef(entityTypeId: 1, fieldKey: 'chapter_no', dataType: 'int', id: 4),
            new FieldDef(entityTypeId: 1, fieldKey: 'chapter_total', dataType: 'int', id: 5),
        ]);
        $textFields = new InMemoryTextFieldRepository([
            new TextField(entityId: 20, fieldKey: 'title', value: '坊っちゃん 第' . $chapterNo . '章', id: 1),
            new TextField(entityId: 20, fieldKey: 'body', value: '本文。', id: 2),
            new TextField(entityId: 20, fieldKey: 'series', value: $series, id: 3),
        ], $entities);
        $intFields = new InMemoryIntFieldRepository([
            new \NeNeRecords\IntField\IntField(entityId: 20, fieldKey: 'chapter_no', value: $chapterNo, id: 1),
            new \NeNeRecords\IntField\IntField(entityId: 20, fieldKey: 'chapter_total', value: $chapterTotal, id: 2),
        ]);

        $useCase = new GetPublicRecordViewUseCase(
            $entityTypes,
            $entities,
            $fieldDefs,
            $textFields,
            $intFields,
            new InMemoryEnumFieldRepository(),
            new InMemoryBoolFieldRepository(),
            new InMemoryDateTimeFieldRepository(),
            new InMemoryEntityRelationRepository(),
            new ListPublicSettingsUseCase(new InMemorySettingRepository(), new InMemoryMediaRepository()),
        );

        return $useCase->execute(new GetPublicRecordViewInput('work', $slug));
    }
}
