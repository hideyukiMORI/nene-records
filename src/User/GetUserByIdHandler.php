<?php

declare(strict_types=1);

namespace NeNeRecords\User;

use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class GetUserByIdHandler
{
    public function __construct(
        private GetUserByIdUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $id         = (int) ($parameters['id'] ?? 0);

        $output = $this->useCase->execute(new GetUserByIdInput($id));

        return $this->response->create([
            'id'              => $output->id,
            'email'           => $output->email,
            'role'            => $output->role,
            'organization_id' => $output->organizationId,
            'org_role'        => $output->orgRole,
            'status'          => $output->status,
            'pending_email'   => $output->pendingEmail,
            'display_name'    => $output->displayName,
            'full_name'       => $output->fullName,
            'job_title'       => $output->jobTitle,
            'created_at'      => $output->createdAt !== null ? date('c', $output->createdAt) : null,
            'updated_at'      => $output->updatedAt !== null ? date('c', $output->updatedAt) : null,
        ]);
    }
}
