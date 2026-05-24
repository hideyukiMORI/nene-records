<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\DateTimeField;

use NeNeRecords\DateTimeField\CreateDateTimeFieldInput;
use NeNeRecords\DateTimeField\CreateDateTimeFieldUseCase;
use NeNeRecords\DateTimeField\DateTimeField;
use NeNeRecords\DateTimeField\FieldKeyNotRegisteredException;
use NeNeRecords\DateTimeField\FieldTypeMismatchException;
use NeNeRecords\DateTimeField\UpdateDateTimeFieldInput;
use NeNeRecords\DateTimeField\UpdateDateTimeFieldUseCase;
use NeNeRecords\Entity\Entity;
use NeNeRecords\FieldDef\FieldDef;
use NeNeRecords\Tests\Entity\InMemoryEntityRepository;
use NeNeRecords\Tests\FieldDef\InMemoryFieldDefRepository;
use PHPUnit\Framework\TestCase;

final class CreateDateTimeFieldUseCaseTest extends TestCase
{
    public function testReturnsOutputWithNewId(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(id: null, entityTypeId: 99));

        $fieldDefs = new InMemoryFieldDefRepository([
            new FieldDef(entityTypeId: 99, fieldKey: 'count', dataType: 'datetime', id: 1),
        ]);

        $datetimeFields = new InMemoryDateTimeFieldRepository([]);
        $useCase = new CreateDateTimeFieldUseCase($datetimeFields, $entities, $fieldDefs);

        $output = $useCase->execute(new CreateDateTimeFieldInput(entityId: $entityId, fieldKey: 'count', value: '2026-05-24T12:00:00+00:00'));

        self::assertSame(1, $output->id);
        self::assertSame($entityId, $output->entityId);
        self::assertSame('count', $output->fieldKey);
        self::assertSame('2026-05-24T12:00:00+00:00', $output->value);
    }

    public function testAssignsSequentialIds(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(id: null, entityTypeId: 1));

        $fieldDefs = new InMemoryFieldDefRepository([
            new FieldDef(entityTypeId: 1, fieldKey: 'a', dataType: 'datetime', id: 1),
            new FieldDef(entityTypeId: 1, fieldKey: 'b', dataType: 'datetime', id: 2),
        ]);

        $datetimeFields = new InMemoryDateTimeFieldRepository([]);
        $useCase = new CreateDateTimeFieldUseCase($datetimeFields, $entities, $fieldDefs);

        $first = $useCase->execute(new CreateDateTimeFieldInput(entityId: $entityId, fieldKey: 'a', value: '2026-05-24T12:00:00+00:00'));
        $second = $useCase->execute(new CreateDateTimeFieldInput(entityId: $entityId, fieldKey: 'b', value: '2026-05-25T12:00:00+00:00'));

        self::assertSame(1, $first->id);
        self::assertSame(2, $second->id);
    }

    public function testThrowsFieldKeyNotRegisteredExceptionWhenFieldDefAbsent(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(id: null, entityTypeId: 1));

        $fieldDefs = new InMemoryFieldDefRepository([]);
        $datetimeFields = new InMemoryDateTimeFieldRepository([]);
        $useCase = new CreateDateTimeFieldUseCase($datetimeFields, $entities, $fieldDefs);

        $this->expectException(FieldKeyNotRegisteredException::class);

        $useCase->execute(new CreateDateTimeFieldInput(entityId: $entityId, fieldKey: 'count', value: '2026-05-24T12:00:00+00:00'));
    }

    public function testThrowsFieldTypeMismatchExceptionWhenDataTypeIsNotDateTime(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(id: null, entityTypeId: 1));

        $fieldDefs = new InMemoryFieldDefRepository([
            new FieldDef(entityTypeId: 1, fieldKey: 'title', dataType: 'text', id: 1),
        ]);

        $datetimeFields = new InMemoryDateTimeFieldRepository([]);
        $useCase = new CreateDateTimeFieldUseCase($datetimeFields, $entities, $fieldDefs);

        $this->expectException(FieldTypeMismatchException::class);

        $useCase->execute(new CreateDateTimeFieldInput(entityId: $entityId, fieldKey: 'title', value: '2026-05-24T12:00:00+00:00'));
    }
}

final class UpdateDateTimeFieldUseCaseTest extends TestCase
{
    public function testThrowsFieldKeyNotRegisteredExceptionWhenUpdatedKeyIsUnregistered(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(id: null, entityTypeId: 1));

        $fieldDefs = new InMemoryFieldDefRepository([
            new FieldDef(entityTypeId: 1, fieldKey: 'count', dataType: 'datetime', id: 1),
        ]);

        $datetimeFields = new InMemoryDateTimeFieldRepository([]);
        $datetimeFields->save(new DateTimeField(entityId: $entityId, fieldKey: 'count', value: '2026-05-24T12:00:00+00:00', id: null));

        $useCase = new UpdateDateTimeFieldUseCase($datetimeFields, $entities, $fieldDefs);

        $this->expectException(FieldKeyNotRegisteredException::class);

        $useCase->execute(new UpdateDateTimeFieldInput(id: 1, fieldKey: 'body', value: '2026-05-25T12:00:00+00:00'));
    }
}
