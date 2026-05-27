<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\TextField;

use NeNeRecords\Entity\Entity;
use NeNeRecords\FieldDef\FieldDef;
use NeNeRecords\Tests\Entity\InMemoryEntityRepository;
use NeNeRecords\Tests\FieldDef\InMemoryFieldDefRepository;
use NeNeRecords\TextField\DeleteTextFieldByIdInput;
use NeNeRecords\TextField\DeleteTextFieldUseCase;
use NeNeRecords\TextField\TextField;
use NeNeRecords\TextField\TextFieldNotFoundException;
use NeNeRecords\TextField\UpdateTextFieldInput;
use NeNeRecords\TextField\UpdateTextFieldUseCase;
use PHPUnit\Framework\TestCase;

final class TextFieldDeleteUpdateUseCaseTest extends TestCase
{
    public function testDeleteTextFieldRemovesIt(): void
    {
        $textFields = new InMemoryTextFieldRepository([
            new TextField(entityId: 1, fieldKey: 'title', value: 'Hello', id: 1),
        ]);
        $useCase = new DeleteTextFieldUseCase($textFields);

        $useCase->execute(new DeleteTextFieldByIdInput(id: 1));

        self::assertNull($textFields->findById(1));
    }

    public function testDeleteTextFieldThrowsNotFoundWhenIdDoesNotExist(): void
    {
        $textFields = new InMemoryTextFieldRepository([]);
        $useCase = new DeleteTextFieldUseCase($textFields);

        $this->expectException(TextFieldNotFoundException::class);

        $useCase->execute(new DeleteTextFieldByIdInput(id: 99));
    }

    public function testUpdateTextFieldChangesValueAndFieldKey(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(id: null, entityTypeId: 10));

        $fieldDefs = new InMemoryFieldDefRepository([
            new FieldDef(entityTypeId: 10, fieldKey: 'title', dataType: 'text', id: 1),
            new FieldDef(entityTypeId: 10, fieldKey: 'body', dataType: 'text', id: 2),
        ]);

        $textFields = new InMemoryTextFieldRepository([
            new TextField(entityId: $entityId, fieldKey: 'title', value: 'Old title', id: 1),
        ]);

        $useCase = new UpdateTextFieldUseCase($textFields, $entities, $fieldDefs);

        $output = $useCase->execute(new UpdateTextFieldInput(id: 1, fieldKey: 'body', value: 'New body'));

        self::assertSame(1, $output->id);
        self::assertSame($entityId, $output->entityId);
        self::assertSame('body', $output->fieldKey);
        self::assertSame('New body', $output->value);
    }

    public function testUpdateTextFieldThrowsNotFoundWhenIdDoesNotExist(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $fieldDefs = new InMemoryFieldDefRepository([]);
        $textFields = new InMemoryTextFieldRepository([]);
        $useCase = new UpdateTextFieldUseCase($textFields, $entities, $fieldDefs);

        $this->expectException(TextFieldNotFoundException::class);

        $useCase->execute(new UpdateTextFieldInput(id: 999, fieldKey: 'title', value: 'value'));
    }
}
