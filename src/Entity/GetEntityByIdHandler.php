<?php

declare(strict_types=1);

namespace NeNeRecords\Entity;

use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class GetEntityByIdHandler
{
    public function __construct(
        private GetEntityByIdUseCaseInterface $useCase,
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

        $output = $this->useCase->execute(new GetEntityByIdInput($id));

        // Unauthenticated callers may only read published records — a draft/scheduled
        // record is indistinguishable from a non-existent one (no metadata leak). See #828.
        $isAnonymous = !is_array($request->getAttribute('nene2.auth.claims'));
        if ($isAnonymous && $output->status !== EntityStatus::Published->value) {
            throw new EntityNotFoundException($id);
        }

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
            'created_at' => $output->createdAtIso,
            'updated_at' => $output->updatedAtIso,
            'layout' => $output->layout,
            'show_comments' => $output->showComments,
            'show_related' => $output->showRelated,
        ]);
    }
}
