<?php

declare(strict_types=1);

namespace NeNeRecords\Signup;

/**
 * Validates a requested tenant slug for public self-serve signup: it becomes a
 * subdomain (`{slug}.nene-records.com`), so it must be a DNS-safe label and must
 * not collide with infrastructure / app hostnames or routes.
 */
final class ReservedSlugs
{
    /** 3–30 chars, lowercase alphanumeric + internal hyphens, no leading/trailing hyphen. */
    private const FORMAT = '/^[a-z0-9](?:[a-z0-9-]{1,28}[a-z0-9])$/';

    /**
     * Hostnames / route prefixes that must never be a tenant: mail + infra labels,
     * the apex's own global surfaces, and reserved app paths.
     *
     * @var list<string>
     */
    private const RESERVED = [
        'www', 'api', 'app', 'admin', 'superadmin', 'login', 'logout', 'signup', 'register',
        'mail', 'email', 'smtp', 'imap', 'pop', 'pop3', 'ftp', 'sftp', 'ssh', 'webmail',
        'ns', 'ns1', 'ns2', 'dns', 'mx', 'cdn', 'static', 'assets', 'public', 'internal',
        'media', 'files', 'img', 'images', 'js', 'css', 'fonts',
        'status', 'health', 'metrics', 'dashboard', 'account', 'accounts', 'billing',
        'help', 'support', 'docs', 'doc', 'blog', 'news', 'about', 'contact', 'legal',
        'test', 'tests', 'demo', 'example', 'staging', 'stage', 'dev', 'beta', 'sandbox',
        'root', 'system', 'sys', 'config', 'settings',
        'noreply', 'no-reply', 'webmaster', 'postmaster', 'hostmaster', 'abuse', 'security',
        'nene', 'records', 'nene-records', 'suite',
    ];

    private function __construct()
    {
    }

    public static function isValidFormat(string $slug): bool
    {
        return preg_match(self::FORMAT, $slug) === 1;
    }

    public static function isReserved(string $slug): bool
    {
        return in_array($slug, self::RESERVED, true);
    }

    /** True when the slug is a usable, non-reserved tenant label. */
    public static function isAvailable(string $slug): bool
    {
        return self::isValidFormat($slug) && !self::isReserved($slug);
    }
}
