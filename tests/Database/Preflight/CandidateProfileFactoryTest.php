<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Database\Preflight;

use Nene2\Database\PdoConnectionFactory;
use NeNeRecords\Database\Preflight\CandidateProfileFactory;
use PHPUnit\Framework\TestCase;

final class CandidateProfileFactoryTest extends TestCase
{
    public function testBuildsProfileFromEnvKeyedByCandidateId(): void
    {
        $profiles = CandidateProfileFactory::fromEnv([
            'DB_CANDIDATE_RESTORE_ADAPTER' => 'mysql',
            'DB_CANDIDATE_RESTORE_HOST' => 'db-restore.internal',
            'DB_CANDIDATE_RESTORE_PORT' => '3306',
            'DB_CANDIDATE_RESTORE_NAME' => 'nene_records',
            'DB_CANDIDATE_RESTORE_USER' => 'preflight_ro',
            'DB_CANDIDATE_RESTORE_PASSWORD' => 'secret',
            'DB_CANDIDATE_RESTORE_MULTITENANT' => 'true',
            'UNRELATED_ENV' => 'ignored',
        ]);

        self::assertArrayHasKey('RESTORE', $profiles);
        $profile = $profiles['RESTORE'];
        self::assertSame('RESTORE', $profile->id);
        self::assertInstanceOf(PdoConnectionFactory::class, $profile->connectionFactory);
        self::assertTrue($profile->multiTenant);
    }

    public function testCandidateIdKeepsUnderscores(): void
    {
        $profiles = CandidateProfileFactory::fromEnv([
            'DB_CANDIDATE_RESTORE_2026_HOST' => 'h',
            'DB_CANDIDATE_RESTORE_2026_NAME' => 'nene_records',
            'DB_CANDIDATE_RESTORE_2026_USER' => 'u',
        ]);

        self::assertArrayHasKey('RESTORE_2026', $profiles);
        self::assertSame('RESTORE_2026', $profiles['RESTORE_2026']->id);
    }

    public function testMultiTenantDefaultsFalse(): void
    {
        $profiles = CandidateProfileFactory::fromEnv([
            'DB_CANDIDATE_CLONE_HOST' => 'h',
            'DB_CANDIDATE_CLONE_NAME' => 'nene_records',
            'DB_CANDIDATE_CLONE_USER' => 'u',
        ]);

        self::assertFalse($profiles['CLONE']->multiTenant);
    }

    public function testSkipsMalformedCandidateInsteadOfFailing(): void
    {
        // HOST and NAME missing → DatabaseConfig rejects it → candidate is dropped,
        // and a valid sibling candidate still builds.
        $profiles = CandidateProfileFactory::fromEnv([
            'DB_CANDIDATE_BROKEN_USER' => 'u',
            'DB_CANDIDATE_GOOD_HOST' => 'h',
            'DB_CANDIDATE_GOOD_NAME' => 'nene_records',
            'DB_CANDIDATE_GOOD_USER' => 'u',
        ]);

        self::assertArrayNotHasKey('BROKEN', $profiles);
        self::assertArrayHasKey('GOOD', $profiles);
    }

    public function testIgnoresNonCandidateEnv(): void
    {
        self::assertSame([], CandidateProfileFactory::fromEnv([
            'DB_HOST' => 'h',
            'DB_CANDIDATE_' => 'no-key',       // empty candidate key
            'DB_CANDIDATE_ONLYKEY' => 'no-field', // no recognised field suffix
            'APP_NAME' => 'x',
        ]));
    }
}
