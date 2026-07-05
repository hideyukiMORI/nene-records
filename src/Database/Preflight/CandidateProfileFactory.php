<?php

declare(strict_types=1);

namespace NeNeRecords\Database\Preflight;

use Nene2\Config\ConfigException;
use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\Preflight\CandidateProfile;

/**
 * Builds candidate database profiles for machine database preflight (#648) from the
 * application's own environment — never from the request body — so a caller of
 * `POST /machine/database/preflight` can only reference candidates the operator has
 * allowlisted (this is what keeps the endpoint SSRF-safe and credentials off the wire).
 *
 * Each candidate is described by `DB_CANDIDATE_<KEY>_*` variables that mirror the primary
 * `DB_*` keys; `<KEY>` becomes the candidate id the caller references. A least-privilege
 * (SELECT-only) credential is recommended; the framework additionally enforces a
 * read-only transaction during inspection.
 *
 *   DB_CANDIDATE_RESTORE_ADAPTER=mysql
 *   DB_CANDIDATE_RESTORE_HOST=db-restore.internal
 *   DB_CANDIDATE_RESTORE_PORT=3306
 *   DB_CANDIDATE_RESTORE_NAME=nene_records
 *   DB_CANDIDATE_RESTORE_USER=preflight_ro
 *   DB_CANDIDATE_RESTORE_PASSWORD=...
 *   DB_CANDIDATE_RESTORE_MULTITENANT=true   # optional, default false
 *
 * A malformed candidate (missing required fields) is skipped rather than fatal, so a
 * misconfigured optional candidate never takes the whole application down — the caller
 * simply gets a 422 (unknown candidate) for that id.
 */
final class CandidateProfileFactory
{
    private const PREFIX = 'DB_CANDIDATE_';

    /** Recognised trailing field names; the last `_`-segment of a variable name. */
    private const FIELDS = ['URL', 'ADAPTER', 'HOST', 'PORT', 'NAME', 'USER', 'PASSWORD', 'CHARSET', 'MULTITENANT'];

    /**
     * @param array<array-key, mixed> $env Typically `$_SERVER + $_ENV`.
     * @return array<string, CandidateProfile> Keyed by candidate id.
     */
    public static function fromEnv(array $env): array
    {
        /** @var array<string, array<string, string>> $grouped */
        $grouped = [];

        foreach ($env as $name => $value) {
            if (!is_string($name) || !is_string($value) || !str_starts_with($name, self::PREFIX)) {
                continue;
            }

            $rest = substr($name, strlen(self::PREFIX));
            $parts = explode('_', $rest);
            $field = array_pop($parts); // explode never yields an empty list, so this is a string
            $key = implode('_', $parts);

            if ($key === '' || !in_array($field, self::FIELDS, true)) {
                continue;
            }

            $grouped[$key][$field] = $value;
        }

        $profiles = [];
        foreach ($grouped as $key => $fields) {
            // A numeric candidate key (e.g. DB_CANDIDATE_2026_*) arrives as an int array
            // key, so normalise to string before use under strict_types.
            $id = (string) $key;
            if ($id === '') {
                continue;
            }

            $profile = self::build($id, $fields);
            if ($profile !== null) {
                $profiles[$id] = $profile;
            }
        }

        return $profiles;
    }

    /**
     * @param non-empty-string $key
     * @param array<string, string> $fields
     */
    private static function build(string $key, array $fields): ?CandidateProfile
    {
        $url = ($fields['URL'] ?? '') !== '' ? $fields['URL'] : null;
        $portRaw = $fields['PORT'] ?? '3306';
        $port = ctype_digit($portRaw) ? (int) $portRaw : 0;

        try {
            $config = new DatabaseConfig(
                url: $url,
                environment: 'candidate',
                adapter: ($fields['ADAPTER'] ?? '') !== '' ? $fields['ADAPTER'] : 'mysql',
                host: $fields['HOST'] ?? '',
                port: $port,
                name: $fields['NAME'] ?? '',
                user: $fields['USER'] ?? '',
                password: $fields['PASSWORD'] ?? '',
                charset: ($fields['CHARSET'] ?? '') !== '' ? $fields['CHARSET'] : 'utf8mb4',
            );
        } catch (ConfigException) {
            return null;
        }

        $multiTenant = filter_var($fields['MULTITENANT'] ?? 'false', FILTER_VALIDATE_BOOLEAN);

        return new CandidateProfile($key, new PdoConnectionFactory($config), $multiTenant);
    }
}
