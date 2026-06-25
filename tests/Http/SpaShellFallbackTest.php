<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Http;

use NeNeRecords\Http\PublicHtmlCsp;
use NeNeRecords\Http\SpaShellFallback;
use NeNeRecords\Setting\ListPublicSettingsOutput;
use NeNeRecords\Setting\ListPublicSettingsUseCaseInterface;
use NeNeRecords\Setting\SettingDef;
use NeNeRecords\Setting\SettingEntry;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

final class SpaShellFallbackTest extends TestCase
{
    private Psr17Factory $factory;
    private SpaShellFallback $fallback;
    private string $shellPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new Psr17Factory();
        $this->shellPath = __DIR__ . '/fixtures/spa-index.html';
        $this->fallback = new SpaShellFallback($this->shellPath, $this->factory, $this->factory);
    }

    private function request(string $method, string $path, string $accept = 'text/html'): ServerRequestInterface
    {
        return $this->factory
            ->createServerRequest($method, 'https://example.test' . $path)
            ->withHeader('Accept', $accept);
    }

    private function notFound(): ResponseInterface
    {
        return $this->factory->createResponse(404);
    }

    public function testServesShellForUnmatchedGetHtmlNavigation(): void
    {
        $result = $this->fallback->apply($this->request('GET', '/admin/users'), $this->notFound());

        self::assertSame(200, $result->getStatusCode());
        self::assertStringContainsString('text/html', $result->getHeaderLine('Content-Type'));
        self::assertStringContainsString('<div id="root">', (string) $result->getBody());
        // SPA-aware CSP so the shell's inline styles / data: fonts are not blocked.
        $csp = $result->getHeaderLine('Content-Security-Policy');
        self::assertStringContainsString("style-src 'self' 'unsafe-inline'", $csp);
        self::assertStringContainsString("font-src 'self' data:", $csp);
    }

    public function testServesShellForBrowsePath(): void
    {
        // 1-segment browse path (e.g. /posts) is not a record permalink → SPA shell.
        $result = $this->fallback->apply($this->request('GET', '/posts'), $this->notFound());

        self::assertSame(200, $result->getStatusCode());
    }

    public function testPassesThroughApiMediaViewAssetPaths(): void
    {
        foreach (['/api/v1/things', '/media/2026/06/x.png', '/view/posts/x', '/assets/index.js'] as $path) {
            $result = $this->fallback->apply($this->request('GET', $path), $this->notFound());
            self::assertSame(404, $result->getStatusCode(), $path . ' must keep its genuine 404');
        }
    }

    public function testIgnoresNonGetRequests(): void
    {
        $result = $this->fallback->apply($this->request('POST', '/admin/users'), $this->notFound());

        self::assertSame(404, $result->getStatusCode());
    }

    public function testIgnoresNonHtmlRequests(): void
    {
        $result = $this->fallback->apply(
            $this->request('GET', '/admin/users', 'application/json'),
            $this->notFound(),
        );

        self::assertSame(404, $result->getStatusCode());
    }

    public function testLeavesNon404ResponsesUntouched(): void
    {
        $ok = $this->factory->createResponse(200);
        $result = $this->fallback->apply($this->request('GET', '/admin/users'), $ok);

        self::assertSame(200, $result->getStatusCode());
        self::assertSame('', (string) $result->getBody());
    }

    public function testNoOpWhenShellMissing(): void
    {
        $fallback = new SpaShellFallback(__DIR__ . '/fixtures/does-not-exist.html', $this->factory, $this->factory);
        $result = $fallback->apply($this->request('GET', '/admin/users'), $this->notFound());

        self::assertSame(404, $result->getStatusCode());
    }

    public function testInjectsRuntimeBasePathAtRoot(): void
    {
        $result = $this->fallback->apply($this->request('GET', '/admin/users'), $this->notFound());
        $body = (string) $result->getBody();

        // Root: base href unchanged, global exposed as empty.
        self::assertStringContainsString('<base href="/" />', $body);
        self::assertStringContainsString('window.__BASE_PATH__ = "";', $body);
    }

    public function testRepointsBaseHrefAndBasePathUnderSubdirectory(): void
    {
        $fallback = new SpaShellFallback($this->shellPath, $this->factory, $this->factory, null, '/blog');
        $body = (string) $fallback->apply($this->request('GET', '/admin/users'), $this->notFound())->getBody();

        self::assertStringContainsString('<base href="/blog/" />', $body);
        self::assertStringContainsString('window.__BASE_PATH__ = "/blog";', $body);
        self::assertStringNotContainsString('<base href="/" />', $body);
    }

    public function testNoAnalyticsWhenNoSettingsUseCase(): void
    {
        // Default construction (no settings) keeps the strict baseline policy.
        $result = $this->fallback->apply($this->request('GET', '/posts'), $this->notFound());

        self::assertSame(PublicHtmlCsp::POLICY, $result->getHeaderLine('Content-Security-Policy'));
        self::assertStringNotContainsString('googletagmanager', (string) $result->getBody());
    }

    public function testInjectsAnalyticsOnPublicPathWhenConfigured(): void
    {
        $fallback = $this->fallbackWith(['analytics_ga4_id' => 'G-XYZ987']);
        $result = $fallback->apply($this->request('GET', '/posts'), $this->notFound());

        $body = (string) $result->getBody();
        $csp = $result->getHeaderLine('Content-Security-Policy');

        self::assertSame(200, $result->getStatusCode());
        self::assertStringContainsString('gtag/js?id=G-XYZ987', $body);
        self::assertStringContainsString('googletagmanager', $csp);

        // The injected script's nonce must match the one in the CSP header.
        preg_match('/nonce="([0-9a-f]{32})"/', $body, $bodyNonce);
        $nonce = $bodyNonce[1] ?? '';
        self::assertNotSame('', $nonce);
        self::assertStringContainsString("'nonce-{$nonce}'", $csp);
    }

    public function testSkipsAnalyticsOnAdminPath(): void
    {
        $fallback = $this->fallbackWith(['analytics_ga4_id' => 'G-XYZ987']);
        $result = $fallback->apply($this->request('GET', '/admin/users'), $this->notFound());

        self::assertSame(200, $result->getStatusCode());
        self::assertStringNotContainsString('googletagmanager', (string) $result->getBody());
        self::assertStringNotContainsString('googletagmanager', $result->getHeaderLine('Content-Security-Policy'));
    }

    public function testBestEffortWhenSettingsResolutionThrows(): void
    {
        $throwing = new class () implements ListPublicSettingsUseCaseInterface {
            public function execute(): ListPublicSettingsOutput
            {
                throw new RuntimeException('no org resolved');
            }
        };
        $fallback = new SpaShellFallback($this->shellPath, $this->factory, $this->factory, $throwing);

        $result = $fallback->apply($this->request('GET', '/posts'), $this->notFound());

        // A failed lookup must never break the shell — serve it with the strict policy.
        self::assertSame(200, $result->getStatusCode());
        self::assertStringContainsString('<div id="root">', (string) $result->getBody());
        self::assertStringNotContainsString('googletagmanager', (string) $result->getBody());
    }

    /** @param array<string, string> $settings */
    private function fallbackWith(array $settings): SpaShellFallback
    {
        $entries = [];
        foreach ($settings as $key => $value) {
            $entries[] = new SettingEntry(
                new SettingDef($key, 'text', '', true, $key, 1),
                $value,
                null,
            );
        }

        $useCase = new class ($entries) implements ListPublicSettingsUseCaseInterface {
            /** @param list<SettingEntry> $entries */
            public function __construct(private array $entries)
            {
            }

            public function execute(): ListPublicSettingsOutput
            {
                return new ListPublicSettingsOutput($this->entries);
            }
        };

        return new SpaShellFallback($this->shellPath, $this->factory, $this->factory, $useCase);
    }
}
