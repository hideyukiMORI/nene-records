<?php

declare(strict_types=1);

namespace NeNeRecords\Comment;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class PostCommentHandler
{
    public function __construct(
        private PostCommentUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $entityId = (int) ($params['id'] ?? 0);

        $body = JsonRequestBodyParser::parse($request);
        $errors = [];

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

        $comment = $this->useCase->execute(new PostCommentInput(
            entityId: $entityId,
            authorName: $authorName,
            authorEmail: $authorEmail,
            body: $commentBody,
        ));

        return $this->response->create($this->serialize($comment), 201);
    }

    /** @return array<string, mixed> */
    private function serialize(Comment $comment): array
    {
        return [
            'id'          => $comment->id,
            'entity_id'   => $comment->entityId,
            'author_name' => $comment->authorName,
            'body'        => $comment->body,
            'is_approved' => $comment->isApproved,
            'created_at'  => $comment->createdAt,
        ];
    }
}
