<?php

declare(strict_types=1);

namespace NeNeRecords\Organization;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class CreateOrganizationHandler
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
        $body = JsonRequestBodyParser::parse($request);

        $errors = [];

        $name = trim((string) ($body['name'] ?? ''));
        $slug = trim((string) ($body['slug'] ?? ''));
        $plan = trim((string) ($body['plan'] ?? 'free'));
        $customDomain = isset($body['custom_domain']) && $body['custom_domain'] !== '' ? trim((string) $body['custom_domain']) : null;

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

        $org = new Organization(
            name: $name,
            slug: $slug,
            plan: $plan,
            isActive: true,
            customDomain: $customDomain,
        );

        $id = $this->repository->save($org);

        return $this->response->create(
            [
                'id' => $id,
                'name' => $name,
                'slug' => $slug,
                'custom_domain' => $customDomain,
                'plan' => $plan,
                'is_active' => true,
            ],
            201,
            ['Location' => '/api/v1/organizations/' . $id],
        );
    }
}
