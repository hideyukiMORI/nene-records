<?php

declare(strict_types=1);

namespace NeNeRecords\ContactSubmission;

use Nene2\Routing\Router;
use NeNeRecords\PublicRecord\ContactSubmissionProxyRoute;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ContactSubmissionRouteRegistrar
{
    public function __construct(
        private SubmitContactFormHandler $submitHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $submit = $this->submitHandler;

        // Under /api/v1/public/ so it is unauthenticated (the visitor has no account), but
        // deliberately *not* added to OrgResolverMiddleware::BYPASS_PREFIXES: the host must
        // still resolve to an organization, or the tenant's connect-token cannot be found.
        $router->post(
            ContactSubmissionProxyRoute::PATH,
            static fn (ServerRequestInterface $request) => $submit->handle($request),
        );
    }
}
