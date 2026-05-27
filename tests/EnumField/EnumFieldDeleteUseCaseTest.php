<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\EnumField;

use NeNeRecords\EnumField\DeleteEnumFieldByIdInput;
use NeNeRecords\EnumField\DeleteEnumFieldUseCase;
use NeNeRecords\EnumField\EnumField;
use NeNeRecords\EnumField\EnumFieldNotFoundException;
use PHPUnit\Framework\TestCase;

final class EnumFieldDeleteUseCaseTest extends TestCase
{
    public function testDeleteEnumFieldRemovesIt(): void
    {
        $enumFields = new InMemoryEnumFieldRepository([
            new EnumField(entityId: 1, fieldKey: 'status', value: 'active', id: 1),
        ]);
        $useCase = new DeleteEnumFieldUseCase($enumFields);

        $useCase->execute(new DeleteEnumFieldByIdInput(id: 1));

        self::assertNull($enumFields->findById(1));
    }

    public function testDeleteEnumFieldThrowsNotFoundWhenIdDoesNotExist(): void
    {
        $enumFields = new InMemoryEnumFieldRepository([]);
        $useCase = new DeleteEnumFieldUseCase($enumFields);

        $this->expectException(EnumFieldNotFoundException::class);

        $useCase->execute(new DeleteEnumFieldByIdInput(id: 99));
    }
}
