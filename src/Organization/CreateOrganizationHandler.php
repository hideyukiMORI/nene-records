<?php

declare(strict_types=1);

namespace NeNeRecords\Organization;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class CreateOrganizationHandler implements RequestHandlerInterface
{
    private const SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    /** @var list<string> */
    private const VALID_PLANS = ['free', 'starter', 'pro', 'enterprise'];

    public function __construct(
        private CreateOrganizationUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = JsonRequestBodyParser::parse($request);

        $errors = [];

        $name         = trim((string) ($body['name'] ?? ''));
        $slug         = trim((string) ($body['slug'] ?? ''));
        $plan         = trim((string) ($body['plan'] ?? 'free'));
        $customDomain = isset($body['custom_domain']) && $body['custom_domain'] !== ''
            ? trim((string) $body['custom_domain'])
            : null;

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

        $output = $this->useCase->execute(new CreateOrganizationInput(
            name: $name,
            slug: $slug,
            plan: $plan,
            customDomain: $customDomain,
        ));

        return $this->response->create(
            [
                'id'            => $output->id,
                'name'          => $output->name,
                'slug'          => $output->slug,
                'custom_domain' => $output->customDomain,
                'plan'          => $output->plan,
                'is_active'     => $output->isActive,
            ],
            201,
            ['Location' => '/api/v1/organizations/' . $output->id],
        );
    }
}
