<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Comment;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\RuntimeApplicationFactory;
use Nene2\Http\UtcClock;
use Nene2\Middleware\InMemoryRateLimitStorage;
use NeNeRecords\Comment\ApproveCommentHandler;
use NeNeRecords\Comment\ApproveCommentUseCase;
use NeNeRecords\Comment\CommentNotFoundExceptionHandler;
use NeNeRecords\Comment\CommentRouteRegistrar;
use NeNeRecords\Comment\DeleteCommentHandler;
use NeNeRecords\Comment\DeleteCommentUseCase;
use NeNeRecords\Comment\ListAllCommentsHandler;
use NeNeRecords\Comment\ListAllCommentsUseCase;
use NeNeRecords\Comment\ListCommentsHandler;
use NeNeRecords\Comment\ListCommentsUseCase;
use NeNeRecords\Comment\PostCommentHandler;
use NeNeRecords\Comment\PostCommentUseCase;
use NeNeRecords\Notification\NullNotifier;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class CommentHttpTest extends TestCase
{
    private Psr17Factory $factory;
    private InMemoryCommentRepository $repository;
    private RequestHandlerInterface $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new Psr17Factory();
        $this->repository = new InMemoryCommentRepository();

        $jsonResponse = new JsonResponseFactory($this->factory, $this->factory);
        $problemDetails = new ProblemDetailsResponseFactory($this->factory, $this->factory);

        $registrar = new CommentRouteRegistrar(
            new PostCommentHandler(
                new PostCommentUseCase($this->repository, new NullNotifier()),
                $jsonResponse,
                $problemDetails,
                new InMemoryRateLimitStorage(),
                new UtcClock(),
            ),
            new ListCommentsHandler(new ListCommentsUseCase($this->repository), $jsonResponse),
            new ListAllCommentsHandler(new ListAllCommentsUseCase($this->repository), $jsonResponse),
            new ApproveCommentHandler(new ApproveCommentUseCase($this->repository), $jsonResponse),
            new DeleteCommentHandler(new DeleteCommentUseCase($this->repository), $this->factory),
        );

        $this->application = (new RuntimeApplicationFactory(
            $this->factory,
            $this->factory,
            domainExceptionHandlers: [
                new CommentNotFoundExceptionHandler($problemDetails),
            ],
            routeRegistrars: [$registrar],
        ))->create();
    }

    public function testListCommentsReturnsEmptyInitially(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/entities/1/comments'),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame([], $payload['items']);
    }

    public function testPostCommentReturns201(): void
    {
        $body = $this->factory->createStream(json_encode([
            'author_name'  => 'Alice',
            'author_email' => 'alice@example.com',
            'body'         => 'Great post!',
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entities/1/comments')
                ->withBody($body),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(201, $response->getStatusCode());
        self::assertSame('Alice', $payload['author_name']);
        self::assertSame('Great post!', $payload['body']);
        self::assertFalse($payload['is_approved']);
        self::assertIsInt($payload['id']);
    }

    public function testPostCommentWithoutAuthorNameReturns422(): void
    {
        $body = $this->factory->createStream(json_encode([
            'author_email' => 'alice@example.com',
            'body'         => 'Hello',
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entities/1/comments')
                ->withBody($body),
        );

        self::assertSame(422, $response->getStatusCode());
    }

    public function testPostCommentWithInvalidEmailReturns422(): void
    {
        $body = $this->factory->createStream(json_encode([
            'author_name'  => 'Alice',
            'author_email' => 'not-an-email',
            'body'         => 'Hello',
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entities/1/comments')
                ->withBody($body),
        );

        self::assertSame(422, $response->getStatusCode());
    }

    public function testPostCommentWithoutBodyReturns422(): void
    {
        $body = $this->factory->createStream(json_encode([
            'author_name'  => 'Alice',
            'author_email' => 'alice@example.com',
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entities/1/comments')
                ->withBody($body),
        );

        self::assertSame(422, $response->getStatusCode());
    }

    public function testPostCommentWithFilledHoneypotReturns422(): void
    {
        $body = $this->factory->createStream(json_encode([
            'author_name'  => 'SpamBot',
            'author_email' => 'spam@example.com',
            'body'         => 'Buy cheap stuff!',
            'website'      => 'http://spam.example.com',
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entities/1/comments')
                ->withBody($body),
        );

        self::assertSame(422, $response->getStatusCode());

        // The honeypot submission must not create a comment.
        $listResponse = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/admin/comments'),
        );
        self::assertCount(0, $this->decodeJson($listResponse)['items']);
    }

    public function testEmptyHoneypotIsAccepted(): void
    {
        $body = $this->factory->createStream(json_encode([
            'author_name'  => 'Alice',
            'author_email' => 'alice@example.com',
            'body'         => 'Real comment',
            'website'      => '',
        ], JSON_THROW_ON_ERROR));

        $response = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entities/1/comments')
                ->withBody($body),
        );

        self::assertSame(201, $response->getStatusCode());
    }

    public function testFourthCommentFromSameIpToSameEntityIsRateLimited(): void
    {
        $post = function (string $name): ResponseInterface {
            $body = $this->factory->createStream(json_encode([
                'author_name'  => $name,
                'author_email' => strtolower($name) . '@example.com',
                'body'         => 'Comment from ' . $name,
            ], JSON_THROW_ON_ERROR));

            return $this->application->handle(
                $this->factory->createServerRequest(
                    'POST',
                    'https://example.test/api/v1/entities/1/comments',
                    ['REMOTE_ADDR' => '203.0.113.7'],
                )->withBody($body),
            );
        };

        // First 3 comments succeed.
        self::assertSame(201, $post('Alice')->getStatusCode());
        self::assertSame(201, $post('Bob')->getStatusCode());
        self::assertSame(201, $post('Carol')->getStatusCode());

        // The 4th within the window is rejected with 429.
        $fourth = $post('Dave');
        self::assertSame(429, $fourth->getStatusCode());
        self::assertNotSame('', $fourth->getHeaderLine('Retry-After'));
    }

    public function testRateLimitIsPerEntity(): void
    {
        $post = function (int $entityId): ResponseInterface {
            $body = $this->factory->createStream(json_encode([
                'author_name'  => 'Alice',
                'author_email' => 'alice@example.com',
                'body'         => 'Hello',
            ], JSON_THROW_ON_ERROR));

            return $this->application->handle(
                $this->factory->createServerRequest(
                    'POST',
                    "https://example.test/api/v1/entities/{$entityId}/comments",
                    ['REMOTE_ADDR' => '203.0.113.7'],
                )->withBody($body),
            );
        };

        // 3 comments to entity 1 exhaust its bucket.
        $post(1);
        $post(1);
        $post(1);
        self::assertSame(429, $post(1)->getStatusCode());

        // A different entity has its own bucket and still accepts comments.
        self::assertSame(201, $post(2)->getStatusCode());
    }

    public function testListCommentsOnlyShowsApproved(): void
    {
        // Post two comments
        foreach (['Alice', 'Bob'] as $name) {
            $body = $this->factory->createStream(json_encode([
                'author_name'  => $name,
                'author_email' => strtolower($name) . '@example.com',
                'body'         => 'Comment from ' . $name,
            ], JSON_THROW_ON_ERROR));
            $this->application->handle(
                $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entities/1/comments')
                    ->withBody($body),
            );
        }

        // Approve only Alice's (id=1)
        $this->application->handle(
            $this->factory->createServerRequest('PATCH', 'https://example.test/api/v1/admin/comments/1/approve'),
        );

        // Public list should only show Alice's comment
        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/entities/1/comments'),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertCount(1, $payload['items']);
        self::assertSame('Alice', $payload['items'][0]['author_name']);
        self::assertArrayNotHasKey('author_email', $payload['items'][0]);
    }

    public function testListAllCommentsIncludesPendingAndEmail(): void
    {
        // Post a comment
        $body = $this->factory->createStream(json_encode([
            'author_name'  => 'Alice',
            'author_email' => 'alice@example.com',
            'body'         => 'Hello',
        ], JSON_THROW_ON_ERROR));
        $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entities/1/comments')
                ->withBody($body),
        );

        $response = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/admin/comments'),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertCount(1, $payload['items']);
        self::assertArrayHasKey('author_email', $payload['items'][0]);
        self::assertSame('alice@example.com', $payload['items'][0]['author_email']);
        self::assertFalse($payload['items'][0]['is_approved']);
    }

    public function testApproveCommentReturns200(): void
    {
        // Post a comment
        $body = $this->factory->createStream(json_encode([
            'author_name'  => 'Bob',
            'author_email' => 'bob@example.com',
            'body'         => 'Nice!',
        ], JSON_THROW_ON_ERROR));
        $createResponse = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entities/5/comments')
                ->withBody($body),
        );
        $created = $this->decodeJson($createResponse);
        $id = $created['id'];

        // Approve
        $response = $this->application->handle(
            $this->factory->createServerRequest('PATCH', "https://example.test/api/v1/admin/comments/{$id}/approve"),
        );
        $payload = $this->decodeJson($response);

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($payload['is_approved']);
    }

    public function testApproveNonExistentCommentReturns404(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('PATCH', 'https://example.test/api/v1/admin/comments/9999/approve'),
        );

        self::assertSame(404, $response->getStatusCode());
    }

    public function testDeleteCommentReturns204(): void
    {
        // Post
        $body = $this->factory->createStream(json_encode([
            'author_name'  => 'Charlie',
            'author_email' => 'charlie@example.com',
            'body'         => 'To be deleted',
        ], JSON_THROW_ON_ERROR));
        $createResponse = $this->application->handle(
            $this->factory->createServerRequest('POST', 'https://example.test/api/v1/entities/2/comments')
                ->withBody($body),
        );
        $created = $this->decodeJson($createResponse);
        $id = $created['id'];

        // Delete
        $response = $this->application->handle(
            $this->factory->createServerRequest('DELETE', "https://example.test/api/v1/admin/comments/{$id}"),
        );
        self::assertSame(204, $response->getStatusCode());

        // Admin list should be empty now
        $listResponse = $this->application->handle(
            $this->factory->createServerRequest('GET', 'https://example.test/api/v1/admin/comments'),
        );
        $listPayload = $this->decodeJson($listResponse);
        self::assertCount(0, $listPayload['items']);
    }

    public function testDeleteNonExistentCommentReturns404(): void
    {
        $response = $this->application->handle(
            $this->factory->createServerRequest('DELETE', 'https://example.test/api/v1/admin/comments/9999'),
        );

        self::assertSame(404, $response->getStatusCode());
    }

    /** @return array<string, mixed> */
    private function decodeJson(ResponseInterface $response): array
    {
        $payload = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);

        return $payload;
    }
}
