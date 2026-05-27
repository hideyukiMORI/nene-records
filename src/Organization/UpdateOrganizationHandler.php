<?php

declare(strict_types=1);

namespace NeNeRecords\Organization;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class UpdateOrganizationHandler
{
    private const SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    private const VALID_PLANS = ['free', 'starter', 'pro', 'enterprise'];

    public function __construct(
        private OrganizationRepositoryInterface $repository,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = (array) $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $id = (int) ($parameters['id'] ?? 0);
        $org = $this->repository->findById($id);

        if ($org === null) {
            throw new OrganizationNotFoundException($id);
        }

        $body = JsonRequestBodyParser::parse($request);
        $errors = [];

        $name = isset($body['name']) ? trim((string) $body['name']) : $org->name;
        $slug = isset($body['slug']) ? trim((string) $body['slug']) : $org->slug;
        $plan = isset($body['plan']) ? trim((string) $body['plan']) : $org->plan;
        $isActive = isset($body['is_active']) ? (bool) $body['is_active'] : $org->isActive;
        $customDomain = array_key_exists('custom_domain', $body)
            ? (($body['custom_domain'] !== null && $body['custom_domain'] !== '') ? trim((string) $body['custom_domain']) : null)
            : $org->customDomain;

        if ($name === '') {
            $errors[] = new ValidationError('name', 'Name is required.', 'required');
        }

        if ($slug === '') {
            $errors[] = new ValidationError('slug', 'Slug is required.', 'required');
        } elseif (preg_match(self::SLUG_PATTERN, $slug) !== 1) {
            $errors[] = new ValidationError('slug', 'Slug must be lowercase alphanumeric with hyphens.', 'format');
        }

        if (!in_array($plan, self::VALID_PLANS, true)) {
            $errors[] = new ValidationError('plan', 'Plan must be one of: ' . implode(', ', self::VALID_PLANS) . '.', 'invalid');
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $updated = new Organization(
            name: $name,
            slug: $slug,
            plan: $plan,
            isActive: $isActive,
            id: $id,
            customDomain: $customDomain,
        );

        $this->repository->update($updated);

        $refreshed = $this->repository->findById($id);

        return $this->response->create([
            'id' => $refreshed?->id,
            'name' => $refreshed?->name,
            'slug' => $refreshed?->slug,
            'custom_domain' => $refreshed?->customDomain,
            'plan' => $refreshed?->plan,
            'is_active' => $refreshed?->isActive,
            'created_at' => $refreshed?->createdAt,
            'updated_at' => $refreshed?->updatedAt,
        ]);
    }
}
