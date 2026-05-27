<?php

declare(strict_types=1);

namespace NeNeRecords\User;

use NeNeRecords\Auth\UserRepositoryInterface;

final readonly class UpdateUserProfileUseCase implements UpdateUserProfileUseCaseInterface
{
    public function __construct(
        private UserRepositoryInterface $users,
        private UserProfileRepositoryInterface $profiles,
    ) {
    }

    public function execute(UpdateUserProfileInput $input): UpdateUserProfileOutput
    {
        $user = $this->users->findById($input->userId);

        if ($user === null) {
            throw new UserNotFoundException($input->userId);
        }

        $profile = $this->profiles->upsert(
            userId: $input->userId,
            displayName: $input->displayName,
            fullName: $input->fullName,
            jobTitle: $input->jobTitle,
        );

        return new UpdateUserProfileOutput(
            userId: $profile->userId,
            displayName: $profile->displayName,
            fullName: $profile->fullName,
            jobTitle: $profile->jobTitle,
        );
    }
}
