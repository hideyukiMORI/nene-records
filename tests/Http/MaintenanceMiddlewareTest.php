<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Http;

use Nene2\Auth\TokenVerificationException;
use Nene2\Auth\TokenVerifierInterface;
use NeNeRecords\Auth\SessionCookie;
use NeNeRecords\Http\MaintenanceMiddleware;
use NeNeRecords\Setting\SettingDef;
use NeNeRecords\Setting\SettingEntry;
use NeNeRecords\Setting\SettingRepositoryInterface;
use NeNeRecords\Setting\SettingRevision;
use NeNeRecords\Setting\SettingValue;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Per-org maintenance mode middleware (#813): 503 on the public surface for
 * anonymous visitors when the org setting is on; logged-in staff and back-office /
 * operational paths pass through; fail-open on read errors.
 */
final class MaintenanceMiddlewareTest extends TestCase
{
    private Psr17Factory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new Psr17Factory();
    }

    // ── ON + anonymous → 503 on the public surface ─────────────────────────────

    #[DataProvider('provideGatedPublicPaths')]
    public function testMaintenanceOnBlocksAnonymousPublicSurface(string $path): void
    {
        $response = $this->middleware('true')->process(
            $this->factory->createServerRequest('GET', 'https://acme.example' . $path),
            $this->passThrough(),
        );

        self::assertSame(503, $response->getStatusCode());
        self::assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
        self::assertNotSame('', $response->getHeaderLine('Retry-After'));
        self::assertStringContainsString('メンテナンス中です', (string) $response->getBody());
    }

    /** @return iterable<string, array{string}> */
    public static function provideGatedPublicPaths(): iterable
    {
        yield 'home'            => ['/'];
        yield 'public record'   => ['/posts/hello-world'];
        yield 'custom permalink' => ['/services'];
        yield 'public read API' => ['/api/v1/public/records/article/my-post'];
    }

    // ── ON + logged-in → pass through (staff can keep working / preview) ────────

    public function testLoggedInCookieBypassesMaintenance(): void
    {
        $request = $this->factory
            ->createServerRequest('GET', 'https://acme.example/')
            ->withCookieParams([SessionCookie::NAME => 'good']);

        self::assertSame(204, $this->middleware('true')->process($request, $this->passThrough())->getStatusCode());
    }

    public function testLoggedInBearerBypassesMaintenance(): void
    {
        $request = $this->factory
            ->createServerRequest('GET', 'https://acme.example/')
            ->withHeader('Authorization', 'Bearer good');

        self::assertSame(204, $this->middleware('true')->process($request, $this->passThrough())->getStatusCode());
    }

    public function testInvalidSessionDoesNotBypass(): void
    {
        $request = $this->factory
            ->createServerRequest('GET', 'https://acme.example/')
            ->withCookieParams([SessionCookie::NAME => 'expired']);

        self::assertSame(503, $this->middleware('true')->process($request, $this->passThrough())->getStatusCode());
    }

    // ── OFF → always pass through ──────────────────────────────────────────────

    #[DataProvider('provideOffValues')]
    public function testMaintenanceOffPassesThrough(?string $value): void
    {
        self::assertSame(204, $this->middleware($value)->process(
            $this->factory->createServerRequest('GET', 'https://acme.example/'),
            $this->passThrough(),
        )->getStatusCode());
    }

    /** @return iterable<string, array{?string}> */
    public static function provideOffValues(): iterable
    {
        yield 'false'  => ['false'];
        yield 'unset'  => [null];
    }

    // ── ON but exempt path → pass through (login/back-office/health/admin API) ──

    #[DataProvider('provideExemptPaths')]
    public function testExemptPathsNeverGated(string $path): void
    {
        self::assertSame(204, $this->middleware('true')->process(
            $this->factory->createServerRequest('GET', 'https://acme.example' . $path),
            $this->passThrough(),
        )->getStatusCode());
    }

    /** @return iterable<string, array{string}> */
    public static function provideExemptPaths(): iterable
    {
        yield 'health'        => ['/health'];
        yield 'login API'     => ['/api/v1/auth/login'];
        yield 'admin shell'   => ['/admin'];
        yield 'admin subpath' => ['/admin/posts'];
        yield 'login route'   => ['/login'];
        yield 'superadmin'    => ['/superadmin'];
        yield 'admin API'     => ['/api/v1/entities'];
    }

    // ── Fail-open: a settings read error must never take the site down ──────────

    public function testFailsOpenWhenSettingReadThrows(): void
    {
        self::assertSame(204, $this->middleware('true', throwOnRead: true)->process(
            $this->factory->createServerRequest('GET', 'https://acme.example/'),
            $this->passThrough(),
        )->getStatusCode());
    }

    // ── helpers ────────────────────────────────────────────────────────────────

    private function middleware(?string $maintenanceValue, bool $throwOnRead = false): MaintenanceMiddleware
    {
        return new MaintenanceMiddleware(
            $this->settings($maintenanceValue, $throwOnRead),
            $this->verifier(),
            $this->factory,
            $this->factory,
        );
    }

    private function settings(?string $value, bool $throw): SettingRepositoryInterface
    {
        return new class ($value, $throw) implements SettingRepositoryInterface {
            public function __construct(private ?string $value, private bool $throw)
            {
            }

            public function findValueByKey(string $settingKey): ?SettingValue
            {
                if ($this->throw) {
                    throw new \RuntimeException('settings backend down');
                }
                if ($this->value === null) {
                    return null;
                }

                return new SettingValue($settingKey, $this->value, false, null, null, null, '2026-01-01 00:00:00', '2026-01-01 00:00:00');
            }

            /** @return list<SettingDef> */
            public function findAllDefs(): array
            {
                return [];
            }

            public function findDefByKey(string $settingKey): ?SettingDef
            {
                return null;
            }

            /** @return list<SettingEntry> */
            public function findAllEntries(): array
            {
                return [];
            }

            /** @return list<SettingEntry> */
            public function findPublicEntries(): array
            {
                return [];
            }

            /** @return list<SettingRevision> */
            public function findRevisionsByKey(string $settingKey, int $limit, int $offset): array
            {
                return [];
            }

            public function applyValue(string $settingKey, string $value, ?int $actorUserId): SettingValue
            {
                throw new \LogicException('not used in tests');
            }
        };
    }

    private function verifier(): TokenVerifierInterface
    {
        return new class () implements TokenVerifierInterface {
            /** @return array<string, mixed> */
            public function verify(string $token): array
            {
                if ($token !== 'good') {
                    throw new TokenVerificationException('Invalid token.');
                }

                return ['role' => 'admin', 'sub' => 'admin@example.com'];
            }
        };
    }

    private function passThrough(): RequestHandlerInterface
    {
        return new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(204);
            }
        };
    }
}
