<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\BlocksField;

use Nene2\Validation\ValidationException;
use NeNeRecords\BlocksField\BlocksDocumentValidator;
use NeNeRecords\BlocksField\BlocksField;
use NeNeRecords\BlocksField\CreateBlocksFieldInput;
use NeNeRecords\BlocksField\CreateBlocksFieldUseCase;
use NeNeRecords\BlocksField\UpdateBlocksFieldInput;
use NeNeRecords\BlocksField\UpdateBlocksFieldUseCase;
use NeNeRecords\Entity\Entity;
use NeNeRecords\FieldDef\FieldDef;
use NeNeRecords\FieldDef\FieldKeyNotRegisteredException;
use NeNeRecords\FieldDef\FieldTypeMismatchException;
use NeNeRecords\Tests\Entity\InMemoryEntityRepository;
use NeNeRecords\Tests\FieldDef\InMemoryFieldDefRepository;
use PHPUnit\Framework\TestCase;

final class CreateBlocksFieldUseCaseTest extends TestCase
{
    private const VALID_DOC = '[{"id":"b1","type":"text","data":{"markdown":"Hello"}}]';

    public function testReturnsOutputWithNewId(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(id: null, entityTypeId: 99));

        $fieldDefs = new InMemoryFieldDefRepository([
            new FieldDef(entityTypeId: 99, fieldKey: 'body', dataType: 'blocks', id: 1),
        ]);

        $useCase = $this->useCase($entities, $fieldDefs);

        $output = $useCase->execute(new CreateBlocksFieldInput(entityId: $entityId, fieldKey: 'body', value: self::VALID_DOC));

        self::assertSame(1, $output->id);
        self::assertSame($entityId, $output->entityId);
        self::assertSame('body', $output->fieldKey);
        self::assertSame(self::VALID_DOC, $output->value);
    }

    public function testThrowsFieldKeyNotRegisteredExceptionWhenFieldDefAbsent(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(id: null, entityTypeId: 1));

        $useCase = $this->useCase($entities, new InMemoryFieldDefRepository([]));

        $this->expectException(FieldKeyNotRegisteredException::class);

        $useCase->execute(new CreateBlocksFieldInput(entityId: $entityId, fieldKey: 'body', value: self::VALID_DOC));
    }

    public function testThrowsFieldTypeMismatchExceptionWhenDataTypeIsNotBlocks(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(id: null, entityTypeId: 1));

        $fieldDefs = new InMemoryFieldDefRepository([
            new FieldDef(entityTypeId: 1, fieldKey: 'body', dataType: 'text', id: 1),
        ]);

        $useCase = $this->useCase($entities, $fieldDefs);

        $this->expectException(FieldTypeMismatchException::class);

        $useCase->execute(new CreateBlocksFieldInput(entityId: $entityId, fieldKey: 'body', value: self::VALID_DOC));
    }

    public function testRejectsInvalidBlocksDocument(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(id: null, entityTypeId: 1));

        $fieldDefs = new InMemoryFieldDefRepository([
            new FieldDef(entityTypeId: 1, fieldKey: 'body', dataType: 'blocks', id: 1),
        ]);

        $useCase = $this->useCase($entities, $fieldDefs);

        // The use case is the trust boundary: a registered blocks field still
        // rejects a malformed document.
        $this->expectException(ValidationException::class);

        $useCase->execute(new CreateBlocksFieldInput(
            entityId: $entityId,
            fieldKey: 'body',
            value: '[{"id":"b1","type":"spaceship","data":{}}]',
        ));
    }

    private function useCase(
        InMemoryEntityRepository $entities,
        InMemoryFieldDefRepository $fieldDefs,
    ): CreateBlocksFieldUseCase {
        return new CreateBlocksFieldUseCase(
            new InMemoryBlocksFieldRepository([]),
            $entities,
            $fieldDefs,
            new BlocksDocumentValidator(),
        );
    }
}

final class UpdateBlocksFieldUseCaseTest extends TestCase
{
    public function testThrowsFieldKeyNotRegisteredExceptionWhenUpdatedKeyIsUnregistered(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(id: null, entityTypeId: 1));

        $fieldDefs = new InMemoryFieldDefRepository([
            new FieldDef(entityTypeId: 1, fieldKey: 'body', dataType: 'blocks', id: 1),
        ]);

        $blocksFields = new InMemoryBlocksFieldRepository([]);
        $blocksFields->save(new BlocksField(entityId: $entityId, fieldKey: 'body', value: '[]', id: null));

        $useCase = new UpdateBlocksFieldUseCase($blocksFields, $entities, $fieldDefs, new BlocksDocumentValidator());

        $this->expectException(FieldKeyNotRegisteredException::class);

        $useCase->execute(new UpdateBlocksFieldInput(id: 1, fieldKey: 'missing', value: '[]'));
    }
}
