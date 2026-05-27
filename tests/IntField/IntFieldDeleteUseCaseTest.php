<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\IntField;

use NeNeRecords\IntField\DeleteIntFieldByIdInput;
use NeNeRecords\IntField\DeleteIntFieldUseCase;
use NeNeRecords\IntField\IntField;
use NeNeRecords\IntField\IntFieldNotFoundException;
use PHPUnit\Framework\TestCase;

final class IntFieldDeleteUseCaseTest extends TestCase
{
    public function testDeleteIntFieldRemovesIt(): void
    {
        $intFields = new InMemoryIntFieldRepository([
            new IntField(entityId: 1, fieldKey: 'score', value: 42, id: 1),
        ]);
        $useCase = new DeleteIntFieldUseCase($intFields);

        $useCase->execute(new DeleteIntFieldByIdInput(id: 1));

        self::assertNull($intFields->findById(1));
    }

    public function testDeleteIntFieldThrowsNotFoundWhenIdDoesNotExist(): void
    {
        $intFields = new InMemoryIntFieldRepository([]);
        $useCase = new DeleteIntFieldUseCase($intFields);

        $this->expectException(IntFieldNotFoundException::class);

        $useCase->execute(new DeleteIntFieldByIdInput(id: 99));
    }
}
