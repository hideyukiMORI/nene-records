<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

use DateTimeImmutable;
use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use NeNeRecords\Layout\PublicLayouts;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class UpdateEntityHandler
{
    use ParsesPermalinkField;

    public function __construct(
        private UpdateEntityUseCaseInterface $useCase,
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

        $rawTypeId = $body['entity_type_id'] ?? null;
        $entityTypeId = is_int($rawTypeId)
            ? $rawTypeId
            : filter_var(is_string($rawTypeId) ? $rawTypeId : null, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);

        if ($entityTypeId === null || $entityTypeId <= 0) {
            $errors[] = new ValidationError('entity_type_id', 'Entity type id is required and must be a positive integer.', 'required');
        }

        $rawSlug = $body['slug'] ?? null;
        $slug = is_string($rawSlug) && trim($rawSlug) !== '' ? trim($rawSlug) : null;

        $rawStatus = $body['status'] ?? null;
        $status = is_string($rawStatus) ? EntityStatus::tryFrom($rawStatus) ?? EntityStatus::Draft : EntityStatus::Draft;

        $rawPublishedAt = $body['published_at'] ?? null;
        $publishedAt = is_string($rawPublishedAt) ? (DateTimeImmutable::createFromFormat(DATE_ATOM, $rawPublishedAt) ?: null) : null;

        $rawScheduledAt = $body['scheduled_at'] ?? null;
        $scheduledAt = is_string($rawScheduledAt) ? (DateTimeImmutable::createFromFormat(DATE_ATOM, $rawScheduledAt) ?: null) : null;

        $rawMetaTitle = $body['meta_title'] ?? null;
        $metaTitle = is_string($rawMetaTitle) && trim($rawMetaTitle) !== '' ? trim($rawMetaTitle) : null;

        $rawMetaDescription = $body['meta_description'] ?? null;
        $metaDescription = is_string($rawMetaDescription) && trim($rawMetaDescription) !== '' ? trim($rawMetaDescription) : null;

        // layout: optional override; null/empty = inherit the type's default_layout.
        $rawLayout = $body['layout'] ?? null;
        $layout = is_string($rawLayout) && trim($rawLayout) !== '' ? trim($rawLayout) : null;
        if ($layout !== null && !PublicLayouts::isValid($layout)) {
            $errors[] = new ValidationError('layout', 'Unknown layout.', 'invalid');
        }

        // A custom-layout page renders most content inside a sandboxed iframe,
        // which crawlers attribute weakly. Require a meta description so the
        // *published* page always carries crawlable text (the dual-representation
        // contract). Drafts are not crawled, so they may omit it while authoring.
        if ($status === EntityStatus::Published && $layout === 'custom' && $metaDescription === null) {
            $errors[] = new ValidationError('meta_description', 'Custom layout pages require a meta description before publishing.', 'required');
        }

        // permalink: optional custom canonical path; null/empty = use the type pattern.
        $permalink = $this->parsePermalinkField($body['permalink'] ?? null, $errors);

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        /** @var int $entityTypeId */
        $output = $this->useCase->execute(new UpdateEntityInput(
            id: $id,
            entityTypeId: $entityTypeId,
            slug: $slug,
            status: $status,
            publishedAt: $publishedAt,
            metaTitle: $metaTitle,
            metaDescription: $metaDescription,
            scheduledAt: $scheduledAt,
            layout: $layout,
            permalink: $permalink,
        ));

        return $this->response->create([
            'id' => $output->id,
            'entity_type_id' => $output->entityTypeId,
            'slug' => $output->slug,
            'permalink' => $output->permalink,
            'status' => $output->status,
            'published_at' => $output->publishedAtIso,
            'scheduled_at' => $output->scheduledAtIso,
            'is_deleted' => $output->isDeleted,
            'deleted_at' => $output->deletedAtIso,
            'meta_title' => $output->metaTitle,
            'meta_description' => $output->metaDescription,
            'layout' => $output->layout,
        ]);
    }
}
