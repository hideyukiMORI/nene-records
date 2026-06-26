<?php

declare(strict_types=1);

namespace NeNeRecords\Tests\Signup;

use NeNeRecords\Signup\PublicSignupInput;
use NeNeRecords\Signup\PublicSignupOutput;
use NeNeRecords\Signup\PublicSignupUseCaseInterface;

/**
 * Test double for {@see PublicSignupUseCaseInterface}: records how many times it
 * was invoked and returns a canned tenant, so handler tests can assert that
 * throttled requests never reach provisioning.
 */
final class RecordingPublicSignupUseCase implements PublicSignupUseCaseInterface
{
    public int $calls = 0;

    public function execute(PublicSignupInput $input): PublicSignupOutput
    {
        ++$this->calls;

        return new PublicSignupOutput(
            token: 'token-' . $this->calls,
            expiresAt: time() + 3600,
            organizationId: 42,
            slug: $input->slug,
            email: $input->email,
            role: 'admin',
        );
    }
}
