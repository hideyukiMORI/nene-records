<?php

declare(strict_types=1);

namespace NeNeRecords\EntityTag;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use NeNeRecords\Entity\EntityNotFoundException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class AttachEntityTagHandler
{
    public function __construct(
        private AttachEntityTagUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $entityId = (int) ($parameters['entityId'] ?? 0);

        if ($entityId <= 0) {
            throw new EntityNotFoundException($entityId);
        }

        $body = JsonRequestBodyParser::parse($request);

        $tagIdRaw = $body['tag_id'] ?? null;

        if (!is_int($tagIdRaw) && !(is_string($tagIdRaw) && ctype_digit($tagIdRaw))) {
            throw new ValidationException([
                new ValidationError('tag_id', 'Tag id is required.', 'required'),
            ]);
        }

        $tagId = (int) $tagIdRaw;

        if ($tagId <= 0) {
            throw new ValidationException([
                new ValidationError('tag_id', 'Tag id must be a positive integer.', 'format'),
            ]);
        }

        $output = $this->useCase->execute(new AttachEntityTagInput($entityId, $tagId));

        return $this->response->create(
            ['id' => $output->id, 'slug' => $output->slug, 'name' => $output->name],
            201,
        );
    }
}
