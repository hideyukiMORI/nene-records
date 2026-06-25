<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Organization;

use Nene2\Http\RequestScopedHolder;
use NeNeRecords\Organization\Organization;
use NeNeRecords\Organization\OrganizationIterator;
use PHPUnit\Framework\TestCase;

final class OrganizationIteratorTest extends TestCase
{
    public function testRunsWorkPerActiveOrgWithHolderScoped(): void
    {
        $repo = new InMemoryOrganizationRepository();
        $a = $repo->save(new Organization('A', 'a', 'free', true));
        $b = $repo->save(new Organization('B', 'b', 'free', true));
        $repo->save(new Organization('Off', 'off', 'free', false)); // inactive → skipped

        /** @var RequestScopedHolder<int> $holder */
        $holder = new RequestScopedHolder();
        $iterator = new OrganizationIterator($repo, $holder);

        $seen = [];
        $iterator->forEachActive(function (int $orgId) use (&$seen, $holder): void {
            // The holder is scoped to the current org during the callback.
            $seen[] = [$orgId, $holder->get()];
        });

        self::assertSame([[$a, $a], [$b, $b]], $seen);
    }

    public function testNoActiveOrgsRunsNothing(): void
    {
        $repo = new InMemoryOrganizationRepository();
        $repo->save(new Organization('Off', 'off', 'free', false));

        /** @var RequestScopedHolder<int> $holder */
        $holder = new RequestScopedHolder();

        $count = 0;
        (new OrganizationIterator($repo, $holder))->forEachActive(function () use (&$count): void {
            $count++;
        });

        self::assertSame(0, $count);
    }
}
