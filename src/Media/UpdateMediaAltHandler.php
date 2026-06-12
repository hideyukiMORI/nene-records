<?php

declare(strict_types=1);

namespace NeNeRecords\Media;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class UpdateMediaAltHandler
{
    public function __construct(
        private UpdateMediaAltUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $id = (int) ($parameters['id'] ?? 0);

        if ($id <= 0) {
            throw new MediaNotFoundException($id);
        }

        $body = JsonRequestBodyParser::parse($request);
        $rawAlt = $body['alt_text'] ?? null;
        $altText = is_string($rawAlt) ? $rawAlt : null;

        $media = $this->useCase->execute(new UpdateMediaAltInput(id: $id, altText: $altText));

        return $this->response->create([
            'id' => $media->id,
            'url' => $media->url,
            'original_name' => $media->originalName,
            'mime_type' => $media->mimeType,
            'size' => $media->size,
            'width' => $media->width,
            'height' => $media->height,
            'alt_text' => $media->altText,
            'created_at' => $media->createdAt,
        ]);
    }
}
