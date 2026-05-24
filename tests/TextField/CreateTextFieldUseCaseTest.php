<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\TextField;

use NeNeRecords\Entity\Entity;
use NeNeRecords\Tests\Entity\InMemoryEntityRepository;
use NeNeRecords\TextField\CreateTextFieldInput;
use NeNeRecords\TextField\CreateTextFieldUseCase;
use PHPUnit\Framework\TestCase;

final class CreateTextFieldUseCaseTest extends TestCase
{
    public function testReturnsOutputWithNewId(): void
    {
        $entities = new InMemoryEntityRepository([]);
        $entityId = $entities->save(new Entity(id: null, entityTypeId: 99));

        $textFields = new InMemoryTextFieldRepository([]);
        $useCase = new CreateTextFieldUseCase($textFields, $entities);

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

        $textFields = new InMemoryTextFieldRepository([]);
        $useCase = new CreateTextFieldUseCase($textFields, $entities);

        $first = $useCase->execute(new CreateTextFieldInput(entityId: $entityId, fieldKey: 'a', value: '1'));
        $second = $useCase->execute(new CreateTextFieldInput(entityId: $entityId, fieldKey: 'b', value: '2'));

        self::assertSame(1, $first->id);
        self::assertSame(2, $second->id);
    }
}
