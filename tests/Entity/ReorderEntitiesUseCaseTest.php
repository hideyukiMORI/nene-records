<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Entity;

use NeNeRecords\Entity\Entity;
use NeNeRecords\Entity\EntityStatus;
use NeNeRecords\Entity\ReorderEntitiesInput;
use NeNeRecords\Entity\ReorderEntitiesUseCase;
use PHPUnit\Framework\TestCase;

final class ReorderEntitiesUseCaseTest extends TestCase
{
    public function testAssignsMenuOrderByPosition(): void
    {
        $entities = new InMemoryEntityRepository([
            new Entity(id: 1, entityTypeId: 1, status: EntityStatus::Published, menuOrder: 9),
            new Entity(id: 2, entityTypeId: 1, status: EntityStatus::Published, menuOrder: 9),
            new Entity(id: 3, entityTypeId: 1, status: EntityStatus::Published, menuOrder: 9),
        ]);
        $useCase = new ReorderEntitiesUseCase($entities);

        $output = $useCase->execute(new ReorderEntitiesInput([3, 1, 2]));

        self::assertSame(3, $output->reordered);
        self::assertSame(0, $entities->findById(3)?->menuOrder);
        self::assertSame(1, $entities->findById(1)?->menuOrder);
        self::assertSame(2, $entities->findById(2)?->menuOrder);
    }
}
