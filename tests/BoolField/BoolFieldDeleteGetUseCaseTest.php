<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\BoolField;

use NeNeRecords\BoolField\BoolField;
use NeNeRecords\BoolField\BoolFieldNotFoundException;
use NeNeRecords\BoolField\DeleteBoolFieldByIdInput;
use NeNeRecords\BoolField\DeleteBoolFieldUseCase;
use NeNeRecords\BoolField\GetBoolFieldByIdInput;
use NeNeRecords\BoolField\GetBoolFieldByIdUseCase;
use PHPUnit\Framework\TestCase;

final class BoolFieldDeleteGetUseCaseTest extends TestCase
{
    public function testDeleteBoolFieldRemovesIt(): void
    {
        $boolFields = new InMemoryBoolFieldRepository([
            new BoolField(entityId: 1, fieldKey: 'active', value: true, id: 1),
        ]);
        $useCase = new DeleteBoolFieldUseCase($boolFields);

        $useCase->execute(new DeleteBoolFieldByIdInput(id: 1));

        self::assertNull($boolFields->findById(1));
    }

    public function testDeleteBoolFieldThrowsNotFoundWhenIdDoesNotExist(): void
    {
        $boolFields = new InMemoryBoolFieldRepository([]);
        $useCase = new DeleteBoolFieldUseCase($boolFields);

        $this->expectException(BoolFieldNotFoundException::class);

        $useCase->execute(new DeleteBoolFieldByIdInput(id: 99));
    }

    public function testGetBoolFieldByIdReturnsCorrectOutput(): void
    {
        $boolFields = new InMemoryBoolFieldRepository([
            new BoolField(entityId: 5, fieldKey: 'featured', value: false, id: 1),
        ]);
        $useCase = new GetBoolFieldByIdUseCase($boolFields);

        $output = $useCase->execute(new GetBoolFieldByIdInput(id: 1));

        self::assertSame(1, $output->id);
        self::assertSame(5, $output->entityId);
        self::assertSame('featured', $output->fieldKey);
        self::assertSame(false, $output->value);
    }

    public function testGetBoolFieldByIdThrowsNotFoundWhenIdDoesNotExist(): void
    {
        $boolFields = new InMemoryBoolFieldRepository([]);
        $useCase = new GetBoolFieldByIdUseCase($boolFields);

        $this->expectException(BoolFieldNotFoundException::class);

        $useCase->execute(new GetBoolFieldByIdInput(id: 42));
    }
}
