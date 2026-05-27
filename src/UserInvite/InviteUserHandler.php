<?php

declare(strict_types=1);

namespace NeNeRecords\UserInvite;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use NeNeRecords\Auth\Role;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class InviteUserHandler
{
    public function __construct(
        private InviteUserUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = JsonRequestBodyParser::parse($request);

        $errors = [];

        $email = trim((string) ($body['email'] ?? ''));
        $role  = trim((string) ($body['role'] ?? ''));

        if ($email === '') {
            $errors[] = new ValidationError('email', 'Email is required.', 'required');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = new ValidationError('email', 'Email format is invalid.', 'format');
        }

        if ($role === '') {
            $errors[] = new ValidationError('role', 'Role is required.', 'required');
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $appBaseUrl = (string) ($body['app_base_url'] ?? '');

        if ($appBaseUrl === '') {
            $scheme     = $request->getUri()->getScheme();
            $host       = $request->getUri()->getHost();
            $port       = $request->getUri()->getPort();
            $appBaseUrl = $scheme . '://' . $host . ($port !== null ? ':' . $port : '');
        }

        // 組織 ID の解決（CreateUserHandler と同様のロジック）
        $resolvedOrgId = $request->getAttribute('nene2.org.id');
        $claims        = $request->getAttribute('nene2.auth.claims');
        $callerRole    = is_array($claims) ? Role::tryFrom((string) ($claims['role'] ?? '')) : null;

        if ($resolvedOrgId !== null) {
            $organizationId = (int) $resolvedOrgId;
        } elseif ($callerRole === Role::Superadmin && isset($body['organization_id'])) {
            $organizationId = (int) $body['organization_id'];
        } else {
            $organizationId = null;
        }

        $output = $this->useCase->execute(new InviteUserInput(
            email: $email,
            role: $role,
            appBaseUrl: $appBaseUrl,
            organizationId: $organizationId,
            orgRole: $role,
        ));

        return $this->response->create(
            [
                'id'     => $output->id,
                'email'  => $output->email,
                'role'   => $output->role,
                'status' => $output->status,
            ],
            201,
            ['Location' => '/api/v1/users/' . $output->id],
        );
    }
}
