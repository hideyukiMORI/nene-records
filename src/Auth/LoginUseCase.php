<?php

declare(strict_types=1);

namespace NeNeRecords\Auth;

use Nene2\Auth\TokenIssuerInterface;

final readonly class LoginUseCase
{
    private const TOKEN_TTL_SECONDS = 86400; // 24 hours

    public function __construct(
        private UserRepositoryInterface $users,
        private TokenIssuerInterface $tokenIssuer,
    ) {
    }

    public function execute(LoginInput $input): LoginOutput
    {
        $user = $this->users->findByEmail($input->email);

        if ($user === null || !password_verify($input->password, $user->passwordHash)) {
            throw new InvalidCredentialsException();
        }

        $role = Role::tryFrom($user->role);

        if ($role === null) {
            throw new InvalidCredentialsException();
        }

        $expiresAt = time() + self::TOKEN_TTL_SECONDS;

        $token = $this->tokenIssuer->issue([
            'sub' => $user->email,
            'role' => $role->value,
            'iat' => time(),
            'exp' => $expiresAt,
        ]);

        return new LoginOutput(
            token: $token,
            expiresAt: $expiresAt,
            email: $user->email,
            role: $role->value,
        );
    }
}
