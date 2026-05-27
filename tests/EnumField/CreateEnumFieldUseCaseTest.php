<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\EnumField;

use NeNeRecords\Entity\Entity;
use NeNeRecords\EnumField\CreateEnumFieldInput;
use NeNeRecords\EnumField\CreateEnumFieldUseCase;
use NeNeRecords\EnumField\EnumField;
use NeNeRecords\EnumField\UpdateEnumFieldInput;
use NeNeRecords\EnumField\UpdateEnumFieldUseCase;
use NeNeRecords\FieldDef\FieldDef;
use NeNeRecords\FieldDef\FieldKeyNotRegisteredException;
use NeNeRecords\FieldDef\FieldTypeMismatchException;
use NeNeRecords\Tests\Entity\InMemoryEntityRepository;
use NeNeRecords\Tests\FieldDef\InMemoryFieldDefRepository;
use PHPUnit\Framework\TestCase;

final class CreateEnumFieldUseCaseTest extends TestCase
{
    public function testReturnsOutputWithNewId(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(id: null, entityTypeId: 99));

        $fieldDefs = new InMemoryFieldDefRepository([
            new FieldDef(entityTypeId: 99, fieldKey: 'count', dataType: 'enum', id: 1),
        ]);

        $enumFields = new InMemoryEnumFieldRepository([]);
        $useCase = new CreateEnumFieldUseCase($enumFields, $entities, $fieldDefs);

        $output = $useCase->execute(new CreateEnumFieldInput(entityId: $entityId, fieldKey: 'count', value: 'active'));

        self::assertSame(1, $output->id);
        self::assertSame($entityId, $output->entityId);
        self::assertSame('count', $output->fieldKey);
        self::assertSame('active', $output->value);
    }

    public function testAssignsSequentialIds(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(id: null, entityTypeId: 1));

        $fieldDefs = new InMemoryFieldDefRepository([
            new FieldDef(entityTypeId: 1, fieldKey: 'a', dataType: 'enum', id: 1),
            new FieldDef(entityTypeId: 1, fieldKey: 'b', dataType: 'enum', id: 2),
        ]);

        $enumFields = new InMemoryEnumFieldRepository([]);
        $useCase = new CreateEnumFieldUseCase($enumFields, $entities, $fieldDefs);

        $first = $useCase->execute(new CreateEnumFieldInput(entityId: $entityId, fieldKey: 'a', value: 'active'));
        $second = $useCase->execute(new CreateEnumFieldInput(entityId: $entityId, fieldKey: 'b', value: 'inactive'));

        self::assertSame(1, $first->id);
        self::assertSame(2, $second->id);
    }

    public function testThrowsFieldKeyNotRegisteredExceptionWhenFieldDefAbsent(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(id: null, entityTypeId: 1));

        $fieldDefs = new InMemoryFieldDefRepository([]);
        $enumFields = new InMemoryEnumFieldRepository([]);
        $useCase = new CreateEnumFieldUseCase($enumFields, $entities, $fieldDefs);

        $this->expectException(FieldKeyNotRegisteredException::class);

        $useCase->execute(new CreateEnumFieldInput(entityId: $entityId, fieldKey: 'count', value: 'active'));
    }

    public function testThrowsFieldTypeMismatchExceptionWhenDataTypeIsNotEnum(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(id: null, entityTypeId: 1));

        $fieldDefs = new InMemoryFieldDefRepository([
            new FieldDef(entityTypeId: 1, fieldKey: 'title', dataType: 'text', id: 1),
        ]);

        $enumFields = new InMemoryEnumFieldRepository([]);
        $useCase = new CreateEnumFieldUseCase($enumFields, $entities, $fieldDefs);

        $this->expectException(FieldTypeMismatchException::class);

        $useCase->execute(new CreateEnumFieldInput(entityId: $entityId, fieldKey: 'title', value: 'active'));
    }
}

final class UpdateEnumFieldUseCaseTest extends TestCase
{
    public function testThrowsFieldKeyNotRegisteredExceptionWhenUpdatedKeyIsUnregistered(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(id: null, entityTypeId: 1));

        $fieldDefs = new InMemoryFieldDefRepository([
            new FieldDef(entityTypeId: 1, fieldKey: 'count', dataType: 'enum', id: 1),
        ]);

        $enumFields = new InMemoryEnumFieldRepository([]);
        $enumFields->save(new EnumField(entityId: $entityId, fieldKey: 'count', value: 'active', id: null));

        $useCase = new UpdateEnumFieldUseCase($enumFields, $entities, $fieldDefs);

        $this->expectException(FieldKeyNotRegisteredException::class);

        $useCase->execute(new UpdateEnumFieldInput(id: 1, fieldKey: 'body', value: 'inactive'));
    }
}
