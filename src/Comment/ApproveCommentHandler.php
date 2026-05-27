<?php

declare(strict_types=1);

namespace NeNeRecords\Comment;

use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ApproveCommentHandler
{
    public function __construct(
        private ApproveCommentUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $id = (int) ($params['id'] ?? 0);

        $output = $this->useCase->execute(new ApproveCommentInput($id));

        return $this->response->create([
            'id'          => $output->id,
            'entity_id'   => $output->entityId,
            'author_name' => $output->authorName,
            'body'        => $output->body,
            'is_approved' => $output->isApproved,
            'created_at'  => $output->createdAt,
        ]);
    }
}
