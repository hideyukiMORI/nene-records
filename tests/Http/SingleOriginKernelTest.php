<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Http;

use NeNeRecords\Http\SingleOriginKernel;
use NeNeRecords\Http\SpaShellFallback;
use NeNeRecords\Tests\UrlRedirect\InMemoryUrlRedirectRepository;
use NeNeRecords\UrlRedirect\UrlRedirectResolver;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class SingleOriginKernelTest extends TestCase
{
    private Psr17Factory $factory;
    private InMemoryUrlRedirectRepository $redirects;
    private string $shellPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new Psr17Factory();
        $this->redirects = new InMemoryUrlRedirectRepository();

        $shellPath = tempnam(sys_get_temp_dir(), 'shell_');
        self::assertNotFalse($shellPath);
        file_put_contents($shellPath, '<!doctype html><div id="root"></div>');
        $this->shellPath = $shellPath;
    }

    protected function tearDown(): void
    {
        @unlink($this->shellPath);
        parent::tearDown();
    }

    /** Inner application handler that always returns the given status. */
    private function appReturning(int $status): RequestHandlerInterface
    {
        $factory = $this->factory;

        return new class ($factory, $status) implements RequestHandlerInterface {
            public function __construct(
                private Psr17Factory $factory,
                private int $status,
            ) {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return $this->factory->createResponse($this->status);
            }
        };
    }

    private function kernel(RequestHandlerInterface $app): SingleOriginKernel
    {
        return new SingleOriginKernel(
            $app,
            new UrlRedirectResolver($this->redirects, $this->factory),
            new SpaShellFallback($this->shellPath, $this->factory, $this->factory),
        );
    }

    public function testPassesThroughNon404Responses(): void
    {
        $response = $this->kernel($this->appReturning(200))
            ->handle($this->factory->createServerRequest('GET', 'https://site.test/posts/1'));

        self::assertSame(200, $response->getStatusCode());
    }

    public function testRedirectTakesPrecedenceOverShellOn404(): void
    {
        $this->redirects->save('/2024/01/hello-world', '/posts/1');

        $request = $this->factory
            ->createServerRequest('GET', 'https://site.test/2024/01/hello-world')
            ->withHeader('Accept', 'text/html');

        $response = $this->kernel($this->appReturning(404))->handle($request);

        self::assertSame(301, $response->getStatusCode());
        self::assertSame('/posts/1', $response->getHeaderLine('Location'));
    }

    public function testFallsBackToShellWhenNoRedirectMatches(): void
    {
        $request = $this->factory
            ->createServerRequest('GET', 'https://site.test/admin/dashboard')
            ->withHeader('Accept', 'text/html');

        $response = $this->kernel($this->appReturning(404))->handle($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
        self::assertStringContainsString('id="root"', (string) $response->getBody());
    }

    public function testKeepsGenuine404ForApiPaths(): void
    {
        $request = $this->factory
            ->createServerRequest('GET', 'https://site.test/api/v1/unknown')
            ->withHeader('Accept', 'application/json');

        $response = $this->kernel($this->appReturning(404))->handle($request);

        self::assertSame(404, $response->getStatusCode());
    }
}
