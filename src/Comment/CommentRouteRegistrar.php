<?php

declare(strict_types=1);

namespace NeNeRecords\Comment;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class CommentRouteRegistrar
{
    public function __construct(
        private PostCommentHandler $postHandler,
        private ListCommentsHandler $listHandler,
        private ListAllCommentsHandler $listAllHandler,
        private ApproveCommentHandler $approveHandler,
        private DeleteCommentHandler $deleteHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $postHandler = $this->postHandler;
        $listHandler = $this->listHandler;
        $listAllHandler = $this->listAllHandler;
        $approveHandler = $this->approveHandler;
        $deleteHandler = $this->deleteHandler;

        // Public endpoints
        $router->post('/api/v1/entities/{id}/comments', static fn (ServerRequestInterface $r) => $postHandler->handle($r));
        $router->get('/api/v1/entities/{id}/comments', static fn (ServerRequestInterface $r) => $listHandler->handle($r));

        // Admin endpoints
        $router->get('/api/v1/admin/comments', static fn (ServerRequestInterface $r) => $listAllHandler->handle($r));
        $router->patch('/api/v1/admin/comments/{id}/approve', static fn (ServerRequestInterface $r) => $approveHandler->handle($r));
        $router->delete('/api/v1/admin/comments/{id}', static fn (ServerRequestInterface $r) => $deleteHandler->handle($r));
    }
}
