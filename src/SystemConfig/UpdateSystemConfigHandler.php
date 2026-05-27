<?php

declare(strict_types=1);

namespace NeNeRecords\SystemConfig;

use Nene2\Error\ProblemDetailsResponseFactory;
use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class UpdateSystemConfigHandler implements RequestHandlerInterface
{
    private const VALID_MODES = ['single', 'subdomain', 'path'];

    public function __construct(
        private SystemConfigRepositoryInterface $config,
        private JsonResponseFactory $json,
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = JsonRequestBodyParser::parse($request);

        $mode = isset($body['tenant_resolution_mode'])
            ? (string) $body['tenant_resolution_mode']
            : null;

        if ($mode === null || !in_array($mode, self::VALID_MODES, true)) {
            return $this->problemDetails->create(
                $request,
                'validation-failed',
                'Validation Failed',
                422,
                'tenant_resolution_mode must be one of: ' . implode(', ', self::VALID_MODES),
            );
        }

        $this->config->set('tenant_resolution_mode', $mode);

        if (isset($body['tenant_org_slug'])) {
            $this->config->set('tenant_org_slug', (string) $body['tenant_org_slug']);
        }

        if (isset($body['tenant_base_domain'])) {
            $this->config->set('tenant_base_domain', (string) $body['tenant_base_domain']);
        }

        $all = $this->config->all();

        return $this->json->create([
            'tenant_resolution_mode' => $all['tenant_resolution_mode'] ?? 'single',
            'tenant_org_slug'        => $all['tenant_org_slug'] ?? '',
            'tenant_base_domain'     => $all['tenant_base_domain'] ?? 'localhost',
        ]);
    }
}
