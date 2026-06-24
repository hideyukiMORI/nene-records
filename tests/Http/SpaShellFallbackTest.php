<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Http;

use NeNeRecords\Http\SpaShellFallback;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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
}
