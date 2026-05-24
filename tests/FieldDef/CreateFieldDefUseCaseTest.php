<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\FieldDef;

use NeNeRecords\EntityType\EntityType;
use NeNeRecords\EntityType\EntityTypeNotFoundException;
use NeNeRecords\FieldDef\CreateFieldDefInput;
use NeNeRecords\FieldDef\CreateFieldDefUseCase;
use NeNeRecords\FieldDef\FieldDef;
use NeNeRecords\FieldDef\FieldDefConflictException;
use NeNeRecords\Tests\EntityType\InMemoryEntityTypeRepository;
use PHPUnit\Framework\TestCase;

final class CreateFieldDefUseCaseTest extends TestCase
{
    public function testReturnsOutputWithNewId(): void
    {
        $entityTypes = new InMemoryEntityTypeRepository([]);
        $entityTypes->save(new EntityType(name: 'Article', slug: 'article'));

        $fieldDefs = new InMemoryFieldDefRepository([]);
        $useCase = new CreateFieldDefUseCase($fieldDefs, $entityTypes);

        $output = $useCase->execute(new CreateFieldDefInput(
            entityTypeId: 1,
            fieldKey: 'title',
            dataType: 'text',
        ));

        self::assertSame(1, $output->id);
        self::assertSame(1, $output->entityTypeId);
        self::assertSame('title', $output->fieldKey);
        self::assertSame('text', $output->dataType);
    }

    public function testThrowsEntityTypeNotFoundExceptionWhenEntityTypeAbsent(): void
    {
        $entityTypes = new InMemoryEntityTypeRepository([]);
        $fieldDefs = new InMemoryFieldDefRepository([]);
        $useCase = new CreateFieldDefUseCase($fieldDefs, $entityTypes);

        $this->expectException(EntityTypeNotFoundException::class);

        $useCase->execute(new CreateFieldDefInput(entityTypeId: 99, fieldKey: 'title', dataType: 'text'));
    }

    public function testThrowsFieldDefConflictExceptionOnDuplicateKey(): void
    {
        $entityTypes = new InMemoryEntityTypeRepository([]);
        $entityTypes->save(new EntityType(name: 'Article', slug: 'article'));

        $fieldDefs = new InMemoryFieldDefRepository([
            new FieldDef(entityTypeId: 1, fieldKey: 'title', dataType: 'text', id: 1),
        ]);
        $useCase = new CreateFieldDefUseCase($fieldDefs, $entityTypes);

        $this->expectException(FieldDefConflictException::class);

        $useCase->execute(new CreateFieldDefInput(entityTypeId: 1, fieldKey: 'title', dataType: 'text'));
    }
}
