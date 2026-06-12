<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ListMediaHandler
{
    public function __construct(
        private ListMediaUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $output = $this->useCase->execute();

        $items = array_map(
            static fn (ListMediaItem $item): array => [
                'id' => $item->id,
                'url' => $item->url,
                'original_name' => $item->originalName,
                'mime_type' => $item->mimeType,
                'size' => $item->size,
                'width' => $item->width,
                'height' => $item->height,
                'alt_text' => $item->altText,
                'created_at' => $item->createdAt,
            ],
            $output->items,
        );

        return $this->response->create(['items' => $items]);
    }
}
