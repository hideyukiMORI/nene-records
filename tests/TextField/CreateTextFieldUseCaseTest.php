<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\TextField;

use NeNeRecords\Entity\Entity;
use NeNeRecords\FieldDef\FieldDef;
use NeNeRecords\FieldDef\FieldKeyNotRegisteredException;
use NeNeRecords\FieldDef\FieldTypeMismatchException;
use NeNeRecords\Tests\Entity\InMemoryEntityRepository;
use NeNeRecords\Tests\FieldDef\InMemoryFieldDefRepository;
use NeNeRecords\TextField\CreateTextFieldInput;
use NeNeRecords\TextField\CreateTextFieldUseCase;
use NeNeRecords\TextField\TextField;
use NeNeRecords\TextField\UpdateTextFieldInput;
use NeNeRecords\TextField\UpdateTextFieldUseCase;
use PHPUnit\Framework\TestCase;

final class CreateTextFieldUseCaseTest extends TestCase
{
    public function testReturnsOutputWithNewId(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(id: null, entityTypeId: 99));

        $fieldDefs = new InMemoryFieldDefRepository([
            new FieldDef(entityTypeId: 99, fieldKey: 'title', dataType: 'text', id: 1),
        ]);

        $textFields = new InMemoryTextFieldRepository([]);
        $useCase = new CreateTextFieldUseCase($textFields, $entities, $fieldDefs);

        $output = $useCase->execute(new CreateTextFieldInput(entityId: $entityId, fieldKey: 'title', value: 'Hello'));

        self::assertSame(1, $output->id);
        self::assertSame($entityId, $output->entityId);
        self::assertSame('title', $output->fieldKey);
        self::assertSame('Hello', $output->value);
    }

    public function testAssignsSequentialIds(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(id: null, entityTypeId: 1));

        $fieldDefs = new InMemoryFieldDefRepository([
            new FieldDef(entityTypeId: 1, fieldKey: 'a', dataType: 'text', id: 1),
            new FieldDef(entityTypeId: 1, fieldKey: 'b', dataType: 'text', id: 2),
        ]);

        $textFields = new InMemoryTextFieldRepository([]);
        $useCase = new CreateTextFieldUseCase($textFields, $entities, $fieldDefs);

        $first = $useCase->execute(new CreateTextFieldInput(entityId: $entityId, fieldKey: 'a', value: '1'));
        $second = $useCase->execute(new CreateTextFieldInput(entityId: $entityId, fieldKey: 'b', value: '2'));

        self::assertSame(1, $first->id);
        self::assertSame(2, $second->id);
    }

    public function testThrowsFieldKeyNotRegisteredExceptionWhenFieldDefAbsent(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(id: null, entityTypeId: 1));

        $fieldDefs = new InMemoryFieldDefRepository([]);
        $textFields = new InMemoryTextFieldRepository([]);
        $useCase = new CreateTextFieldUseCase($textFields, $entities, $fieldDefs);

        $this->expectException(FieldKeyNotRegisteredException::class);

        $useCase->execute(new CreateTextFieldInput(entityId: $entityId, fieldKey: 'title', value: 'Hello'));
    }

    public function testAcceptsTextBackedDataTypesIncludingHtml(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(id: null, entityTypeId: 1));

        $fieldDefs = new InMemoryFieldDefRepository([
            new FieldDef(entityTypeId: 1, fieldKey: 'block', dataType: 'html', id: 1),
            new FieldDef(entityTypeId: 1, fieldKey: 'body', dataType: 'markdown', id: 2),
        ]);
        $textFields = new InMemoryTextFieldRepository([]);
        $useCase = new CreateTextFieldUseCase($textFields, $entities, $fieldDefs);

        $html = $useCase->execute(new CreateTextFieldInput(entityId: $entityId, fieldKey: 'block', value: '<p>hi</p>'));
        $md = $useCase->execute(new CreateTextFieldInput(entityId: $entityId, fieldKey: 'body', value: '# Hi'));

        self::assertSame('<p>hi</p>', $html->value);
        self::assertSame('# Hi', $md->value);
    }

    public function testThrowsFieldTypeMismatchExceptionWhenDataTypeIsNotText(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(id: null, entityTypeId: 1));

        $fieldDefs = new InMemoryFieldDefRepository([
            new FieldDef(entityTypeId: 1, fieldKey: 'count', dataType: 'int', id: 1),
        ]);

        $textFields = new InMemoryTextFieldRepository([]);
        $useCase = new CreateTextFieldUseCase($textFields, $entities, $fieldDefs);

        $this->expectException(FieldTypeMismatchException::class);

        $useCase->execute(new CreateTextFieldInput(entityId: $entityId, fieldKey: 'count', value: '1'));
    }
}

final class UpdateTextFieldUseCaseTest extends TestCase
{
    public function testThrowsFieldKeyNotRegisteredExceptionWhenUpdatedKeyIsUnregistered(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(id: null, entityTypeId: 1));

        $fieldDefs = new InMemoryFieldDefRepository([
            new FieldDef(entityTypeId: 1, fieldKey: 'title', dataType: 'text', id: 1),
        ]);

        $textFields = new InMemoryTextFieldRepository([]);
        $textFields->save(new TextField(entityId: $entityId, fieldKey: 'title', value: 'Old', id: null));

        $useCase = new UpdateTextFieldUseCase($textFields, $entities, $fieldDefs);

        $this->expectException(FieldKeyNotRegisteredException::class);

        $useCase->execute(new UpdateTextFieldInput(id: 1, fieldKey: 'body', value: 'New'));
    }
}
