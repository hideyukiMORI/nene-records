<?php

declare(strict_types=1);

namespace NeNeRecords\Comment;

use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ListAllCommentsHandler
{
    public function __construct(
        private ListAllCommentsUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $output = $this->useCase->execute();

        $items = array_map(
            static fn (ListCommentsItem $item): array => [
                'id'           => $item->id,
                'entity_id'    => $item->entityId,
                'author_name'  => $item->authorName,
                'author_email' => $item->authorEmail,
                'body'         => $item->body,
                'is_approved'  => $item->isApproved,
                'created_at'   => $item->createdAt,
            ],
            $output->items,
        );

        return $this->response->create(['items' => $items]);
    }
}
