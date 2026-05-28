<?php

declare(strict_types=1);

namespace NeNeRecords\User;

use NeNeRecords\Auth\UserRepositoryInterface;

final readonly class GetUserByIdUseCase implements GetUserByIdUseCaseInterface
{
    public function __construct(
        private UserRepositoryInterface $users,
        private UserProfileRepositoryInterface $profiles,
    ) {
    }

    public function execute(GetUserByIdInput $input): GetUserByIdOutput
    {
        $user = $this->users->findById($input->id);

        if ($user === null) {
            throw new UserNotFoundException($input->id);
        }

        $profile = $this->profiles->findByUserId($user->id);

        return new GetUserByIdOutput(
            id: $user->id,
            email: $user->email,
            role: $user->role,
            organizationId: $user->organizationId,
            orgRole: $user->orgRole,
            status: $user->status,
            pendingEmail: $user->pendingEmail,
            displayName: $profile?->displayName,
            fullName: $profile?->fullName,
            jobTitle: $profile?->jobTitle,
            createdAt: $user->createdAt,
            updatedAt: $user->updatedAt,
        );
    }
}
