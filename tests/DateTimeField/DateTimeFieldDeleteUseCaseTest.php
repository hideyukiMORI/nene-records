<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\DateTimeField;

use NeNeRecords\DateTimeField\DateTimeField;
use NeNeRecords\DateTimeField\DateTimeFieldNotFoundException;
use NeNeRecords\DateTimeField\DeleteDateTimeFieldByIdInput;
use NeNeRecords\DateTimeField\DeleteDateTimeFieldUseCase;
use PHPUnit\Framework\TestCase;

final class DateTimeFieldDeleteUseCaseTest extends TestCase
{
    public function testDeleteDateTimeFieldRemovesIt(): void
    {
        $dateTimeFields = new InMemoryDateTimeFieldRepository([
            new DateTimeField(entityId: 1, fieldKey: 'publishedAt', value: '2026-01-01T00:00:00+00:00', id: 1),
        ]);
        $useCase = new DeleteDateTimeFieldUseCase($dateTimeFields);

        $useCase->execute(new DeleteDateTimeFieldByIdInput(id: 1));

        self::assertNull($dateTimeFields->findById(1));
    }

    public function testDeleteDateTimeFieldThrowsNotFoundWhenIdDoesNotExist(): void
    {
        $dateTimeFields = new InMemoryDateTimeFieldRepository([]);
        $useCase = new DeleteDateTimeFieldUseCase($dateTimeFields);

        $this->expectException(DateTimeFieldNotFoundException::class);

        $useCase->execute(new DeleteDateTimeFieldByIdInput(id: 99));
    }
}
