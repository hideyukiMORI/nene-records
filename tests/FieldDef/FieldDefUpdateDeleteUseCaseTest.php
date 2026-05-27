<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\FieldDef;

use NeNeRecords\EntityType\EntityType;
use NeNeRecords\EntityType\EntityTypeNotFoundException;
use NeNeRecords\FieldDef\DeleteFieldDefInput;
use NeNeRecords\FieldDef\DeleteFieldDefUseCase;
use NeNeRecords\FieldDef\FieldDef;
use NeNeRecords\FieldDef\FieldDefConflictException;
use NeNeRecords\FieldDef\FieldDefNotFoundException;
use NeNeRecords\FieldDef\UpdateFieldDefInput;
use NeNeRecords\FieldDef\UpdateFieldDefUseCase;
use NeNeRecords\Tests\EntityType\InMemoryEntityTypeRepository;
use PHPUnit\Framework\TestCase;

final class FieldDefUpdateDeleteUseCaseTest extends TestCase
{
    public function testUpdateFieldDefChangesFieldKeyAndDataType(): void
    {
        $entityTypes = new InMemoryEntityTypeRepository([
            new EntityType(name: 'Post', slug: 'post', id: 1),
        ]);
        $fieldDefs = new InMemoryFieldDefRepository([
            new FieldDef(entityTypeId: 1, fieldKey: 'title', dataType: 'text', id: 1),
        ]);
        $useCase = new UpdateFieldDefUseCase($fieldDefs, $entityTypes);

        $output = $useCase->execute(new UpdateFieldDefInput(
            id: 1,
            entityTypeId: 1,
            fieldKey: 'headline',
            dataType: 'text',
        ));

        self::assertSame(1, $output->id);
        self::assertSame(1, $output->entityTypeId);
        self::assertSame('headline', $output->fieldKey);
        self::assertSame('text', $output->dataType);
    }

    public function testUpdateFieldDefThrowsNotFoundWhenIdDoesNotExist(): void
    {
        $entityTypes = new InMemoryEntityTypeRepository([
            new EntityType(name: 'Post', slug: 'post', id: 1),
        ]);
        $fieldDefs = new InMemoryFieldDefRepository([]);
        $useCase = new UpdateFieldDefUseCase($fieldDefs, $entityTypes);

        $this->expectException(FieldDefNotFoundException::class);

        $useCase->execute(new UpdateFieldDefInput(id: 99, entityTypeId: 1, fieldKey: 'title', dataType: 'text'));
    }

    public function testUpdateFieldDefThrowsEntityTypeNotFoundWhenEntityTypeDoesNotExist(): void
    {
        $entityTypes = new InMemoryEntityTypeRepository([]);
        $fieldDefs = new InMemoryFieldDefRepository([
            new FieldDef(entityTypeId: 1, fieldKey: 'title', dataType: 'text', id: 1),
        ]);
        $useCase = new UpdateFieldDefUseCase($fieldDefs, $entityTypes);

        $this->expectException(EntityTypeNotFoundException::class);

        $useCase->execute(new UpdateFieldDefInput(id: 1, entityTypeId: 99, fieldKey: 'title', dataType: 'text'));
    }

    public function testUpdateFieldDefThrowsConflictOnDuplicateFieldKeyInSameEntityType(): void
    {
        $entityTypes = new InMemoryEntityTypeRepository([
            new EntityType(name: 'Post', slug: 'post', id: 1),
        ]);
        $fieldDefs = new InMemoryFieldDefRepository([
            new FieldDef(entityTypeId: 1, fieldKey: 'title', dataType: 'text', id: 1),
            new FieldDef(entityTypeId: 1, fieldKey: 'body', dataType: 'text', id: 2),
        ]);
        $useCase = new UpdateFieldDefUseCase($fieldDefs, $entityTypes);

        $this->expectException(FieldDefConflictException::class);

        // Rename field 1's key to 'body' which is already used by field 2.
        $useCase->execute(new UpdateFieldDefInput(id: 1, entityTypeId: 1, fieldKey: 'body', dataType: 'text'));
    }

    public function testDeleteFieldDefSoftDeletesIt(): void
    {
        $fieldDefs = new InMemoryFieldDefRepository([
            new FieldDef(entityTypeId: 1, fieldKey: 'title', dataType: 'text', id: 1),
        ]);
        $useCase = new DeleteFieldDefUseCase($fieldDefs);

        $useCase->execute(new DeleteFieldDefInput(id: 1));

        // After soft-delete, findById returns null.
        self::assertNull($fieldDefs->findById(1));
    }

    public function testDeleteFieldDefThrowsNotFoundWhenIdDoesNotExist(): void
    {
        $fieldDefs = new InMemoryFieldDefRepository([]);
        $useCase = new DeleteFieldDefUseCase($fieldDefs);

        $this->expectException(FieldDefNotFoundException::class);

        $useCase->execute(new DeleteFieldDefInput(id: 99));
    }
}
