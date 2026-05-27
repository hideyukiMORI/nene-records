<?php

declare(strict_types=1);

namespace NeNeRecords\User;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use NeNeRecords\Auth\Role;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class CreateUserHandler
{
    public function __construct(
        private CreateUserUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = JsonRequestBodyParser::parse($request);

        $errors = [];

        $email    = trim((string) ($body['email'] ?? ''));
        $password = (string) ($body['password'] ?? '');
        $role     = trim((string) ($body['role'] ?? ''));

        if ($email === '') {
            $errors[] = new ValidationError('email', 'Email is required.', 'required');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = new ValidationError('email', 'Email format is invalid.', 'format');
        }

        if ($password === '') {
            $errors[] = new ValidationError('password', 'Password is required.', 'required');
        } elseif (strlen($password) < 8) {
            $errors[] = new ValidationError('password', 'Password must be at least 8 characters.', 'min_length');
        }

        if ($role === '') {
            $errors[] = new ValidationError('role', 'Role is required.', 'required');
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        // 組織 ID の解決:
        //  - nene2.org.id が設定されている (org-scoped route) → そちらを使う
        //  - なければリクエストボディの organization_id を使う（superadmin 直接指定）
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

        $output = $this->useCase->execute(new CreateUserInput(
            email: $email,
            password: $password,
            role: $role,
            organizationId: $organizationId,
            orgRole: $role,
        ));

        return $this->response->create(
            [
                'id'         => $output->id,
                'email'      => $output->email,
                'role'       => $output->role,
                'status'     => $output->status,
                'created_at' => $output->createdAt !== null ? date('c', $output->createdAt) : null,
            ],
            201,
            ['Location' => '/api/v1/users/' . $output->id],
        );
    }
}
