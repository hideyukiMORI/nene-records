<?php

declare(strict_types=1);

namespace NeNeRecords\EntityType;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class UpdateEntityTypeHandler
{
    private const SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    /**
     * Valid tokens in permalink_pattern.
     * At minimum {slug} or {id} must appear so we can resolve an entity.
     */
    private const RESERVED_PATHS = ['/admin', '/login', '/forbidden', '/api'];

    public function __construct(
        private UpdateEntityTypeUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $id = (int) ($parameters['id'] ?? 0);

        if ($id <= 0) {
            throw new EntityTypeNotFoundException($id);
        }

        $body = JsonRequestBodyParser::parse($request);

        $errors = [];

        $name = trim((string) ($body['name'] ?? ''));
        $slug = trim((string) ($body['slug'] ?? ''));
        $isPinned = (bool) ($body['is_pinned'] ?? false);

        // labels: optional {"ja":"投稿","fr":"Articles"} – only string values are kept
        $rawLabels = $body['labels'] ?? null;
        $labels = null;
        if (is_array($rawLabels)) {
            $filtered = array_filter(
                array_map('strval', $rawLabels),
                static fn (string $v) => $v !== '',
            );
            $labels = $filtered !== [] ? array_map('strval', $filtered) : null;
        }

        // permalink_pattern: optional, e.g. "/{type}/{slug}", "/{type}/{year}/{month}/{slug}"
        $rawPattern = $body['permalink_pattern'] ?? null;
        $permalinkPattern = null;
        if (is_string($rawPattern) && trim($rawPattern) !== '') {
            $permalinkPattern = trim($rawPattern);

            // Must start with /
            if (!str_starts_with($permalinkPattern, '/')) {
                $errors[] = new ValidationError('permalink_pattern', 'Permalink pattern must start with /.', 'format');
            }

            // Must contain {slug} or {id} to be resolvable
            if (!str_contains($permalinkPattern, '{slug}') && !str_contains($permalinkPattern, '{id}')) {
                $errors[] = new ValidationError('permalink_pattern', 'Permalink pattern must contain {slug} or {id}.', 'format');
            }

            // Must not conflict with reserved application paths
            foreach (self::RESERVED_PATHS as $reserved) {
                if (str_starts_with($permalinkPattern, $reserved)) {
                    $errors[] = new ValidationError('permalink_pattern', "Permalink pattern must not start with reserved path \"{$reserved}\".", 'conflict');
                    break;
                }
            }
        }

        if ($name === '') {
            $errors[] = new ValidationError('name', 'Name is required.', 'required');
        }

        if ($slug === '') {
            $errors[] = new ValidationError('slug', 'Slug is required.', 'required');
        } elseif (preg_match(self::SLUG_PATTERN, $slug) !== 1) {
            $errors[] = new ValidationError('slug', 'Slug format is invalid.', 'format');
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $output = $this->useCase->execute(new UpdateEntityTypeInput(
            id: $id,
            name: $name,
            slug: $slug,
            isPinned: $isPinned,
            labels: $labels,
            permalinkPattern: $permalinkPattern,
        ));

        return $this->response->create([
            'id'                => $output->id,
            'name'              => $output->name,
            'slug'              => $output->slug,
            'is_pinned'         => $output->isPinned,
            'labels'            => $output->labels ?? new \stdClass(),
            'permalink_pattern' => $output->permalinkPattern,
        ]);
    }
}
