<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\EntityRelation;

use NeNeRecords\Entity\Entity;
use NeNeRecords\Entity\EntityNotFoundException;
use NeNeRecords\EntityRelation\AttachEntityRelationInput;
use NeNeRecords\EntityRelation\AttachEntityRelationUseCase;
use NeNeRecords\EntityRelation\DetachEntityRelationInput;
use NeNeRecords\EntityRelation\DetachEntityRelationUseCase;
use NeNeRecords\EntityRelation\RelationAlreadyAttachedException;
use NeNeRecords\EntityRelation\RelationNotAttachedException;
use NeNeRecords\EntityRelation\RelationTargetTypeMismatchException;
use NeNeRecords\FieldDef\FieldDef;
use NeNeRecords\FieldDef\FieldKeyNotRegisteredException;
use NeNeRecords\FieldDef\FieldTypeMismatchException;
use NeNeRecords\Tests\Entity\InMemoryEntityRepository;
use NeNeRecords\Tests\FieldDef\InMemoryFieldDefRepository;
use PHPUnit\Framework\TestCase;

final class EntityRelationUseCaseTest extends TestCase
{
    // AttachEntityRelationUseCase tests

    public function testAttachEntityRelationWithCardinalityMany(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $sourceId = $entities->save(new Entity(id: null, entityTypeId: 1));
        $targetId = $entities->save(new Entity(id: null, entityTypeId: 2));

        $fieldDefs = new InMemoryFieldDefRepository([
            new FieldDef(
                entityTypeId: 1,
                fieldKey: 'related',
                dataType: 'relation',
                id: 1,
                targetEntityTypeId: 2,
                cardinality: 'many',
            ),
        ]);

        $entityRelations = new InMemoryEntityRelationRepository();
        $useCase = new AttachEntityRelationUseCase($entities, $fieldDefs, $entityRelations);

        $output = $useCase->execute(new AttachEntityRelationInput(
            entityId: $sourceId,
            fieldKey: 'related',
            targetEntityId: $targetId,
        ));

        self::assertSame('related', $output->fieldKey);
        self::assertSame($targetId, $output->targetEntityId);
        self::assertTrue($entityRelations->isAttached($sourceId, $targetId, 'related'));
    }

    public function testAttachEntityRelationWithCardinalityOneReplacesExisting(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $sourceId = $entities->save(new Entity(id: null, entityTypeId: 1));
        $firstTargetId = $entities->save(new Entity(id: null, entityTypeId: 2));
        $secondTargetId = $entities->save(new Entity(id: null, entityTypeId: 2));

        $fieldDefs = new InMemoryFieldDefRepository([
            new FieldDef(
                entityTypeId: 1,
                fieldKey: 'parent',
                dataType: 'relation',
                id: 1,
                targetEntityTypeId: 2,
                cardinality: 'one',
            ),
        ]);

        $entityRelations = new InMemoryEntityRelationRepository();
        $entityRelations->attach($sourceId, $firstTargetId, 'parent');

        $useCase = new AttachEntityRelationUseCase($entities, $fieldDefs, $entityRelations);

        $output = $useCase->execute(new AttachEntityRelationInput(
            entityId: $sourceId,
            fieldKey: 'parent',
            targetEntityId: $secondTargetId,
        ));

        self::assertSame('parent', $output->fieldKey);
        self::assertSame($secondTargetId, $output->targetEntityId);
        self::assertFalse($entityRelations->isAttached($sourceId, $firstTargetId, 'parent'));
        self::assertTrue($entityRelations->isAttached($sourceId, $secondTargetId, 'parent'));
    }

    public function testAttachEntityRelationThrowsEntityNotFoundExceptionWhenSourceEntityMissing(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $fieldDefs = new InMemoryFieldDefRepository([]);
        $entityRelations = new InMemoryEntityRelationRepository();
        $useCase = new AttachEntityRelationUseCase($entities, $fieldDefs, $entityRelations);

        $this->expectException(EntityNotFoundException::class);

        $useCase->execute(new AttachEntityRelationInput(
            entityId: 999,
            fieldKey: 'related',
            targetEntityId: 1,
        ));
    }

    public function testAttachEntityRelationThrowsFieldKeyNotRegisteredExceptionWhenFieldNotRegistered(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $sourceId = $entities->save(new Entity(id: null, entityTypeId: 1));

        $fieldDefs = new InMemoryFieldDefRepository([]);
        $entityRelations = new InMemoryEntityRelationRepository();
        $useCase = new AttachEntityRelationUseCase($entities, $fieldDefs, $entityRelations);

        $this->expectException(FieldKeyNotRegisteredException::class);

        $useCase->execute(new AttachEntityRelationInput(
            entityId: $sourceId,
            fieldKey: 'missing',
            targetEntityId: 1,
        ));
    }

    public function testAttachEntityRelationThrowsFieldTypeMismatchExceptionWhenNotRelationType(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $sourceId = $entities->save(new Entity(id: null, entityTypeId: 1));

        $fieldDefs = new InMemoryFieldDefRepository([
            new FieldDef(entityTypeId: 1, fieldKey: 'title', dataType: 'text', id: 1),
        ]);

        $entityRelations = new InMemoryEntityRelationRepository();
        $useCase = new AttachEntityRelationUseCase($entities, $fieldDefs, $entityRelations);

        $this->expectException(FieldTypeMismatchException::class);

        $useCase->execute(new AttachEntityRelationInput(
            entityId: $sourceId,
            fieldKey: 'title',
            targetEntityId: 1,
        ));
    }

    public function testAttachEntityRelationThrowsRelationTargetTypeMismatchExceptionWhenTargetTypeDoesNotMatch(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $sourceId = $entities->save(new Entity(id: null, entityTypeId: 1));
        $targetId = $entities->save(new Entity(id: null, entityTypeId: 3));

        $fieldDefs = new InMemoryFieldDefRepository([
            new FieldDef(
                entityTypeId: 1,
                fieldKey: 'related',
                dataType: 'relation',
                id: 1,
                targetEntityTypeId: 2,
                cardinality: 'many',
            ),
        ]);

        $entityRelations = new InMemoryEntityRelationRepository();
        $useCase = new AttachEntityRelationUseCase($entities, $fieldDefs, $entityRelations);

        $this->expectException(RelationTargetTypeMismatchException::class);

        $useCase->execute(new AttachEntityRelationInput(
            entityId: $sourceId,
            fieldKey: 'related',
            targetEntityId: $targetId,
        ));
    }

    public function testAttachEntityRelationThrowsRelationAlreadyAttachedExceptionWhenAlreadyAttached(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $sourceId = $entities->save(new Entity(id: null, entityTypeId: 1));
        $targetId = $entities->save(new Entity(id: null, entityTypeId: 2));

        $fieldDefs = new InMemoryFieldDefRepository([
            new FieldDef(
                entityTypeId: 1,
                fieldKey: 'related',
                dataType: 'relation',
                id: 1,
                targetEntityTypeId: 2,
                cardinality: 'many',
            ),
        ]);

        $entityRelations = new InMemoryEntityRelationRepository();
        $entityRelations->attach($sourceId, $targetId, 'related');

        $useCase = new AttachEntityRelationUseCase($entities, $fieldDefs, $entityRelations);

        $this->expectException(RelationAlreadyAttachedException::class);

        $useCase->execute(new AttachEntityRelationInput(
            entityId: $sourceId,
            fieldKey: 'related',
            targetEntityId: $targetId,
        ));
    }

    // DetachEntityRelationUseCase tests

    public function testDetachEntityRelationDetachesRelation(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $sourceId = $entities->save(new Entity(id: null, entityTypeId: 1));
        $targetId = $entities->save(new Entity(id: null, entityTypeId: 2));

        $entityRelations = new InMemoryEntityRelationRepository();
        $entityRelations->attach($sourceId, $targetId, 'related');

        $useCase = new DetachEntityRelationUseCase($entities, $entityRelations);

        $useCase->execute(new DetachEntityRelationInput(
            entityId: $sourceId,
            fieldKey: 'related',
            targetEntityId: $targetId,
        ));

        self::assertFalse($entityRelations->isAttached($sourceId, $targetId, 'related'));
    }

    public function testDetachEntityRelationThrowsEntityNotFoundExceptionWhenEntityMissing(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityRelations = new InMemoryEntityRelationRepository();
        $useCase = new DetachEntityRelationUseCase($entities, $entityRelations);

        $this->expectException(EntityNotFoundException::class);

        $useCase->execute(new DetachEntityRelationInput(
            entityId: 999,
            fieldKey: 'related',
            targetEntityId: 1,
        ));
    }

    public function testDetachEntityRelationThrowsRelationNotAttachedExceptionWhenNotAttached(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $sourceId = $entities->save(new Entity(id: null, entityTypeId: 1));

        $entityRelations = new InMemoryEntityRelationRepository();
        $useCase = new DetachEntityRelationUseCase($entities, $entityRelations);

        $this->expectException(RelationNotAttachedException::class);

        $useCase->execute(new DetachEntityRelationInput(
            entityId: $sourceId,
            fieldKey: 'related',
            targetEntityId: 99,
        ));
    }
}
