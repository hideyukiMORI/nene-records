<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Entity;

use NeNeRecords\Entity\CreateEntityInput;
use NeNeRecords\Entity\CreateEntityUseCase;
use NeNeRecords\EntityType\EntityType;
use NeNeRecords\Tests\EntityType\InMemoryEntityTypeRepository;
use PHPUnit\Framework\TestCase;

final class CreateEntityUseCaseTest extends TestCase
{
    public function testReturnsOutputWithNewId(): void
    {
        $entityTypes = new InMemoryEntityTypeRepository([]);
        $typeId = $entityTypes->save(new EntityType(name: 'Thing', slug: 'thing'));

        $entities = new InMemoryEntityRepository([]);
        $useCase = new CreateEntityUseCase($entities, $entityTypes);

        $output = $useCase->execute(new CreateEntityInput(entityTypeId: $typeId));

        self::assertSame(1, $output->id);
        self::assertSame($typeId, $output->entityTypeId);
        self::assertFalse($output->isDeleted);
        self::assertNull($output->deletedAtIso);
    }

    public function testAssignsSequentialIds(): void
    {
        $entityTypes = new InMemoryEntityTypeRepository([]);
        $typeId = $entityTypes->save(new EntityType(name: 'Thing', slug: 'thing'));

        $entities = new InMemoryEntityRepository([]);
        $useCase = new CreateEntityUseCase($entities, $entityTypes);

        $first = $useCase->execute(new CreateEntityInput(entityTypeId: $typeId));
        $second = $useCase->execute(new CreateEntityInput(entityTypeId: $typeId));

        self::assertSame(1, $first->id);
        self::assertSame(2, $second->id);
    }
}
