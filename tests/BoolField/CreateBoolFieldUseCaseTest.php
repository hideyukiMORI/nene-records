<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\BoolField;

use NeNeRecords\BoolField\BoolField;
use NeNeRecords\BoolField\CreateBoolFieldInput;
use NeNeRecords\BoolField\CreateBoolFieldUseCase;
use NeNeRecords\BoolField\FieldKeyNotRegisteredException;
use NeNeRecords\BoolField\FieldTypeMismatchException;
use NeNeRecords\BoolField\UpdateBoolFieldInput;
use NeNeRecords\BoolField\UpdateBoolFieldUseCase;
use NeNeRecords\Entity\Entity;
use NeNeRecords\FieldDef\FieldDef;
use NeNeRecords\Tests\Entity\InMemoryEntityRepository;
use NeNeRecords\Tests\FieldDef\InMemoryFieldDefRepository;
use PHPUnit\Framework\TestCase;

final class CreateBoolFieldUseCaseTest extends TestCase
{
    public function testReturnsOutputWithNewId(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(id: null, entityTypeId: 99));

        $fieldDefs = new InMemoryFieldDefRepository([
            new FieldDef(entityTypeId: 99, fieldKey: 'count', dataType: 'bool', id: 1),
        ]);

        $boolFields = new InMemoryBoolFieldRepository([]);
        $useCase = new CreateBoolFieldUseCase($boolFields, $entities, $fieldDefs);

        $output = $useCase->execute(new CreateBoolFieldInput(entityId: $entityId, fieldKey: 'count', value: true));

        self::assertSame(1, $output->id);
        self::assertSame($entityId, $output->entityId);
        self::assertSame('count', $output->fieldKey);
        self::assertSame(true, $output->value);
    }

    public function testAssignsSequentialIds(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(id: null, entityTypeId: 1));

        $fieldDefs = new InMemoryFieldDefRepository([
            new FieldDef(entityTypeId: 1, fieldKey: 'a', dataType: 'bool', id: 1),
            new FieldDef(entityTypeId: 1, fieldKey: 'b', dataType: 'bool', id: 2),
        ]);

        $boolFields = new InMemoryBoolFieldRepository([]);
        $useCase = new CreateBoolFieldUseCase($boolFields, $entities, $fieldDefs);

        $first = $useCase->execute(new CreateBoolFieldInput(entityId: $entityId, fieldKey: 'a', value: true));
        $second = $useCase->execute(new CreateBoolFieldInput(entityId: $entityId, fieldKey: 'b', value: false));

        self::assertSame(1, $first->id);
        self::assertSame(2, $second->id);
    }

    public function testThrowsFieldKeyNotRegisteredExceptionWhenFieldDefAbsent(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(id: null, entityTypeId: 1));

        $fieldDefs = new InMemoryFieldDefRepository([]);
        $boolFields = new InMemoryBoolFieldRepository([]);
        $useCase = new CreateBoolFieldUseCase($boolFields, $entities, $fieldDefs);

        $this->expectException(FieldKeyNotRegisteredException::class);

        $useCase->execute(new CreateBoolFieldInput(entityId: $entityId, fieldKey: 'count', value: true));
    }

    public function testThrowsFieldTypeMismatchExceptionWhenDataTypeIsNotBool(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(id: null, entityTypeId: 1));

        $fieldDefs = new InMemoryFieldDefRepository([
            new FieldDef(entityTypeId: 1, fieldKey: 'title', dataType: 'text', id: 1),
        ]);

        $boolFields = new InMemoryBoolFieldRepository([]);
        $useCase = new CreateBoolFieldUseCase($boolFields, $entities, $fieldDefs);

        $this->expectException(FieldTypeMismatchException::class);

        $useCase->execute(new CreateBoolFieldInput(entityId: $entityId, fieldKey: 'title', value: true));
    }
}

final class UpdateBoolFieldUseCaseTest extends TestCase
{
    public function testThrowsFieldKeyNotRegisteredExceptionWhenUpdatedKeyIsUnregistered(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(id: null, entityTypeId: 1));

        $fieldDefs = new InMemoryFieldDefRepository([
            new FieldDef(entityTypeId: 1, fieldKey: 'count', dataType: 'bool', id: 1),
        ]);

        $boolFields = new InMemoryBoolFieldRepository([]);
        $boolFields->save(new BoolField(entityId: $entityId, fieldKey: 'count', value: true, id: null));

        $useCase = new UpdateBoolFieldUseCase($boolFields, $entities, $fieldDefs);

        $this->expectException(FieldKeyNotRegisteredException::class);

        $useCase->execute(new UpdateBoolFieldInput(id: 1, fieldKey: 'body', value: false));
    }
}
