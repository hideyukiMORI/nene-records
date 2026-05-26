<?php

declare(strict_types=1);

namespace NeNeRecords\Comment;

use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ListCommentsHandler
{
    public function __construct(
        private ListCommentsUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $entityId = (int) ($params['id'] ?? 0);

        // Public endpoint: only return approved comments
        $output = $this->useCase->execute(new ListCommentsInput(
            entityId: $entityId,
            approvedOnly: true,
        ));

        $items = array_map(
            static fn (ListCommentsItem $item): array => [
                'id'          => $item->id,
                'entity_id'   => $item->entityId,
                'author_name' => $item->authorName,
                'body'        => $item->body,
                'is_approved' => $item->isApproved,
                'created_at'  => $item->createdAt,
            ],
            $output->items,
        );

        return $this->response->create(['items' => $items]);
    }
}
