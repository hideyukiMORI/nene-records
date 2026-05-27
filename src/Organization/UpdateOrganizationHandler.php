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
use Psr\Http\Server\RequestHandlerInterface;

final readonly class UpdateOrganizationHandler implements RequestHandlerInterface
{
    private const SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    /** @var list<string> */
    private const VALID_PLANS = ['free', 'starter', 'pro', 'enterprise'];

    public function __construct(
        private UpdateOrganizationUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = (array) $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $id         = (int) ($parameters['id'] ?? 0);

        $body   = JsonRequestBodyParser::parse($request);
        $errors = [];

        $name     = isset($body['name']) ? trim((string) $body['name']) : null;
        $slug     = isset($body['slug']) ? trim((string) $body['slug']) : null;
        $plan     = isset($body['plan']) ? trim((string) $body['plan']) : null;
        $isActive = isset($body['is_active']) ? (bool) $body['is_active'] : null;

        $updateCustomDomain = array_key_exists('custom_domain', $body);
        $customDomain       = $updateCustomDomain
            ? (($body['custom_domain'] !== null && $body['custom_domain'] !== '') ? trim((string) $body['custom_domain']) : null)
            : null;

        if ($name !== null && $name === '') {
            $errors[] = new ValidationError('name', 'Name must not be empty.', 'required');
        }

        if ($slug !== null) {
            if ($slug === '') {
                $errors[] = new ValidationError('slug', 'Slug must not be empty.', 'required');
            } elseif (preg_match(self::SLUG_PATTERN, $slug) !== 1) {
                $errors[] = new ValidationError('slug', 'Slug must be lowercase alphanumeric with hyphens.', 'format');
            }
        }

        if ($plan !== null && !in_array($plan, self::VALID_PLANS, true)) {
            $errors[] = new ValidationError('plan', 'Plan must be one of: ' . implode(', ', self::VALID_PLANS) . '.', 'invalid');
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $output = $this->useCase->execute(new UpdateOrganizationInput(
            id: $id,
            name: $name,
            slug: $slug,
            plan: $plan,
            isActive: $isActive,
            updateCustomDomain: $updateCustomDomain,
            customDomain: $customDomain,
        ));

        return $this->response->create([
            'id'            => $output->id,
            'name'          => $output->name,
            'slug'          => $output->slug,
            'custom_domain' => $output->customDomain,
            'plan'          => $output->plan,
            'is_active'     => $output->isActive,
            'created_at'    => $output->createdAt,
            'updated_at'    => $output->updatedAt,
        ]);
    }
}
