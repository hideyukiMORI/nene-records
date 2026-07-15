<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Http;

use Nene2\Auth\TokenIssuerInterface;
use Nene2\Database\DatabaseQueryExecutorInterface;
use NeNeRecords\Http\RuntimeContainerFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * End-to-end proof that the opt-in X-Authorization fallback receiver (NENE2 #1558 /
 * ADR 0019) is wired into this product's runtime pipeline.
 *
 * Front-end fleet clients (`@hideyukimori/nene2-client` v1.1.0) mirror every bearer
 * token into `X-Authorization: Bearer <token>` so that shared hosting (HETEML-type
 * Tier A) — where an upstream proxy strips the standard `Authorization` header before
 * PHP sees it — can still authenticate. `RuntimeServiceProvider` enables the receiver
 * via `enableAuthorizationHeaderFallback: true`, so the framework's
 * AuthorizationHeaderFallbackMiddleware restores `Authorization` from the mirror
 * (only when `Authorization` is absent/empty) at the head of the auth stage, before
 * `AdminApiAuthMiddleware` (this product's own bearer/cookie auth middleware) runs.
 *
 * `GET /api/v1/organizations` is bearer-protected for all methods
 * ({@see \NeNeRecords\Auth\AdminApiAuthMiddleware::ADMIN_ONLY_PREFIXES}) and is one of
 * the `OrgResolverMiddleware` bypass prefixes
 * ({@see \NeNeRecords\Organization\Resolution\OrgResolverMiddleware::BYPASS_PREFIXES}),
 * so these assertions isolate the credential-restoration behaviour with no seeded
 * tenant — the same shape as nene-field's `/organizations` pick.
 *
 * `AdminApiAuthMiddleware` is the only middleware in this product's pipeline that ever
 * sets `WWW-Authenticate`, so its absence proves the request cleared the bearer-auth
 * stage — regardless of what a downstream stage (org/role authorization) does with it
 * afterwards. With minimal claims (`sub`, `exp`, no `role`), an authenticated request
 * to this superadmin-only route is rejected by `CapabilityMiddleware` with a plain 403
 * (no `WWW-Authenticate` — that middleware never sets it), which is why the "passes
 * authentication" cases below assert 403, not 200: the org list handler requires a
 * `role` claim that these tests intentionally omit, since only the transport-level
 * mirror behaviour is in scope here.
 *
 * The tests fail if the opt-in flag is removed from RuntimeServiceProvider: a
 * mirror-only request would then never restore `Authorization` and would be
 * rejected as `missing_token`.
 */
final class AuthorizationHeaderFallbackE2ETest extends TestCase
{
    private const PROTECTED_PATH = '/api/v1/organizations';

    private RequestHandlerInterface $app;
    private TokenIssuerInterface $issuer;

    protected function setUp(): void
    {
        parent::setUp();

        $container = (new RuntimeContainerFactory(dirname(__DIR__, 2)))->create();

        // The runtime boots against SQLite `:memory:` (phpunit.xml.dist). Every
        // request through this path touches `system_config` (tenant-resolution mode
        // lookup, RuntimeServiceProvider — unconditional) and `access_logs`
        // (AccessLogMiddleware — fail-open on error, but created here so the test
        // exercises the real path instead of masking failures behind its catch).
        // `rate_limits` (ThrottleMiddleware) sits after auth+capability and is out
        // of reach for every case below (401/403), but is created too so a future
        // capability change doesn't reintroduce a missing-table failure. Created
        // through the container's own DatabaseQueryExecutorInterface (a per-boot
        // singleton) so the same in-memory connection that RuntimeApplicationFactory's
        // services read from sees them — a fresh PDO connection to `:memory:` would
        // be a distinct, empty database.
        $query = $container->get(DatabaseQueryExecutorInterface::class);
        self::assertInstanceOf(DatabaseQueryExecutorInterface::class, $query);
        $query->execute('CREATE TABLE system_config (`key` TEXT PRIMARY KEY, `value` TEXT, updated_at TEXT)');
        $query->execute('CREATE TABLE rate_limits (key_hash TEXT PRIMARY KEY, count INTEGER, reset_at INTEGER)');
        $query->execute(
            'CREATE TABLE access_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                organization_id INTEGER,
                request_id TEXT,
                method TEXT,
                path TEXT,
                status_code INTEGER,
                duration_ms INTEGER,
                accessed_at TEXT,
                access_date TEXT
            )',
        );

        $app = $container->get(RequestHandlerInterface::class);
        self::assertInstanceOf(RequestHandlerInterface::class, $app);
        $this->app = $app;

        $issuer = $container->get(TokenIssuerInterface::class);
        self::assertInstanceOf(TokenIssuerInterface::class, $issuer);
        $this->issuer = $issuer;
    }

    /**
     * The mirror end-to-end proof: a valid bearer token supplied ONLY in the
     * `X-Authorization` header (no standard `Authorization`) is restored by the
     * fallback receiver and accepted by `AdminApiAuthMiddleware` — the request clears
     * the bearer-auth stage (no `WWW-Authenticate` challenge).
     */
    public function testValidTokenInMirrorOnlyPassesAuthentication(): void
    {
        $token = $this->issuer->issue(['sub' => 'admin-e2e', 'exp' => time() + 3600]);

        $request = (new Psr17Factory())
            ->createServerRequest('GET', self::PROTECTED_PATH)
            ->withHeader('X-Authorization', 'Bearer ' . $token);

        $response = $this->app->handle($request);

        self::assertSame(
            '',
            $response->getHeaderLine('WWW-Authenticate'),
            'A valid token mirrored only into X-Authorization must clear the bearer auth stage (no challenge issued).',
        );
    }

    /**
     * The auth stage actually receives the mirrored credential: an INVALID token
     * in `X-Authorization` only is rejected as `invalid_token` (AdminApiAuthMiddleware
     * saw a token and rejected it), NOT `missing_token` — which is only possible if
     * the fallback receiver restored `Authorization` from the mirror before auth ran.
     */
    public function testInvalidTokenInMirrorOnlyReachesBearerStageAsInvalidNotMissing(): void
    {
        $request = (new Psr17Factory())
            ->createServerRequest('GET', self::PROTECTED_PATH)
            ->withHeader('X-Authorization', 'Bearer not-a-real-token');

        $response = $this->app->handle($request);

        self::assertSame(401, $response->getStatusCode());
        $wwwAuth = $response->getHeaderLine('WWW-Authenticate');
        self::assertStringContainsString('error="invalid_token"', $wwwAuth);
        self::assertStringNotContainsString('error="missing_token"', $wwwAuth);
    }

    /**
     * Baseline / control: with NO credential in either header, the auth stage
     * reports `missing_token`. This is the response a mirror-only request would get
     * if the opt-in fallback were disabled.
     */
    public function testNoCredentialYieldsMissingToken(): void
    {
        $request = (new Psr17Factory())->createServerRequest('GET', self::PROTECTED_PATH);

        $response = $this->app->handle($request);

        self::assertSame(401, $response->getStatusCode());
        self::assertStringContainsString(
            'error="missing_token"',
            $response->getHeaderLine('WWW-Authenticate'),
        );
    }

    /**
     * The standard header still wins when both are present (byte-for-byte behaviour
     * unchanged on hosting that delivers `Authorization`): a valid standard token
     * clears the bearer stage even when an invalid mirror is also sent. If the
     * receiver wrongly preferred the mirror, AdminApiAuthMiddleware would reject the
     * invalid token with an `invalid_token` challenge; its absence proves
     * standard-header precedence.
     */
    public function testStandardAuthorizationHeaderTakesPrecedenceOverMirror(): void
    {
        $token = $this->issuer->issue(['sub' => 'admin-e2e', 'exp' => time() + 3600]);

        $request = (new Psr17Factory())
            ->createServerRequest('GET', self::PROTECTED_PATH)
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->withHeader('X-Authorization', 'Bearer not-a-real-token');

        $response = $this->app->handle($request);

        self::assertSame('', $response->getHeaderLine('WWW-Authenticate'));
    }
}
