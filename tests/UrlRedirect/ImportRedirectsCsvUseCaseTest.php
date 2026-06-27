<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\UrlRedirect;

use NeNeRecords\UrlRedirect\ImportRedirectsCsvUseCase;
use PHPUnit\Framework\TestCase;

final class ImportRedirectsCsvUseCaseTest extends TestCase
{
    public function testImportsValidRowsAsUpserts(): void
    {
        $repo = new InMemoryUrlRedirectRepository();
        $output = (new ImportRedirectsCsvUseCase($repo))->execute(
            "/old-page,/company/about\n/blog/2020/post,/articles/post\n",
            false,
        );

        self::assertFalse($output->dryRun);
        self::assertSame(2, $output->totalRows);
        self::assertSame(2, $output->validRows);
        self::assertSame(2, $output->importedRows);
        self::assertSame(0, $output->skippedRows);
        self::assertSame(
            ['/old-page' => '/company/about', '/blog/2020/post' => '/articles/post'],
            $repo->all(),
        );
    }

    public function testDryRunPreviewsWithoutWriting(): void
    {
        $repo = new InMemoryUrlRedirectRepository();
        $output = (new ImportRedirectsCsvUseCase($repo))->execute("/a,/b\n", true);

        self::assertTrue($output->dryRun);
        self::assertSame(1, $output->validRows);
        self::assertSame(0, $output->importedRows);
        self::assertSame([['source' => '/a', 'target' => '/b']], $output->samples);
        self::assertSame([], $repo->all());
    }

    public function testSkipsAHeaderRow(): void
    {
        $repo = new InMemoryUrlRedirectRepository();
        $output = (new ImportRedirectsCsvUseCase($repo))->execute("source,target\n/x,/y\n", false);

        self::assertSame(1, $output->validRows);
        self::assertSame(['/x' => '/y'], $repo->all());
    }

    public function testNormalizesFullUrlsTrailingSlashesAndMissingLeadingSlash(): void
    {
        $repo = new InMemoryUrlRedirectRepository();
        $output = (new ImportRedirectsCsvUseCase($repo))->execute(
            "https://old.example/about/,company/about\n",
            false,
        );

        self::assertSame(1, $output->importedRows);
        self::assertSame(['/about' => '/company/about'], $repo->all());
    }

    public function testReportsMissingColumnIdenticalAndDuplicateRows(): void
    {
        $repo = new InMemoryUrlRedirectRepository();
        $csv = implode("\n", [
            '/only-one-column', // line 1: no target → skipped
            '/same,/same',      // line 2: identical → skipped
            '/dup,/first',      // line 3: valid
            '/dup,/second',     // line 4: duplicate source → skipped
            '/good,/target',    // line 5: valid
        ]) . "\n";

        $output = (new ImportRedirectsCsvUseCase($repo))->execute($csv, false);

        self::assertSame(2, $output->validRows);
        self::assertSame(2, $output->importedRows);
        self::assertSame(3, $output->skippedRows);
        self::assertSame(5, $output->totalRows);
        self::assertSame(['/dup' => '/first', '/good' => '/target'], $repo->all());
        self::assertSame([1, 2, 4], array_map(static fn (array $e): int => $e['line'], $output->errors));
    }
}
