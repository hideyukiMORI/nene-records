<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\IntField;

use NeNeRecords\Entity\Entity;
use NeNeRecords\FieldDef\FieldDef;
use NeNeRecords\IntField\CreateIntFieldInput;
use NeNeRecords\IntField\CreateIntFieldUseCase;
use NeNeRecords\IntField\FieldKeyNotRegisteredException;
use NeNeRecords\IntField\FieldTypeMismatchException;
use NeNeRecords\IntField\IntField;
use NeNeRecords\IntField\UpdateIntFieldInput;
use NeNeRecords\IntField\UpdateIntFieldUseCase;
use NeNeRecords\Tests\Entity\InMemoryEntityRepository;
use NeNeRecords\Tests\FieldDef\InMemoryFieldDefRepository;
use PHPUnit\Framework\TestCase;

final class CreateIntFieldUseCaseTest extends TestCase
{
    public function testReturnsOutputWithNewId(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(id: null, entityTypeId: 99));

        $fieldDefs = new InMemoryFieldDefRepository([
            new FieldDef(entityTypeId: 99, fieldKey: 'count', dataType: 'int', id: 1),
        ]);

        $intFields = new InMemoryIntFieldRepository([]);
        $useCase = new CreateIntFieldUseCase($intFields, $entities, $fieldDefs);

        $output = $useCase->execute(new CreateIntFieldInput(entityId: $entityId, fieldKey: 'count', value: 42));

        self::assertSame(1, $output->id);
        self::assertSame($entityId, $output->entityId);
        self::assertSame('count', $output->fieldKey);
        self::assertSame(42, $output->value);
    }

    public function testAssignsSequentialIds(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(id: null, entityTypeId: 1));

        $fieldDefs = new InMemoryFieldDefRepository([
            new FieldDef(entityTypeId: 1, fieldKey: 'a', dataType: 'int', id: 1),
            new FieldDef(entityTypeId: 1, fieldKey: 'b', dataType: 'int', id: 2),
        ]);

        $intFields = new InMemoryIntFieldRepository([]);
        $useCase = new CreateIntFieldUseCase($intFields, $entities, $fieldDefs);

        $first = $useCase->execute(new CreateIntFieldInput(entityId: $entityId, fieldKey: 'a', value: 1));
        $second = $useCase->execute(new CreateIntFieldInput(entityId: $entityId, fieldKey: 'b', value: 2));

        self::assertSame(1, $first->id);
        self::assertSame(2, $second->id);
    }

    public function testThrowsFieldKeyNotRegisteredExceptionWhenFieldDefAbsent(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(id: null, entityTypeId: 1));

        $fieldDefs = new InMemoryFieldDefRepository([]);
        $intFields = new InMemoryIntFieldRepository([]);
        $useCase = new CreateIntFieldUseCase($intFields, $entities, $fieldDefs);

        $this->expectException(FieldKeyNotRegisteredException::class);

        $useCase->execute(new CreateIntFieldInput(entityId: $entityId, fieldKey: 'count', value: 1));
    }

    public function testThrowsFieldTypeMismatchExceptionWhenDataTypeIsNotInt(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(id: null, entityTypeId: 1));

        $fieldDefs = new InMemoryFieldDefRepository([
            new FieldDef(entityTypeId: 1, fieldKey: 'title', dataType: 'text', id: 1),
        ]);

        $intFields = new InMemoryIntFieldRepository([]);
        $useCase = new CreateIntFieldUseCase($intFields, $entities, $fieldDefs);

        $this->expectException(FieldTypeMismatchException::class);

        $useCase->execute(new CreateIntFieldInput(entityId: $entityId, fieldKey: 'title', value: 1));
    }
}

final class UpdateIntFieldUseCaseTest extends TestCase
{
    public function testThrowsFieldKeyNotRegisteredExceptionWhenUpdatedKeyIsUnregistered(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(id: null, entityTypeId: 1));

        $fieldDefs = new InMemoryFieldDefRepository([
            new FieldDef(entityTypeId: 1, fieldKey: 'count', dataType: 'int', id: 1),
        ]);

        $intFields = new InMemoryIntFieldRepository([]);
        $intFields->save(new IntField(entityId: $entityId, fieldKey: 'count', value: 10, id: null));

        $useCase = new UpdateIntFieldUseCase($intFields, $entities, $fieldDefs);

        $this->expectException(FieldKeyNotRegisteredException::class);

        $useCase->execute(new UpdateIntFieldInput(id: 1, fieldKey: 'body', value: 20));
    }
}
