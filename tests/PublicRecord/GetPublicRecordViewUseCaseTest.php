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
}
