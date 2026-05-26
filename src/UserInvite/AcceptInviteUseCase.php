<?php

declare(strict_types=1);

namespace NeNeRecords\UserInvite;

use Nene2\Http\SecureTokenHelper;
use NeNeRecords\Auth\UserRepositoryInterface;

final readonly class AcceptInviteUseCase implements AcceptInviteUseCaseInterface
{
    public function __construct(
        private UserRepositoryInterface $users,
    ) {
    }

    public function execute(AcceptInviteInput $input): void
    {
        $tokenHash = SecureTokenHelper::hash($input->token);
        $user = $this->users->findByInviteToken($tokenHash);

        if ($user === null || $user->inviteExpiresAt === null || $user->inviteExpiresAt < time()) {
            throw new InvalidInviteTokenException();
        }

        $newHash = password_hash($input->password, PASSWORD_BCRYPT);
        $this->users->updatePassword($user->id, $newHash);
        $this->users->clearInviteToken($user->id);
    }
}
