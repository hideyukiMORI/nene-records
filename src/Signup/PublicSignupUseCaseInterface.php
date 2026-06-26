<?php

declare(strict_types=1);

namespace NeNeRecords\Signup;

/**
 * Provisions a brand-new tenant (organization + admin user) and signs the admin
 * in. Extracted so {@see PublicSignupHandler} can depend on the contract rather
 * than the concrete use case (mirrors {@see \NeNeRecords\Comment\PostCommentUseCaseInterface}).
 */
interface PublicSignupUseCaseInterface
{
    public function execute(PublicSignupInput $input): PublicSignupOutput;
}
