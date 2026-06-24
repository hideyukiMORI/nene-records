<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\UrlRedirect;

use NeNeRecords\UrlRedirect\UrlRedirectRepositoryInterface;
use NeNeRecords\UrlRedirect\UrlRedirectResolver;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

final class UrlRedirectResolverTest extends TestCase
{
    private Psr17Factory $factory;
    private InMemoryUrlRedirectRepository $redirects;
    private UrlRedirectResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new Psr17Factory();
        $this->redirects = new InMemoryUrlRedirectRepository();
        $this->redirects->save('/2024/01/hello-world', '/posts/1');
        $this->resolver = new UrlRedirectResolver($this->redirects, $this->factory);
    }

    private function notFound(): ResponseInterface
    {
        return $this->factory->createResponse(404);
    }

    private function get(string $path): ServerRequestInterface
    {
        return $this->factory->createServerRequest('GET', 'https://site.test' . $path);
    }

    public function testRedirectsMatchedPathWith301(): void
    {
        $response = $this->resolver->apply($this->get('/2024/01/hello-world'), $this->notFound());

        self::assertSame(301, $response->getStatusCode());
        self::assertSame('/posts/1', $response->getHeaderLine('Location'));
    }

    public function testNormalizesTrailingSlashBeforeLookup(): void
    {
        $response = $this->resolver->apply($this->get('/2024/01/hello-world/'), $this->notFound());

        self::assertSame(301, $response->getStatusCode());
        self::assertSame('/posts/1', $response->getHeaderLine('Location'));
    }

    public function testLeavesUnmatchedPathAs404(): void
    {
        $response = $this->resolver->apply($this->get('/2024/01/unknown'), $this->notFound());

        self::assertSame(404, $response->getStatusCode());
    }

    public function testIgnoresNon404Responses(): void
    {
        $ok = $this->factory->createResponse(200);
        $response = $this->resolver->apply($this->get('/2024/01/hello-world'), $ok);

        self::assertSame(200, $response->getStatusCode());
    }

    public function testIgnoresApiPaths(): void
    {
        $this->redirects->save('/api/v1/legacy', '/posts/9');
        $response = $this->resolver->apply($this->get('/api/v1/legacy'), $this->notFound());

        self::assertSame(404, $response->getStatusCode());
    }

    public function testIgnoresNonGetMethods(): void
    {
        $request = $this->factory->createServerRequest('POST', 'https://site.test/2024/01/hello-world');
        $response = $this->resolver->apply($request, $this->notFound());

        self::assertSame(404, $response->getStatusCode());
    }

    public function testFallsThroughWhenLookupThrows(): void
    {
        // No org resolved (e.g. /admin) → the org-scoped repo's holder is unset and
        // findTargetBySource throws. The resolver must swallow it and return the
        // original 404 so the SPA shell fallback can serve the request.
        $throwing = new class () implements UrlRedirectRepositoryInterface {
            public function findTargetBySource(string $sourcePath): ?string
            {
                throw new RuntimeException('RequestScopedHolder::get() called before set()');
            }

            public function save(string $sourcePath, string $targetPath): void
            {
            }
        };
        $resolver = new UrlRedirectResolver($throwing, $this->factory);

        $response = $resolver->apply($this->get('/admin'), $this->notFound());

        self::assertSame(404, $response->getStatusCode());
    }
}
