<?php

declare(strict_types=1);

namespace NeNeRecords\Comment;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Middleware\RateLimitStorageInterface;
use Nene2\Routing\Router;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class PostCommentHandler
{
    /** Hidden form field that real users leave empty; bots that auto-fill it are rejected. */
    private const HONEYPOT_FIELD = 'website';

    public function __construct(
        private PostCommentUseCaseInterface $useCase,
        private JsonResponseFactory $response,
        private ProblemDetailsResponseFactory $problemDetails,
        private RateLimitStorageInterface $rateLimitStorage,
        /** Max comments accepted per IP, per entity, within the window. */
        private int $maxCommentsPerWindow = 3,
        /** Rate-limit window in seconds (default: 1 hour). */
        private int $rateLimitWindowSeconds = 3600,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $entityId = (int) ($params['id'] ?? 0);

        $body = JsonRequestBodyParser::parse($request);
        $errors = [];

        // Honeypot: a non-empty hidden field means an automated submission.
        if (trim((string) ($body[self::HONEYPOT_FIELD] ?? '')) !== '') {
            $errors[] = new ValidationError(self::HONEYPOT_FIELD, 'Spam detected.', 'spam');
        }

        $authorName = trim((string) ($body['author_name'] ?? ''));
        $authorEmail = trim((string) ($body['author_email'] ?? ''));
        $commentBody = trim((string) ($body['body'] ?? ''));

        if ($authorName === '') {
            $errors[] = new ValidationError('author_name', 'Author name is required.', 'required');
        }

        if ($authorEmail === '') {
            $errors[] = new ValidationError('author_email', 'Author email is required.', 'required');
        } elseif (!filter_var($authorEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = new ValidationError('author_email', 'Author email must be a valid email address.', 'email');
        }

        if ($commentBody === '') {
            $errors[] = new ValidationError('body', 'Body is required.', 'required');
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        // Rate limit only valid submissions so malformed/spam attempts don't consume the budget.
        $rateLimitResponse = $this->enforceRateLimit($request, $entityId);
        if ($rateLimitResponse !== null) {
            return $rateLimitResponse;
        }

        $output = $this->useCase->execute(new PostCommentInput(
            entityId: $entityId,
            authorName: $authorName,
            authorEmail: $authorEmail,
            body: $commentBody,
        ));

        return $this->response->create([
            'id'          => $output->id,
            'entity_id'   => $output->entityId,
            'author_name' => $output->authorName,
            'body'        => $output->body,
            'is_approved' => $output->isApproved,
            'created_at'  => $output->createdAt,
        ], 201);
    }

    /**
     * Throttle comments per client IP and per entity. Returns a 429 Problem Details
     * response when the limit is exceeded, or null when the request may proceed.
     */
    private function enforceRateLimit(ServerRequestInterface $request, int $entityId): ?ResponseInterface
    {
        $params = $request->getServerParams();
        $ip = (string) ($params['REMOTE_ADDR'] ?? 'unknown');
        $key = sprintf('comment:%s:%d', $ip, $entityId);

        $result = $this->rateLimitStorage->hit($key, $this->rateLimitWindowSeconds);

        if ($result['count'] <= $this->maxCommentsPerWindow) {
            return null;
        }

        $retryAfter = max(0, $result['reset_at'] - time());

        return $this->problemDetails->create(
            $request,
            'too-many-requests',
            'Too Many Requests',
            429,
            sprintf(
                'Comment rate limit of %d per %d seconds exceeded. Try again in %d seconds.',
                $this->maxCommentsPerWindow,
                $this->rateLimitWindowSeconds,
                $retryAfter,
            ),
        )
            ->withHeader('Retry-After', (string) $retryAfter)
            ->withHeader('X-RateLimit-Limit', (string) $this->maxCommentsPerWindow)
            ->withHeader('X-RateLimit-Remaining', '0')
            ->withHeader('X-RateLimit-Reset', (string) $result['reset_at']);
    }
}
