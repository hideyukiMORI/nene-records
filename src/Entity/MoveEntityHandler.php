<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

use InvalidArgumentException;
use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * `POST /api/v1/entities/{id}/move` — move a record (and its subtree) to a new
 * custom permalink, cascading descendant paths + recording 301s (#659).
 */
final readonly class MoveEntityHandler
{
    use ParsesPermalinkField;

    public function __construct(
        private MoveEntitySubtreeUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $id = (int) ($parameters['id'] ?? 0);

        if ($id <= 0) {
            throw new EntityNotFoundException($id);
        }

        $body = JsonRequestBodyParser::parse($request);

        $errors = [];
        $permalink = $this->parsePermalinkField($body['permalink'] ?? null, $errors);

        // parsePermalinkField returns null for both absent AND invalid input, but
        // only appends an error in the invalid case — so an empty $errors here means
        // the field was simply missing.
        if ($permalink === null && $errors === []) {
            $errors[] = new ValidationError('permalink', 'A target permalink is required to move a page.', 'required');
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        /** @var string $permalink */
        try {
            $output = $this->useCase->execute(new MoveEntitySubtreeInput(
                entityId: $id,
                newPermalink: $permalink,
            ));
        } catch (InvalidArgumentException $e) {
            throw new ValidationException([
                new ValidationError('permalink', $e->getMessage(), 'invalid'),
            ]);
        }

        return $this->response->create([
            'id' => $output->entityId,
            'permalink' => $output->permalink,
            'moved_count' => $output->movedCount,
        ]);
    }
}
