<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Setting;

use Nene2\Database\DatabaseTransactionManagerInterface;
use Nene2\Http\RequestScopedHolder;
use NeNeRecords\Entity\Entity;
use NeNeRecords\Entity\EntityStatus;
use NeNeRecords\PublicRecord\FrontPageSetting;
use NeNeRecords\Setting\SettingValueInvalidException;
use NeNeRecords\Setting\UpdateSettingInput;
use NeNeRecords\Setting\UpdateSettingUseCase;
use NeNeRecords\Tests\Entity\InMemoryEntityRepository;
use NeNeRecords\Tests\EntityType\InMemoryEntityTypeRepository;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Saving `front_page` may only ever pin an existing, published, non-deleted record in
 * the current org — or be cleared with '' (#701). Validation runs before the write, so
 * a rejected value never reaches the transaction and an accepted one always does.
 *
 * The fake transaction manager throws a sentinel: reaching it proves validation passed.
 */
final class UpdateSettingFrontPageValidationTest extends TestCase
{
    public function testAcceptsAnEmptyValue(): void
    {
        $this->expectException(ReachedTransactionException::class);
        $this->useCase([])->execute(new UpdateSettingInput('front_page', ''));
    }

    public function testAcceptsAPublishedRecordId(): void
    {
        $this->expectException(ReachedTransactionException::class);
        $this->useCase([$this->page(7, EntityStatus::Published)])->execute(new UpdateSettingInput('front_page', '7'));
    }

    public function testRejectsANonNumericValue(): void
    {
        $this->expectException(SettingValueInvalidException::class);
        $this->useCase([])->execute(new UpdateSettingInput('front_page', 'about'));
    }

    public function testRejectsAMissingRecord(): void
    {
        $this->expectException(SettingValueInvalidException::class);
        $this->useCase([])->execute(new UpdateSettingInput('front_page', '999'));
    }

    public function testRejectsADraftRecord(): void
    {
        $this->expectException(SettingValueInvalidException::class);
        $this->useCase([$this->page(3, EntityStatus::Draft)])->execute(new UpdateSettingInput('front_page', '3'));
    }

    /**
     * @param list<Entity> $entities
     */
    private function useCase(array $entities): UpdateSettingUseCase
    {
        $transactions = new class () implements DatabaseTransactionManagerInterface {
            public function transactional(callable $callback): never
            {
                // Only an accepted value gets here (validation throws earlier otherwise).
                throw new ReachedTransactionException();
            }
        };

        /** @var RequestScopedHolder<int> $orgId */
        $orgId = new RequestScopedHolder();
        $orgId->set(1);

        return new UpdateSettingUseCase($transactions, $orgId, new FrontPageSetting(
            new InMemorySettingRepository(),
            new InMemoryEntityRepository($entities),
            new InMemoryEntityTypeRepository(),
        ));
    }

    private function page(int $id, EntityStatus $status): Entity
    {
        return new Entity(id: $id, entityTypeId: 1, slug: 'p' . $id, status: $status);
    }
}

/** Sentinel thrown by the fake transaction manager once validation has passed. */
final class ReachedTransactionException extends RuntimeException
{
}
